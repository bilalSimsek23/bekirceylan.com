<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
        'exchange_rate' => 'decimal:4',
        'amount_try' => 'decimal:2',
        'booking_amount' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $t) {
            $account = CashAccount::find($t->cash_account_id);
            $t->currency = $account?->currency ?? $t->currency ?? 'TRY';
            if ($t->currency === 'TRY') {
                $t->exchange_rate = 1;
            }
            $t->exchange_rate = (float) ($t->exchange_rate ?: 1);
            $t->amount_try = round((float) $t->amount * (float) $t->exchange_rate, 2);
            $t->created_by ??= auth()->id();

            if ($t->booking_id) {
                $booking = Booking::find($t->booking_id);
                if ($booking) {
                    $t->customer_id ??= $booking->customer_id;
                    $t->tour_id ??= $booking->tour_id;
                    if ($booking->currency === $t->currency) {
                        $t->booking_amount = $t->amount;
                    } elseif (blank($t->booking_amount)) {
                        // Kurdan otomatik karşılık: TL karşılığı / rezervasyon para biriminin kuru
                        $rate = app(\App\Services\ExchangeRateService::class)->rate($booking->currency);
                        $t->booking_amount = $rate ? round($t->amount_try / $rate, 2) : null;
                    }
                }
            } else {
                $t->booking_amount = null;
            }
        });
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(CashAccount::class, 'cash_account_id');
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class)->withTrashed();
    }

    public function tour(): BelongsTo
    {
        return $this->belongsTo(Tour::class)->withTrashed();
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getSignedAmountAttribute(): float
    {
        return ($this->type === 'income' ? 1 : -1) * (float) $this->amount;
    }
}
