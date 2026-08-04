<?php

namespace App\Services\Invoice;

use App\Models\Order;
use App\Support\ShopSettings;
use Illuminate\Support\Carbon;

class OrderInvoicePresenter
{
    /**
     * @return array{
     *     order: Order,
     *     shopName: string,
     *     bank: array{name: string, account: string, holder: string},
     *     issuedAt: Carbon
     * }
     */
    public function present(Order $order): array
    {
        $order->loadMissing('items');

        return [
            'order' => $order,
            'shopName' => (string) config('app.name', 'ThafhanClothes'),
            'bank' => ShopSettings::bank(),
            'issuedAt' => now(),
        ];
    }
}
