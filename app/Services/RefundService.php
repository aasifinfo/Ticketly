<?php

namespace App\Services;

use App\Jobs\SendRefundConfirmation;
use App\Models\Booking;
use App\Models\TicketTier;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\Refund as StripeRefund;
use Stripe\Stripe;

/**
 * RefundService
 *
 * Single-responsibility service for processing Stripe refunds.
 * All refund logic passes through here — webhook handler, organiser portal,
 * and future admin panel all use the same entry point.
 *
 * PCI-DSS: No card data is ever stored. Refunds are processed solely
 * via PaymentIntent ID or Charge ID stored at booking time.
 */
class RefundService
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    /**
     * Process a full or partial refund for a booking.
     *
     * @param  Booking     $booking
     * @param  float|null  $amount       null = full refund
     * @param  string      $reason
     * @param  bool        $restoreStock Whether to restore available_quantity on tiers
     * @return array{success: bool, refund_id: string|null, error: string|null}
     */
    public function process(
        Booking $booking,
        ?float  $amount = null,
        string  $reason = 'requested_by_customer',
        bool    $restoreStock = true
    ): array {
        // ── Guard: only paid bookings can be refunded ───────────────
        if (!in_array($booking->status, ['paid', 'partially_refunded'], true)) {
            Log::warning('[RefundService] Attempted to refund non-paid booking', [
                'booking' => $booking->reference,
                'status'  => $booking->status,
            ]);
            return ['success' => false, 'refund_id' => null, 'error' => 'Booking is not in a paid state.'];
        }

        // ── Guard: no duplicate full refunds ─────────────────────────
        if ($booking->isFullyRefunded()) {
            return ['success' => false, 'refund_id' => null, 'error' => 'Booking has already been fully refunded.'];
        }

        $alreadyRefunded = (float) ($booking->refund_amount ?? 0);
        $originalTotal = (float) $booking->total + $alreadyRefunded;
        $refundAmount = $amount ?? max(0.0, (float) $booking->total);
        $amountPence  = (int) round($refundAmount * 100);

        if ($refundAmount <= 0) {
            return ['success' => false, 'refund_id' => null, 'error' => 'Refund amount must be greater than 0.'];
        }

        if (($alreadyRefunded + $refundAmount) - $originalTotal > 0.01) {
            return ['success' => false, 'refund_id' => null, 'error' => 'Refund exceeds original payment total.'];
        }

        // ── Resolve Stripe reference ─────────────────────────────────
        $stripeRef = $this->resolveStripeReference($booking);
        if (!$stripeRef) {
            return ['success' => false, 'refund_id' => null, 'error' => 'No Stripe payment reference found on this booking.'];
        }

        try {
            // ── Issue Stripe refund ───────────────────────────────────
            $refundData = array_merge($stripeRef, [
                'amount'   => $amountPence,
                'reason'   => $this->normaliseReason($reason),
                'metadata' => [
                    'booking_reference' => $booking->reference,
                    'refunded_by'       => 'system',
                ],
            ]);

            $stripeRefund = StripeRefund::create($refundData);

            // ── Update booking in a transaction ────────────────────────
            DB::transaction(function () use ($booking, $refundAmount, $reason, $stripeRefund, $restoreStock, $alreadyRefunded, $originalTotal) {
                $updatedRefundAmount = $alreadyRefunded + $refundAmount;
                $isFullRefund = $updatedRefundAmount >= $originalTotal;

                $booking->update([
                    'status'        => $isFullRefund ? 'refunded' : 'partially_refunded',
                    'refund_amount' => $updatedRefundAmount,
                    'refunded_at'   => now(),
                    'refund_reason' => $reason,
                ]);

                // ── Restore stock if full refund ───────────────────────
                if ($restoreStock && $isFullRefund) {
                    $this->restoreInventory($booking);
                }
            });

            // ── Dispatch notification ─────────────────────────────────
            dispatch(new SendRefundConfirmation($booking->fresh(), $refundAmount));

            Log::info('[RefundService] Refund processed', [
                'booking'   => $booking->reference,
                'amount'    => $refundAmount,
                'stripe_id' => $stripeRefund->id,
            ]);

            return [
                'success'   => true,
                'refund_id' => $stripeRefund->id,
                'error'     => null,
            ];

        } catch (\Stripe\Exception\InvalidRequestException $e) {
            Log::error('[RefundService] Stripe InvalidRequest: ' . $e->getMessage(), [
                'booking' => $booking->reference,
            ]);
            return ['success' => false, 'refund_id' => null, 'error' => 'Stripe error: ' . $e->getMessage()];

        } catch (\Exception $e) {
            Log::error('[RefundService] Unexpected error: ' . $e->getMessage(), [
                'booking' => $booking->reference,
                'trace'   => $e->getTraceAsString(),
            ]);
            return ['success' => false, 'refund_id' => null, 'error' => 'An unexpected error occurred processing the refund.'];
        }
    }

    /**
     * Process bulk refunds for a cancelled event.
     * Called by EventController when an event is cancelled.
     */
    public function processBulkCancellationRefunds(int $eventId, string $reason): array
    {
        $bookings = Booking::where('event_id', $eventId)
            ->whereIn('status', ['paid', 'partially_refunded'])
            ->with('items.ticketTier')
            ->get();

        $results = ['processed' => 0, 'failed' => 0, 'errors' => []];

        foreach ($bookings as $booking) {
            $result = $this->process($booking, null, $reason, true);
            if ($result['success']) {
                $results['processed']++;
            } else {
                $results['failed']++;
                $results['errors'][] = [$booking->reference => $result['error']];
            }
        }

        Log::info('[RefundService] Bulk refund complete', [
            'event_id'  => $eventId,
            'processed' => $results['processed'],
            'failed'    => $results['failed'],
        ]);

        return $results;
    }

    // ── Private helpers ───────────────────────────────────────────

    private function resolveStripeReference(Booking $booking): ?array
    {
        if ($booking->stripe_payment_intent_id) {
            return ['payment_intent' => $booking->stripe_payment_intent_id];
        }
        if ($booking->stripe_charge_id) {
            return ['charge' => $booking->stripe_charge_id];
        }
        return null;
    }

    private function restoreInventory(Booking $booking): void
    {
        $booking->loadMissing('items');
        foreach ($booking->items as $item) {
            TicketTier::where('id', $item->ticket_tier_id)
                ->lockForUpdate()
                ->increment('available_quantity', $item->quantity);
        }
        Log::info('[RefundService] Inventory restored for booking: ' . $booking->reference);
    }

    private function normaliseReason(string $reason): string
    {
        // Stripe accepts only: duplicate, fraudulent, requested_by_customer
        return in_array($reason, ['duplicate', 'fraudulent', 'requested_by_customer'])
            ? $reason
            : 'requested_by_customer';
    }
}
