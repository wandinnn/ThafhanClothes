<?php

namespace App\Observers;

use App\Models\Product;
use App\Services\MeiliSearchService;

class ProductObserver
{
    private MeiliSearchService $meili;

    public function __construct()
    {
        $this->meili = new MeiliSearchService;
    }

    public function saved(Product $product): void
    {
        $this->meili->indexProduct($product);
    }

    public function deleted(Product $product): void
    {
        $this->meili->deleteProduct($product->id);
    }
}
