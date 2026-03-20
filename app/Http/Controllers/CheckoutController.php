<?php

namespace App\Http\Controllers;

use App\Jobs\SendBookingConfirmation;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\PromoCode;
use App\Models\Reservation;
use App\Repositories\BookingRepository;
use App\Services\PaymentFailureNotifier;
use App\Services\ServiceFeeCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\PaymentIntent;
use Stripe\Exception\InvalidRequestException;
use Stripe\Stripe;

class CheckoutController extends Controller
{
    public function __construct(
        private readonly BookingRepository $bookingRepo,
        private readonly PaymentFailureNotifier $paymentFailureNotifier
    ) {}

    // ── Show checkout ─────────────────────────────────────────────
    public function show(string $token)
    {
        $reservation = Reservation::with(['event', 'items.ticketTier'])
            ->where('token', $token)
            ->firstOrFail();

        // Already completed → redirect to booking
        if ($reservation->status === 'completed') {
            $booking = Booking::where('reservation_id', $reservation->id)
                ->where('status', 'paid')->first();
            if ($booking) return redirect()->route('booking.show', $booking->reference);
        }

        // Expired
        if ($reservation->isExpired()) {
            if ($reservation->status === 'pending') {
                ReservationController::releaseReservation($reservation);
            }
            return redirect()->route('events.show', $reservation->event->slug)
                ->withErrors(['reservation' => 'Your 10-minute ticket hold has expired. Please select tickets again.']);
        }

        $feePct = config('ticketly.service_fee_percentage', 5);
        $portalFeePct = config('ticketly.portal_fee_percentage', 10);

        return view('checkout.show', compact('reservation', 'feePct', 'portalFeePct'));
    }

