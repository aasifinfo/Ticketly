<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class PromoCode extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'organiser_id', 'event_id', 'code', 'type', 'value',
        'max_discount', 'max_uses', 'used_count', 'is_active', 'expires_at',
    ];

    protected $casts = [
        'value'       => 'decimal:2',
        'max_discount'=> 'decimal:2',
        'is_active'   => 'boolean',
        'expires_at'  => 'datetime',
    ];

    public function organiser() { return $this->belongsTo(Organiser::class); }
    public function event()     { return $this->belongsTo(Event::class); }
    public function bookings()  { return $this->hasMany(Booking::class); }

    public function isValid(): bool
    {
        if (!$this->is_active) return false;
        if ($this->expires_at && $this->expires_at->isPast()) return false;
        if ($this->max_uses && $this->used_count >= $this->max_uses) return false;
        return true;
    }

    public function calculateDiscount(float $subtotal): float
    {
        if ($this->type === 'percentage') {
            $discount = round($subtotal * ($this->value / 100), 2);
            if ($this->max_discount) {
                $discount = min($discount, (float) $this->max_discount);
            }
            return $discount;
        }
        // Fixed amount
        return min((float) $this->value, $subtotal);
    }
}