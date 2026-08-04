<?php

namespace App\Services\Payment;

use App\Models\Order;

class OrderPaymentSettler
{
    public function settle(
        Order $order,
        string $gateway,
        ?string $transactionId = null,
        ?string $paymentMethod = null,
    ): Order {
        $order->forceFill([
            'status' => 'confirmed',
            'payment_gateway' => $gateway,
            'payment_method' => $paymentMethod ?: ($order->payment_method ?: $gateway),
            'payment_transaction_id' => $transactionId ?: $order->payment_transaction_id,
            'paid_at' => now(),
        ])->save();

        return $order->fresh(['items']) ?? $order;
    }
}
