<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference', 'event_id', 'reservation_id', 'customer_id', 'promo_code_id',
        'customer_name', 'customer_email', 'customer_phone',
        'subtotal', 'discount_amount', 'portal_fee', 'service_fee', 'total', 'currency',
        'stripe_session_id', 'stripe_payment_intent_id', 'stripe_charge_id',
        'status', 'refund_amount', 'refunded_at', 'refund_reason',
        'confirmation_sent_at', 'reminders_sent',
    ];

    protected $casts = [
        'subtotal'             => 'decimal:2',
        'discount_amount'      => 'decimal:2',
        'portal_fee'           => 'decimal:2',
        'service_fee'          => 'decimal:2',
        'total'                => 'decimal:2',
        'refund_amount'        => 'decimal:2',
        'refunded_at'          => 'datetime',
        'confirmation_sent_at' => 'datetime',
        'reminders_sent'       => 'array',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($b) {
            $b->reference = $b->reference ?? strtoupper('TKT-' . Str::random(8));
            $b->currency  = $b->currency ?? ticketly_currency();
        });
    }

    public function event()      { return $this->belongsTo(Event::class); }
    public function items()      { return $this->hasMany(BookingItem::class); }
    public function reservation(){ return $this->belongsTo(Reservation::class); }
    public function promoCode()  { return $this->belongsTo(PromoCode::class); }
    public function customer()   { return $this->belongsTo(Customer::class); }

    public function isPaid(): bool     { return $this->status === 'paid'; }
    public function isRefunded(): bool { return in_array($this->status, ['refunded', 'partially_refunded']); }
    public function isFullyRefunded(): bool { return $this->status === 'refunded'; }
    public function isPartiallyRefunded(): bool { return $this->status === 'partially_refunded'; }

    public function getStatusBadgeAttribute(): array
    {
        return match($this->status) {
            'paid'               => ['label' => 'Paid',     'class' => 'badge--positive'],
            'refunded'           => ['label' => 'Refunded', 'class' => 'badge--accent'],
            'partially_refunded' => ['label' => 'Part. Refund', 'class' => 'badge--accent'],
            'cancelled'          => ['label' => 'Cancelled','class' => 'badge--danger'],
            'failed'             => ['label' => 'Failed',   'class' => 'badge--neutral'],
            default              => ['label' => ucfirst($this->status), 'class' => 'badge--neutral'],
        };
    }
}
