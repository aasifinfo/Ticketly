<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'organiser_id', 'title', 'slug', 'short_description', 'description',
        'banner', 'category', 'starts_at', 'ends_at',
        'venue_name', 'venue_address', 'city', 'country', 'postcode',
        'parking_info', 'performer_lineup', 'refund_policy',
        'status', 'cancelled_at', 'cancellation_reason',
        'is_featured', 'total_capacity',
        'approval_status', 'approved_at', 'rejected_at', 'rejection_reason',
        'approved_by_admin_id', 'rejected_by_admin_id',
    ];

    protected $casts = [
        'starts_at'        => 'datetime',
        'ends_at'          => 'datetime',
        'cancelled_at'     => 'datetime',
        'approved_at'      => 'datetime',
        'rejected_at'      => 'datetime',
        'is_featured'      => 'boolean',
        'performer_lineup' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($e) {
            if (!$e->slug) {
                $e->slug = static::uniqueSlug($e->title);
            }
        });
    }

    public static function uniqueSlug(string $title): string
    {
        $base = Str::slug($title);
        $slug = $base;
        $i    = 2;
        while (static::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i++;
        }
        return $slug;
    }

    // ── Relationships ─────────────────────────────────────────────
    public function organiser()   { return $this->belongsTo(Organiser::class); }
    public function ticketTiers() { return $this->hasMany(TicketTier::class)->orderBy('sort_order'); }
    public function bookings()    { return $this->hasMany(Booking::class); }
    public function bookingItems()
    {
        return $this->hasManyThrough(BookingItem::class, Booking::class, 'event_id', 'booking_id', 'id', 'id');
    }
    public function reservations(){ return $this->hasMany(Reservation::class); }
    public function promoCodes()  { return $this->hasMany(PromoCode::class); }

    // ── Accessors ─────────────────────────────────────────────────
    public function getBannerUrlAttribute(): ?string
    {
        return $this->banner
            ? \Illuminate\Support\Facades\Storage::url($this->banner)
            : null;
    }

    public function getLowestPriceAttribute(): float
    {
        $min = $this->ticketTiers()->where('is_active', true)->min('price');
        return $min ?? 0;
    }

    public function getFormattedDateAttribute(): string
    {
        return $this->starts_at->format('D, d M Y');
    }

    public function getFormattedTimeAttribute(): string
    {
        return $this->starts_at->format('g:ia') . ' – ' . $this->ends_at->format('g:ia');
    }

    // ── State helpers ─────────────────────────────────────────────
    public function isPublished(): bool  { return $this->status === 'published'; }
    public function isDraft(): bool      { return $this->status === 'draft'; }
    public function isCancelled(): bool  { return $this->status === 'cancelled'; }
    public function isApproved(): bool   { return $this->approval_status === 'approved'; }

    public function getStatusBadgeAttribute(): array
    {
        return match($this->status) {
            'published'   => ['label' => 'Published',   'class' => 'badge--positive'],
            'draft'       => ['label' => 'Draft',       'class' => 'badge--warning'],
            'cancelled'   => ['label' => 'Cancelled',   'class' => 'badge--danger'],
            default       => ['label' => ucfirst($this->status), 'class' => 'badge--neutral'],
        };
    }
}
