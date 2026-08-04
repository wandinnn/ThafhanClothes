<?php

namespace App\Support;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Collection;

/**
 * Keranjang berbasis line-item: setiap kombinasi produk + ukuran + warna
 * menjadi baris terpisah, sehingga pilihan tidak saling menimpa.
 */
class Cart
{
    public const SESSION_KEY = 'cart';

    public const MAX_QUANTITY_PER_LINE = 99;

    /**
     * Kunci baris keranjang. Format: "{productId}|{size}|{color}".
     */
    public static function lineKey(int $productId, string $size, string $color): string
    {
        return $productId.'|'.trim($size).'|'.trim($color);
    }

    /**
     * @return array{product_id: int, size: string, color: string}|null
     */
    public static function parseKey(string $key): ?array
    {
        $parts = explode('|', $key, 3);

        if (count($parts) !== 3) {
            return null;
        }

        $productId = (int) $parts[0];

        if ($productId <= 0 || $parts[1] === '' || $parts[2] === '') {
            return null;
        }

        return [
            'product_id' => $productId,
            'size' => $parts[1],
            'color' => $parts[2],
        ];
    }

    /**
     * Migrasi format lama (`productId => qty` + `cart_meta`) ke line-item.
     */
    public static function migrateLegacy(): void
    {
        $cart = session(self::SESSION_KEY, []);
        $meta = session('cart_meta', []);

        if ($cart === [] || ! self::isLegacy($cart)) {
            return;
        }

        $migrated = [];

        foreach ($cart as $productId => $quantity) {
            $productId = (int) $productId;
            $quantity = (int) $quantity;
            $itemMeta = $meta[$productId] ?? [];
            $size = (string) ($itemMeta['size'] ?? 'M');
            $color = (string) ($itemMeta['color'] ?? 'Hitam');

            if ($productId <= 0 || $quantity <= 0) {
                continue;
            }

            $key = self::lineKey($productId, $size, $color);
            $migrated[$key] = min(($migrated[$key] ?? 0) + $quantity, self::MAX_QUANTITY_PER_LINE);
        }

        session([self::SESSION_KEY => $migrated]);
        session()->forget('cart_meta');
    }

