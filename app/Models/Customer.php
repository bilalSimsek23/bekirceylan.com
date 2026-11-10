<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'birth_date' => 'date',
        'passport_issue_date' => 'date',
        'passport_expiry_date' => 'date',
        'tags' => 'array',
        'needs_wheelchair' => 'boolean',
        'kvkk_consent' => 'boolean',
        'sms_consent' => 'boolean',
    ];

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(CustomerDocument::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function inquiries(): HasMany
    {
        return $this->hasMany(Inquiry::class);
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    public function getAgeAttribute(): ?int
    {
        return $this->birth_date?->age;
    }

    /** Pasaport, belirtilen tarihten itibaren 6 aydan az geçerliyse true. */
    public function passportExpiresSoon(?\DateTimeInterface $reference = null): bool
    {
        if (! $this->passport_expiry_date) {
            return false;
        }

        return $this->passport_expiry_date->lt(now()->parse($reference ?? now())->addMonths(6));
    }

    public function scopePassportExpiring(Builder $query): Builder
    {
        return $query->whereNotNull('passport_expiry_date')->where('passport_expiry_date', '<', now()->addMonths(6));
    }

    /** TC Kimlik No algoritma doğrulaması */
    public static function isValidTcNo(?string $tc): bool
    {
        if (! $tc || ! preg_match('/^[1-9][0-9]{10}$/', $tc)) {
            return false;
        }
        $d = array_map('intval', str_split($tc));
        $odd = $d[0] + $d[2] + $d[4] + $d[6] + $d[8];
        $even = $d[1] + $d[3] + $d[5] + $d[7];
        if ((($odd * 7) - $even) % 10 !== $d[9]) {
            return false;
        }

        return array_sum(array_slice($d, 0, 10)) % 10 === $d[10];
    }
}
