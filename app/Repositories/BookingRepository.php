<?php

namespace App\Repositories;

use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Customer;
use App\Models\Reservation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * BookingRepository
 *
 * Responsible for the single operation of converting a completed
 * Stripe PaymentIntent into a persisted Booking record.
 *
 * Idempotent: safe to call multiple times for the same reservation
 * (webhook retries, poll fallback). Uses lockForUpdate to prevent races.
 */
class BookingRepository
{
    /**
     * Create a booking from a confirmed reservation.
     * Returns existing booking if already created (idempotent).
     */
    public function createFromReservation(Reservation $reservation, string $paymentIntentId): Booking
    {
        return DB::transaction(function () use ($reservation, $paymentIntentId) {
            // Idempotency check inside transaction
            $existing = Booking::where('reservation_id', $reservation->id)
                ->where('status', 'paid')
                ->lockForUpdate()
                ->first();

            if ($existing) {
                Log::info('[BookingRepository] Booking already exists – returning.', [
                    'reference' => $existing->reference,
                ]);
                return $existing;
            }

            $reservation->loadMissing('items');

            $customer = null;
            if (!empty($reservation->customer_email)) {
                $customer = Customer::updateOrCreate(
                    ['email' => $reservation->customer_email],
                    [
                        'name'  => $reservation->customer_name,
                        'phone' => $reservation->customer_phone,
                    ]
                );
            }

            $booking = Booking::create([
                'event_id'                 => $reservation->event_id,
                'reservation_id'           => $reservation->id,
                'customer_id'              => $customer?->id,
                'customer_name'            => $reservation->customer_name,
                'customer_email'           => $reservation->customer_email,
                'customer_phone'           => $reservation->customer_phone,
                'promo_code_id'            => $reservation->promo_code_id,
                'discount_amount'          => $reservation->discount_amount,
                'subtotal'                 => $reservation->subtotal,
                'portal_fee'               => $reservation->portal_fee ?? 0,
                'service_fee'              => $reservation->service_fee ?? 0,
                'total'                    => $reservation->total,
                'currency'                 => ticketly_currency(),
                'stripe_payment_intent_id' => $paymentIntentId,
                'status'                   => 'paid',
            ]);

            foreach ($reservation->items as $item) {
                BookingItem::create([
                    'booking_id'     => $booking->id,
                    'ticket_tier_id' => $item->ticket_tier_id,
                    'quantity'       => $item->quantity,
                    'unit_price'     => $item->unit_price,
                    'subtotal'       => $item->subtotal,
                ]);
            }

            // Increment promo usage
            if ($reservation->promo_code_id) {
                $reservation->promoCode?->increment('used_count');
            }

            $reservation->update(['status' => 'completed']);

            Log::info('[BookingRepository] Booking created.', [
                'reference' => $booking->reference,
                'total'     => $booking->total,
            ]);

            return $booking;
        });
    }

    /**
     * Find a booking by Stripe PaymentIntent ID.
     */
    public function findByPaymentIntent(string $intentId): ?Booking
    {
        return Booking::where('stripe_payment_intent_id', $intentId)->first();
    }

    /**
     * Find a booking by reference (customer-facing).
     */
    public function findByReference(string $reference): ?Booking
    {
        return Booking::with(['event', 'items.ticketTier', 'promoCode'])
            ->where('reference', strtoupper($reference))
            ->first();
    }
}
