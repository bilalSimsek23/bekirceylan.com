<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Booking extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'price' => 'decimal:2',
        'discount' => 'decimal:2',
        'extra_charges' => 'decimal:2',
        'registered_at' => 'date',
        'cancelled_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $booking) {
            $booking->registered_at ??= now();
            $booking->created_by ??= auth()->id();
            if (blank($booking->booking_no)) {
                $prefix = 'BC'.now()->format('y');
                $last = static::where('booking_no', 'like', $prefix.'%')->max('booking_no');
                $next = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;
                $booking->booking_no = $prefix.str_pad((string) $next, 5, '0', STR_PAD_LEFT);
            }
        });

        static::saving(function (self $booking) {
            if ($booking->isDirty('status') && $booking->status === 'cancelled') {
                $booking->cancelled_at ??= now();
            }
        });
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class)->withTrashed();
    }

    public function tour(): BelongsTo
    {
        return $this->belongsTo(Tour::class)->withTrashed();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function getTotalAttribute(): float
    {
        return round((float) $this->price + (float) $this->extra_charges - (float) $this->discount, 2);
    }

    /** Rezervasyon para biriminde net ödenen (tahsilat - iade) */
    public function getPaidAttribute(): float
    {
        $rows = $this->relationLoaded('transactions') ? $this->transactions : $this->transactions()->get();

        return round($rows->sum(fn (Transaction $t) => ($t->type === 'income' ? 1 : -1) * (float) $t->booking_amount), 2);
    }

    public function getBalanceAttribute(): float
    {
        return round($this->total - $this->paid, 2);
    }
}