    // ── Create / update PaymentIntent ─────────────────────────────
    public function createIntent(Request $request, string $token)
    {
        $reservation = Reservation::with(['event', 'items.ticketTier'])
            ->where('token', $token)
            ->first();

        if (!$reservation || $reservation->status !== 'pending') {
            return response()->json([
                'error'       => 'Your ticket hold is no longer active.',
                'expired'     => true,
                'redirect_to' => route('events.index'),
            ], 422);
        }

        if ($reservation->isExpired()) {
            ReservationController::releaseReservation($reservation);
            return response()->json([
                'error'       => 'Your 10-minute ticket hold has expired. Tickets are being released now.',
                'expired'     => true,
                'redirect_to' => route('events.show', $reservation->event->slug),
            ], 422);
        }

        $hasCustomerPayload = $request->filled('name')
            || $request->filled('email')
            || $request->filled('phone')
            || $request->filled('city')
            || $request->filled('state')
            || $request->filled('postal_code')
            || $request->filled('country');

        $validated = [];
        if ($hasCustomerPayload) {
            $validated = $request->validate([
                'name'          => 'required|string|max:255',
                'email'         => 'required|email:rfc|max:255',
                'phone'         => 'required|string|max:30',
                'city'          => 'nullable|string|max:255',
                'state'         => 'nullable|string|max:255',
                'postal_code'   => 'nullable|string|max:20',
                'country'       => 'nullable|string|size:2',
            ]);
        }

        // Promo
        $subtotal = (float) $reservation->subtotal;
        $basePricing = ServiceFeeCalculator::total($subtotal);
        $promoCode = null;
        $discountAmount = 0.0;
        if ($request->filled('promo_code')) {
            $promoCode = PromoCode::where('code', strtoupper(trim($request->promo_code)))
                ->where('is_active', true)
                ->whereNull('deleted_at')
                ->first();
            if ($promoCode && $promoCode->isValid()) {
                $event = $reservation->event;
                $isApplicable = false;
                if ($promoCode->event_id) {
                    $isApplicable = (int) $promoCode->event_id === (int) $reservation->event_id;
                } elseif ($promoCode->organiser_id && $event?->organiser_id) {
                    $isApplicable = (int) $promoCode->organiser_id === (int) $event->organiser_id;
                }

                if ($isApplicable) {
                    $discountAmount = $promoCode->calculateDiscount($basePricing['gross_total']);
                } else {
                    $promoCode = null;
                }
            } else {
                $promoCode = null;
            }
        }

        $pricing = ServiceFeeCalculator::total($subtotal, $discountAmount);

        if ($pricing['total'] > 0 && $hasCustomerPayload) {
            $addressValidated = $request->validate([
                'city'          => 'required|string|max:255',
                'state'         => 'required|string|max:255',
                'postal_code'   => 'required|string|max:20',
                'country'       => 'required|string|size:2',
            ]);
            $validated = array_merge($validated, $addressValidated);
        }
        $connectedOrganiser = $this->connectedOrganiser($reservation);
        $platformFee = $pricing['portal_fee'] + $pricing['service_fee'];

        $updateData = [
            'promo_code_id'   => $promoCode?->id,
            'discount_amount' => $pricing['discount'],
            'portal_fee'      => $pricing['portal_fee'],
            'service_fee'     => $pricing['service_fee'],
            'total'           => $pricing['total'],
        ];
        if (!empty($validated)) {
            $updateData['customer_name'] = $validated['name'];
            $updateData['customer_email'] = $validated['email'];
            $updateData['customer_phone'] = $validated['phone'];
        }
        $reservation->update($updateData);

        $hasCustomerDetails = !empty($reservation->customer_name)
            && !empty($reservation->customer_email)
            && !empty($reservation->customer_phone);

        $customerEmail = $validated['email'] ?? $reservation->customer_email;
        if (!empty($customerEmail)) {
            $customer = Customer::where('email', $customerEmail)->first();
            if ($customer && $customer->is_suspended) {
                return response()->json([
                    'error' => 'This customer account is suspended. Please contact support.',
                ], 403);
            }
        }

        if ($pricing['total'] <= 0 && !ticketly_setting('allow_free_events', true)) {
            return response()->json([
                'error' => 'Free events are currently disabled. Please contact support.',
            ], 422);
        }

        if ($pricing['total'] <= 0) {
            if (!$hasCustomerDetails) {
                return response()->json([
                    'needs_customer' => true,
                    'message'        => 'Please enter your contact details to confirm free tickets.',
                ]);
            }

            $booking = $this->createBookingFromReservation($reservation, 'FREE');
            return response()->json([
                'free'     => true,
                'redirect' => route('booking.show', $booking->reference),
            ]);
        }

        Stripe::setApiKey(config('services.stripe.secret'));

        $amountPence = max(5000, ServiceFeeCalculator::toPence($pricing['total']));

        $customerName = $validated['name'] ?? $reservation->customer_name;
        $customerEmail = $validated['email'] ?? $reservation->customer_email;
        $customerPhone = $validated['phone'] ?? $reservation->customer_phone;

        $shipping = null;

        $metadata = [
            'reservation_id'    => (string) $reservation->id,
            'reservation_token' => $token,
        ];
        if ($connectedOrganiser) {
            $metadata['organiser_id'] = (string) $connectedOrganiser->id;
            $metadata['organiser_stripe_account_id'] = (string) $connectedOrganiser->stripe_account_id;
        }
        if (!empty($customerEmail)) {
            $metadata['customer_email'] = (string) $customerEmail;
        }
        if (!empty($customerName)) {
            $metadata['customer_name'] = (string) $customerName;
        }

        if ($reservation->stripe_payment_intent_id) {
            try {
                $intent = $this->updateIntent(
                    $reservation->stripe_payment_intent_id,
                    $amountPence,
                    $metadata,
                    $customerEmail,
                    $shipping,
                    $connectedOrganiser?->stripe_account_id,
                    $platformFee
                );
            } catch (\Exception $e) {
                // Intent may be in non-updatable state; create fresh.
                $intent = $this->createFreshIntent(
                    $amountPence,
                    ['email' => $customerEmail],
                    $reservation->event->title,
                    $metadata,
                    $shipping,
                    $connectedOrganiser?->stripe_account_id,
                    $platformFee
                );
                $reservation->update(['stripe_payment_intent_id' => $intent->id]);
            }
        } else {
            $intent = $this->createFreshIntent(
                $amountPence,
                ['email' => $customerEmail],
                $reservation->event->title,
                $metadata,
                $shipping,
                $connectedOrganiser?->stripe_account_id,
                $platformFee
            );
            $reservation->update(['stripe_payment_intent_id' => $intent->id]);
        }
        return response()->json([
            'client_secret'  => $intent->client_secret,
            'intent_id'      => $intent->id,
            'amount'         => $pricing['total'],
            'discount'       => $pricing['discount'],
            'gross_total'    => $pricing['gross_total'],
            'portal_fee'     => $pricing['portal_fee'],
            'service_fee'    => $pricing['service_fee'],
            'currency'       => ticketly_currency(),
        ]);
    }

