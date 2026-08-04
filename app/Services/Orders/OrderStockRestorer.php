<?php

namespace App\Services\Orders;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;

class OrderStockRestorer
{
    public function restore(Order $order): void
    {
        $order->loadMissing('items');

        DB::transaction(function () use ($order): void {
            foreach ($order->items as $item) {
                if ($item->product_variant_id) {
                    ProductVariant::whereKey($item->product_variant_id)
                        ->increment('stock', (int) $item->quantity);

                    $variant = ProductVariant::find($item->product_variant_id);
                    $variant?->product?->syncStockFromVariants();

                    continue;
                }

                if ($item->product_id) {
                    Product::whereKey($item->product_id)
                        ->increment('stock', (int) $item->quantity);
                }
            }
        });
    }
}
