<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\OrderNotifier;
use App\Services\Orders\OrderStockRestorer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CancelExpiredOrdersCommand extends Command
{
    protected $signature = 'shop:cancel-expired-orders';

    protected $description = 'Batalkan pesanan pending_payment yang sudah lewat batas waktu bayar dan kembalikan stok';

    public function handle(OrderStockRestorer $restorer, OrderNotifier $notifier): int
    {
        $orders = Order::query()
            ->with('items')
            ->where('status', 'pending_payment')
            ->whereNotNull('payment_expires_at')
            ->where('payment_expires_at', '<', now())
            ->get();

        $cancelled = 0;

        foreach ($orders as $order) {
            DB::transaction(function () use ($order, $restorer, $notifier, &$cancelled): void {
                $locked = Order::query()
                    ->whereKey($order->id)
                    ->where('status', 'pending_payment')
                    ->lockForUpdate()
                    ->first();

                if (! $locked) {
                    return;
                }

                $previous = $locked->status;
                $restorer->restore($locked);
                $locked->update(['status' => 'cancelled']);
                $notifier->statusUpdated($locked->fresh(['items']), $previous);
                $cancelled++;
            });
        }

        $this->info("Dibatalkan: {$cancelled} pesanan.");

        return self::SUCCESS;
    }
}
