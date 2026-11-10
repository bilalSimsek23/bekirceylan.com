<?php

namespace App\Models;

use App\Support\Options;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Tour extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'itinerary' => 'array',
        'included' => 'array',
        'excluded' => 'array',
        'hotels' => 'array',
        'room_prices' => 'array',
        'gallery' => 'array',
        'start_date' => 'date',
        'end_date' => 'date',
        'price' => 'decimal:2',
        'old_price' => 'decimal:2',
        'is_featured' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $tour) {
            if (blank($tour->slug)) {
                $base = Str::slug($tour->title);
                $slug = $base;
                $i = 2;
                while (static::withTrashed()->where('slug', $slug)->whereKeyNot($tour->getKey())->exists()) {
                    $slug = $base.'-'.$i++;
                }
                $tour->slug = $slug;
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(TourCategory::class, 'tour_category_id');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function activeBookings(): HasMany
    {
        return $this->bookings()->where('status', '!=', 'cancelled');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderByRaw('start_date IS NULL')->orderBy('start_date');
    }

    public function getOccupiedAttribute(): int
    {
        return $this->active_bookings_count ?? $this->activeBookings()->count();
    }

    public function getRemainingSeatsAttribute(): ?int
    {
        return $this->capacity === null ? null : max(0, $this->capacity - $this->occupied);
    }

    public function getCoverUrlAttribute(): string
    {
        if (blank($this->cover_image)) {
            return asset('images/placeholder-tour.svg');
        }

        return Str::startsWith($this->cover_image, ['http://', 'https://', '/'])
            ? $this->cover_image
            : Storage::disk('public')->url($this->cover_image);
    }

    public function getGalleryUrlsAttribute(): array
    {
        return collect($this->gallery ?? [])
            ->map(fn ($path) => Str::startsWith($path, ['http', '/']) ? $path : Storage::disk('public')->url($path))
            ->all();
    }

    public function getDateRangeAttribute(): ?string
    {
        if (! $this->start_date) {
            return null;
        }
        $start = $this->start_date->translatedFormat('d M Y');

        return $this->end_date ? $start.' – '.$this->end_date->translatedFormat('d M Y') : $start;
    }

    public function formattedPrice(?float $value = null): ?string
    {
        $value ??= $this->price;

        return $value === null ? null : Options::money($value, $this->currency, 0);
    }

    public function getLabelAttribute(): string
    {
        return trim($this->title.($this->start_date ? ' ('.$this->start_date->format('d.m.Y').')' : ''));
    }
}
