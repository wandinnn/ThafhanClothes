<?php

namespace App\Observers;

use App\Models\Product;
use App\Services\MeiliSearchService;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProductObserver
{
    public function __construct(private MeiliSearchService $meili) {}

    public function saved(Product $product): void
    {
        $this->silently(
            fn () => $this->meili->indexProduct($product),
            'Gagal mengindeks produk ke Meilisearch',
            $product->id,
        );
    }

    public function deleted(Product $product): void
    {
        $this->silently(
            fn () => $this->meili->deleteProduct($product->id),
            'Gagal menghapus produk dari Meilisearch',
            $product->id,
        );
    }

    /**
     * Indexing hanya pelengkap pencarian, jadi kegagalannya tidak boleh
     * menggagalkan penyimpanan produk di database.
     */
    private function silently(callable $callback, string $message, int $productId): void
    {
        try {
            $callback();
        } catch (Throwable $e) {
            Log::warning($message, [
                'product_id' => $productId,
                'exception' => $e->getMessage(),
            ]);
        }
    }
}
