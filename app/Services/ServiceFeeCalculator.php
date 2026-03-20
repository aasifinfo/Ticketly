<?php

namespace App\Services;

class ServiceFeeCalculator
{
    /**
     * Calculate the service fee for a given subtotal.
     */
    public static function fee(float $subtotal): float
    {
        if ($subtotal <= 0) {
            return 0.0;
        }

        $pct = (float) config('ticketly.service_fee_percentage', 5);

        return round($subtotal * ($pct / 100), 2);
    }

    /**
     * Calculate the portal fee for a given subtotal.
     */
    public static function portalFee(float $subtotal): float
    {
        if ($subtotal <= 0) {
            return 0.0;
        }

        $pct = (float) config('ticketly.portal_fee_percentage', 10);

        return round($subtotal * ($pct / 100), 2);
    }

    /**
     * Return the pricing breakdown including discount.
     *
     * @return array{subtotal: float, discount: float, gross_total: float, portal_fee: float, service_fee: float, total: float}
     */
    public static function total(float $subtotal, float $discount = 0.0): array
    {
        $portalFee = self::portalFee($subtotal);
        $serviceFee = self::fee($subtotal);
        $grossTotal = round($subtotal + $portalFee + $serviceFee, 2);
        $appliedDiscount = min(max(0.0, round($discount, 2)), $grossTotal);
        $total = max(0.0, round($grossTotal - $appliedDiscount, 2));

        return [
            'subtotal' => round($subtotal, 2),
            'discount' => $appliedDiscount,
            'gross_total' => $grossTotal,
            'portal_fee' => $portalFee,
            'service_fee' => $serviceFee,
            'total' => $total,
        ];
    }

    public static function format(float $amount): string
    {
        return ticketly_money($amount);
    }

    public static function toPence(float $amount): int
    {
        return (int) round($amount * 100);
    }

    public static function fromPence(int $pence): float
    {
        return round($pence / 100, 2);
    }
}
