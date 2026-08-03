<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'description',
        'price',
        'original_price',
        'image_url',
        'stock', // ditambahkan
        'is_best_seller',
        'is_new_arrival',
        'is_flash_sale',
    ];

    protected function casts(): array
    {
        return [
            'is_best_seller' => 'boolean',
            'is_new_arrival' => 'boolean',
            'is_flash_sale' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function getFormattedPriceAttribute(): string
    {
        return 'Rp'.number_format($this->price, 0, ',', '.');
    }

    public function getFormattedOriginalPriceAttribute(): ?string
    {
        if (! $this->original_price) {
            return null;
        }

        return 'Rp'.number_format($this->original_price, 0, ',', '.');
    }

    public function getDiscountPercentAttribute(): ?int
    {
        if (! $this->original_price || $this->original_price <= $this->price) {
            return null;
        }

        return (int) round(
            (($this->original_price - $this->price) / $this->original_price) * 100
        );
    }

    public function getAverageRatingAttribute(): float
    {
        return round(
            (float) ($this->reviews()->avg('rating') ?? 0),
            1
        );
    }

    public function getReviewsCountAttribute(): int
    {
        return $this->reviews()->count();
    }

    /**
     * Cek apakah stok habis
     */
    public function getIsOutOfStockAttribute(): bool
    {
        return $this->stock <= 0;
    }

    /**
     * Format tampilan stok
     */
    public function getFormattedStockAttribute(): string
    {
        if ($this->stock <= 0) {
            return 'Stok Habis';
        }

        if ($this->stock <= 5) {
            return 'Stok: '.$this->stock.' (terbatas!)';
        }

        return 'Stok: '.$this->stock;
    }
}