    // ── Poll status ───────────────────────────────────────────────
    public function pollStatus(Request $request, string $token)
    {
        $reservation = Reservation::with('event')->where('token', $token)->firstOrFail();

        if ($reservation->status === 'pending' && $reservation->isExpired()) {
            ReservationController::releaseReservation($reservation);
            return response()->json([
                'status'      => 'expired',
                'message'     => 'Your 10-minute hold has expired. Tickets are being released.',
                'redirect_to' => route('events.show', $reservation->event->slug),
            ]);
        }

        if (in_array($reservation->status, ['expired', 'released'])) {
            return response()->json([
                'status'      => 'expired',
                'message'     => 'Your ticket hold is no longer active.',
                'redirect_to' => route('events.show', $reservation->event->slug),
            ]);
        }

        if ($reservation->status === 'completed') {
            $booking = Booking::where('reservation_id', $reservation->id)->where('status', 'paid')->first();
            if ($booking) {
                return response()->json([
                    'status'   => 'paid',
                    'redirect' => route('booking.show', $booking->reference),
                ]);
            }
        }

        if ($reservation->stripe_payment_intent_id) {
            try {
                Stripe::setApiKey(config('services.stripe.secret'));
                $intent = PaymentIntent::retrieve($reservation->stripe_payment_intent_id);

                if ($intent->status === 'succeeded' && $reservation->status === 'pending') {
                    if ($reservation->isExpired()) {
                        ReservationController::releaseReservation($reservation);
                        return response()->json([
                            'status'      => 'expired',
                            'message'     => 'Your hold expired before payment completion. No payment will be taken.',
                            'redirect_to' => route('events.show', $reservation->event->slug),
                        ]);
                    }

                    $booking = $this->createBookingFromReservation($reservation, $intent->id);
                    return response()->json([
                        'status'   => 'paid',
                        'redirect' => route('booking.show', $booking->reference),
                    ]);
                }
                $failedStatuses = ['canceled', 'requires_payment_method'];
                $hasError = !empty($intent->last_payment_error?->message);
                $attempted = $request->boolean('attempt');
                $isFailedState = $intent->status === 'canceled'
                    || ($intent->status === 'requires_payment_method' && ($hasError || $attempted));

                if (in_array($intent->status, $failedStatuses, true) && $isFailedState) {
                    $errorMessage = $intent->last_payment_error?->message
                        ?? 'Your payment could not be processed.';

                    $this->paymentFailureNotifier->notify($reservation, $errorMessage);

                    return response()->json([
                        'status'  => 'failed',
                        'message' => $errorMessage,
                    ]);
                }
            } catch (\Exception $e) {
                Log::warning('[Checkout] Poll error: ' . $e->getMessage());
            }
        }

        return response()->json([
            'status'  => 'pending',
            'expires' => $reservation->secondsRemaining(),
        ]);
    }

    // ── Success return URL ────────────────────────────────────────
    public function success(string $token)
    {
        $reservation = Reservation::with('event')->where('token', $token)->firstOrFail();

        if ($reservation->status === 'completed') {
            $booking = Booking::where('reservation_id', $reservation->id)->where('status', 'paid')->first();
            if ($booking) return redirect()->route('booking.show', $booking->reference);
        }

        return view('checkout.processing', compact('reservation', 'token'));
    }

    // ── Cancel / retry ────────────────────────────────────────────
    // public function cancel(string $token)
    // {
    //     $reservation = Reservation::with('event')->where('token', $token)->first();
    //     if ($reservation && $reservation->isActive()) {
    //         return redirect()->route('checkout.show', $token)->with('payment_cancelled', true);
    //     }
    //     return redirect()->route('events.index');
    // }