    /**
     * @param  array<string|int, int>  $cart
     */
    private static function isLegacy(array $cart): bool
    {
        foreach (array_keys($cart) as $key) {
            if (! is_string($key) || ! str_contains((string) $key, '|')) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{success: bool, message: string, count: int}
     */
    public static function add(Product $product, int $quantity, string $size, string $color): array
    {
        self::migrateLegacy();

        if ($product->stock <= 0) {
            return self::failure('Stok produk ini sedang habis.');
        }

        $size = trim($size);
        $color = trim($color);

        if ($size === '' || $color === '') {
            return self::failure('Ukuran dan warna wajib dipilih.');
        }

        if ($product->hasVariants() && ! $product->variantFor($size, $color)) {
            return self::failure("Kombinasi {$size} / {$color} tidak tersedia untuk produk ini.");
        }

        $cart = session(self::SESSION_KEY, []);
        $key = self::lineKey($product->id, $size, $color);
        $requested = ($cart[$key] ?? 0) + max(1, $quantity);
        $available = self::availableFor($product, $size, $color, $cart, $cart[$key] ?? 0);
        $maxAllowed = max(0, min($available, self::MAX_QUANTITY_PER_LINE));

        if ($maxAllowed <= 0) {
            return self::failure($product->hasVariants()
                ? "Stok {$product->name} ukuran {$size} warna {$color} sedang habis."
                : "Stok {$product->name} tersisa {$product->stock}.");
        }

        $cart[$key] = min($requested, $maxAllowed);
        session([self::SESSION_KEY => $cart]);
        session()->forget('cart_meta');

        $message = $requested > $maxAllowed
            ? "Stok tersisa {$maxAllowed}, jumlah di keranjang disesuaikan."
            : 'Produk berhasil ditambahkan ke keranjang!';

        return [
            'success' => true,
            'message' => $message,
            'count' => self::totalQuantity($cart),
        ];
    }

    /**
     * @return array{success: false, message: string, count: int}
     */
    private static function failure(string $message): array
    {
        return [
            'success' => false,
            'message' => $message,
            'count' => self::totalQuantity(),
        ];
    }

    /**
     * Sisa stok yang boleh dipesan pada satu baris keranjang. Produk bervarian
     * dibatasi stok varian itu sendiri; produk tanpa varian berbagi satu stok
     * sehingga baris lain dari produk yang sama ikut dikurangkan.
     *
     * @param  array<string, int>  $cart
     */
    private static function availableFor(Product $product, string $size, string $color, array $cart, int $currentLineQty): int
    {
        if ($product->hasVariants()) {
            return $product->stockFor($size, $color);
        }

        $otherLinesQty = self::quantityForProduct($product->id, $cart) - $currentLineQty;

        return (int) $product->stock - $otherLinesQty;
    }

    public static function updateQuantity(string $lineKey, int $quantity): void
    {
        self::migrateLegacy();
        $cart = session(self::SESSION_KEY, []);
        $parsed = self::parseKey($lineKey);

        if (! $parsed || ! isset($cart[$lineKey])) {
            return;
        }

        if ($quantity <= 0) {
            unset($cart[$lineKey]);
            session([self::SESSION_KEY => $cart]);

            return;
        }

        $product = Product::with('variants')->find($parsed['product_id']);

        if (! $product || $product->stock <= 0) {
            unset($cart[$lineKey]);
            session([self::SESSION_KEY => $cart]);

            return;
        }

        $available = self::availableFor($product, $parsed['size'], $parsed['color'], $cart, $cart[$lineKey]);
        $maxAllowed = max(0, min($available, self::MAX_QUANTITY_PER_LINE));

        if ($maxAllowed <= 0) {
            unset($cart[$lineKey]);
        } else {
            $cart[$lineKey] = min($quantity, $maxAllowed);
        }

        session([self::SESSION_KEY => $cart]);
    }

    public static function remove(string $lineKey): void
    {
        self::migrateLegacy();
        $cart = session(self::SESSION_KEY, []);
        unset($cart[$lineKey]);
        session([self::SESSION_KEY => $cart]);
    }

    public static function clear(): void
    {
        session()->forget([self::SESSION_KEY, 'cart_meta']);
    }

    /**
     * @return array<string, int>
     */
    public static function raw(): array
    {
        self::migrateLegacy();

        return session(self::SESSION_KEY, []);
    }

    public static function totalQuantity(?array $cart = null): int
    {
        $cart ??= self::raw();

        return (int) array_sum($cart);
    }

    /**
     * Total kuantitas satu produk di semua line-item (semua ukuran/warna).
     *
     * @param  array<string, int>|null  $cart
     */
    public static function quantityForProduct(int $productId, ?array $cart = null): int
    {
        $cart ??= self::raw();
        $total = 0;

        foreach ($cart as $key => $quantity) {
            $parsed = self::parseKey((string) $key);

            if ($parsed && $parsed['product_id'] === $productId) {
                $total += (int) $quantity;
            }
        }

        return $total;
    }

    /**
     * Baris keranjang siap tampil / checkout, dikelompokkan per produk untuk klaim stok.
     *
     * @return Collection<int, array{key: string, product: Product, variant: ProductVariant|null, quantity: int, size: string, color: string}>
     */
    public static function lines(): Collection
    {
        $cart = self::raw();

        if ($cart === []) {
            return collect();
        }

        $productIds = collect($cart)
            ->keys()
            ->map(fn (string $key) => self::parseKey($key)['product_id'] ?? null)
            ->filter()
            ->unique()
            ->values()
            ->all();

        $products = Product::with(['category', 'variants'])->whereIn('id', $productIds)->get()->keyBy('id');
        $lines = collect();

        foreach ($cart as $key => $quantity) {
            $parsed = self::parseKey((string) $key);
            $quantity = (int) $quantity;

            if (! $parsed || $quantity <= 0) {
                continue;
            }

            $product = $products->get($parsed['product_id']);

            if (! $product) {
                continue;
            }

            $lines->push([
                'key' => (string) $key,
                'product' => $product,
                'variant' => $product->hasVariants()
                    ? $product->variantFor($parsed['size'], $parsed['color'])
                    : null,
                'quantity' => min($quantity, self::MAX_QUANTITY_PER_LINE),
                'size' => $parsed['size'],
                'color' => $parsed['color'],
            ]);
        }

        return $lines;
    }

    /**
     * Kuantitas per product_id untuk klaim stok di checkout.
     *
     * @return array<int, int>
     */
    public static function quantitiesByProduct(): array
    {
        $totals = [];

        foreach (self::lines() as $line) {
            $id = $line['product']->id;
            $totals[$id] = ($totals[$id] ?? 0) + $line['quantity'];
        }

        return $totals;
    }
}
