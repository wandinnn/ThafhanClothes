<?php

namespace App\Services\Payment;

use App\Contracts\PaymentGateway;
use App\Data\PaymentSession;
use App\Models\Order;
use Illuminate\Support\Str;

class FakePaymentGateway implements PaymentGateway
{
    public function __construct(private OrderPaymentSettler $settler) {}

    public function driver(): string
    {
        return 'fake';
    }

    public function supportsProofUpload(): bool
    {
        return true;
    }

    public function supportsInstantPay(): bool
    {
        return true;
    }

    public function createSession(Order $order): PaymentSession
    {
        $transactionId = 'FAKE-'.Str::upper(Str::random(10));

        $order->forceFill([
            'payment_gateway' => 'fake',
            'payment_transaction_id' => $transactionId,
        ])->save();

        return new PaymentSession(
            mode: 'simulate',
            transactionId: $transactionId,
            meta: ['message' => 'Simulasi pembayaran lokal (tanpa Midtrans).'],
        );
    }

    public function settle(Order $order, ?string $transactionId = null): Order
    {
        return $this->settler->settle(
            $order,
            'fake',
            $transactionId ?: ($order->payment_transaction_id ?: 'FAKE-'.Str::upper(Str::random(10))),
            'fake_gateway',
        );
    }
}