    public function cancel(Request $request, string $token)
    {
        $reservation = Reservation::with('event')->where('token', $token)->first();

        if ($reservation && $reservation->isActive()) {

            $error = $request->query('error');

            return redirect()
                ->route('checkout.show', $token)
                ->with('payment_error', $error);
        }

        return redirect()->route('events.index');
    }

    // ── Public helper (used by WebhookController + poll) ──────────
    public function createBookingFromReservation(Reservation $reservation, string $intentId): Booking
    {
        $booking = $this->bookingRepo->createFromReservation($reservation, $intentId);
        SendBookingConfirmation::dispatchSync($booking);
        return $booking;
    }

    // ── Private ───────────────────────────────────────────────────
    private function createFreshIntent(
        int $amountPence,
        array $customer,
        string $eventTitle,
        array $metadata,
        ?array $shipping = null,
        ?string $stripeAccountId = null,
        float $platformFee = 0.0
    ): PaymentIntent
    {
        $payload = [
            'amount'               => $amountPence,
            'currency'             => strtolower(ticketly_currency()),
            'automatic_payment_methods' => ['enabled' => true],
            'receipt_email'        => $customer['email'] ?? null,
            'description'          => $eventTitle,
            'metadata'             => $metadata,
        ];
        if (!empty($shipping)) {
            $payload['shipping'] = $shipping;
        }

        if (!empty($stripeAccountId)) {
            $payload['application_fee_amount'] = $this->platformFeePence($platformFee, $amountPence);
            $payload['transfer_data'] = ['destination' => $stripeAccountId];
        }

        try {
            return PaymentIntent::create($payload);
        } catch (InvalidRequestException $e) {
            if (empty($stripeAccountId)) {
                throw $e;
            }

            Log::warning('[Checkout] PaymentIntent create failed with Connect destination. Retrying without transfer_data.', [
                'error' => $e->getMessage(),
                'destination' => $stripeAccountId,
            ]);

            unset($payload['application_fee_amount'], $payload['transfer_data']);
            return PaymentIntent::create($payload);
        }
    }

    private function updateIntent(
        string $intentId,
        int $amountPence,
        array $metadata,
        ?string $customerEmail,
        ?array $shipping,
        ?string $stripeAccountId,
        float $platformFee
    ): PaymentIntent {
        $intentUpdate = [
            'amount'   => $amountPence,
            'metadata' => $metadata,
        ];
        if (!empty($customerEmail)) {
            $intentUpdate['receipt_email'] = $customerEmail;
        }
        if (!empty($shipping)) {
            $intentUpdate['shipping'] = $shipping;
        }
        if (!empty($stripeAccountId)) {
            $intentUpdate['application_fee_amount'] = $this->platformFeePence($platformFee, $amountPence);
            $intentUpdate['transfer_data'] = ['destination' => $stripeAccountId];
        }

        try {
            return PaymentIntent::update($intentId, $intentUpdate);
        } catch (InvalidRequestException $e) {
            if (empty($stripeAccountId)) {
                throw $e;
            }

            Log::warning('[Checkout] PaymentIntent update failed with Connect destination. Retrying without transfer_data.', [
                'error' => $e->getMessage(),
                'destination' => $stripeAccountId,
            ]);

            unset($intentUpdate['application_fee_amount'], $intentUpdate['transfer_data']);
            return PaymentIntent::update($intentId, $intentUpdate);
        }
    }

    private function connectedOrganiser(Reservation $reservation): ?\App\Models\Organiser
    {
        $reservation->loadMissing('event.organiser');
        $organiser = $reservation->event?->organiser;
        if (!$organiser) return null;
        if (!$organiser->stripe_account_id) return null;
        if (!$organiser->stripe_onboarding_complete) return null;
        return $organiser;
    }

    private function platformFeePence(float $platformFee, int $amountPence): int
    {
        $feePence = ServiceFeeCalculator::toPence($platformFee);
        return min($feePence, $amountPence);
    }
}
