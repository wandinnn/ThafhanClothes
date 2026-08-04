<?php

namespace App\Support;

use App\Models\Product;

/**
 * Daftar ukuran dan warna yang ditawarkan toko.
 * Dipakai bersama oleh halaman detail produk dan modal add-to-cart.
 */
class ProductOptions
{
    /**
     * @return list<string>
     */
    public static function colors(): array
    {
        return ['Hitam', 'Putih', 'Coklat', 'Merah', 'Navy', 'Abu-abu'];
    }

    /**
     * @return list<array{name: string, hex: string}>
     */
    public static function colorsWithHex(): array
    {
        return [
            ['name' => 'Hitam', 'hex' => '#1a1a1a'],
            ['name' => 'Putih', 'hex' => '#f5f5f5'],
            ['name' => 'Coklat', 'hex' => '#8B4513'],
            ['name' => 'Merah', 'hex' => '#c0392b'],
            ['name' => 'Navy', 'hex' => '#1e3a5f'],
            ['name' => 'Abu-abu', 'hex' => '#7f8c8d'],
        ];
    }

    /**
     * @return list<string>
     */
    public static function sizesFor(?Product $product): array
    {
        if (! $product) {
            return ['XS', 'S', 'M', 'L', 'XL', 'XXL'];
        }

        $slug = $product->category?->slug ?? '';

        return match (true) {
            str_contains($slug, 'celana') => ['28', '30', '32', '34', '36'],
            str_contains($slug, 'sepatu') => ['38', '39', '40', '41', '42'],
            str_contains($slug, 'aksesoris') => self::accessorySizes($product),
            default => ['XS', 'S', 'M', 'L', 'XL', 'XXL'],
        };
    }

    /**
     * @return list<string>
     */
    private static function accessorySizes(Product $product): array
    {
        $name = strtolower($product->name);

        return match (true) {
            str_contains($name, 'jam') || str_contains($name, 'watch') => ['40mm', '44mm'],
            default => ['One Size'],
        };
    }
}
