<?php

namespace App\Models;

use App\Support\ProductOptions;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

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

    public const MAX_GALLERY_IMAGES = 5;

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order')->orderBy('id');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    /**
     * Daftar URL gallery: gambar utama + foto tambahan, maksimal 5.
     *
     * @return list<string>
     */
    public function galleryUrls(): array
    {
        $urls = collect([$this->image_url])
            ->merge($this->relationLoaded('images') ? $this->images->pluck('url') : $this->images()->pluck('url'))
            ->filter(fn (?string $url): bool => filled($url))
            ->unique()
            ->take(self::MAX_GALLERY_IMAGES)
            ->values()
            ->all();

        return $urls;
    }

    public function seoDescription(): string
    {
        $text = trim(strip_tags((string) $this->description));

        if ($text === '') {
            return $this->name.' — koleksi fashion ThafhanClothes.';
        }

        return Str::limit($text, 155);
    }

    /**
     * Produk tanpa varian memakai stok tunggal di `products.stock`, sehingga
     * katalog lama tetap bisa dijual selama admin belum mengisi varian.
     */
    public function hasVariants(): bool
    {
        return $this->relationLoaded('variants')
            ? $this->variants->isNotEmpty()
            : $this->variants()->exists();
    }

    public function variantFor(string $size, string $color): ?ProductVariant
    {
        $size = trim($size);
        $color = trim($color);

        if ($this->relationLoaded('variants')) {
            return $this->variants->first(
                fn (ProductVariant $variant): bool => $variant->size === $size && $variant->color === $color
            );
        }

        return $this->variants()->where('size', $size)->where('color', $color)->first();
    }

    /**
     * Stok yang benar-benar bisa dijual untuk satu kombinasi ukuran + warna.
     */
    public function stockFor(string $size, string $color): int
    {
        if (! $this->hasVariants()) {
            return (int) $this->stock;
        }

        return (int) ($this->variantFor($size, $color)?->stock ?? 0);
    }

    /**
     * @return list<string>
     */
    public function availableSizes(): array
    {
        if (! $this->hasVariants()) {
            return ProductOptions::sizesFor($this);
        }

        $order = ProductOptions::sizesFor($this);

        return $this->loadedVariants()
            ->pluck('size')
            ->unique()
            ->sortBy(fn (string $size): int => ($index = array_search($size, $order, true)) === false ? PHP_INT_MAX : $index)
            ->values()
            ->all();
    }

    /**
     * @return list<string>
     */
    public function availableColors(): array
    {
        if (! $this->hasVariants()) {
            return ProductOptions::colors();
        }

        $order = ProductOptions::colors();

        return $this->loadedVariants()
            ->pluck('color')
            ->unique()
            ->sortBy(fn (string $color): int => ($index = array_search($color, $order, true)) === false ? PHP_INT_MAX : $index)
            ->values()
            ->all();
    }

    /**
     * Peta stok per kombinasi, format `"{size}|{color}" => stok`.
     *
     * @return array<string, int>
     */
    public function variantStockMap(): array
    {
        if (! $this->hasVariants()) {
            return [];
        }

        return $this->loadedVariants()
            ->mapWithKeys(fn (ProductVariant $variant): array => [
                $variant->size.'|'.$variant->color => (int) $variant->stock,
            ])
            ->all();
    }

    /**
     * Selaraskan cache `products.stock` dengan jumlah stok seluruh varian.
     */
    public function syncStockFromVariants(): void
    {
        if (! $this->variants()->exists()) {
            return;
        }

        $total = (int) $this->variants()->sum('stock');

        if ((int) $this->stock !== $total) {
            $this->forceFill(['stock' => $total])->save();
        }

        $this->unsetRelation('variants');
    }

    /**
     * @return Collection<int, ProductVariant>
     */
    private function loadedVariants()
    {
        $this->loadMissing('variants');

        return $this->variants;
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

    /**
     * Pakai hasil `withAvg()` atau relasi yang sudah dimuat lebih dulu agar
     * listing produk tidak memicu query tambahan per baris.
     */
    public function getAverageRatingAttribute(): float
    {
        if (array_key_exists('reviews_avg_rating', $this->attributes)) {
            return round((float) $this->attributes['reviews_avg_rating'], 1);
        }

        if ($this->relationLoaded('reviews')) {
            return round((float) $this->reviews->avg('rating'), 1);
        }

        return round((float) ($this->reviews()->avg('rating') ?? 0), 1);
    }

    public function getReviewsCountAttribute(): int
    {
        if (array_key_exists('reviews_count', $this->attributes)) {
            return (int) $this->attributes['reviews_count'];
        }

        if ($this->relationLoaded('reviews')) {
            return $this->reviews->count();
        }

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
