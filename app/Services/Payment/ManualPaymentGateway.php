<?php

namespace App\Services\Payment;

use App\Contracts\PaymentGateway;
use App\Data\PaymentSession;
use App\Models\Order;
use App\Support\ShopSettings;
use RuntimeException;

class ManualPaymentGateway implements PaymentGateway
{
    public function driver(): string
    {
        return 'manual';
    }

    public function supportsProofUpload(): bool
    {
        return true;
    }

    public function supportsInstantPay(): bool
    {
        return false;
    }

    public function createSession(Order $order): PaymentSession
    {
        return new PaymentSession(mode: 'manual', meta: [
            'bank' => ShopSettings::bank(),
        ]);
    }

    public function settle(Order $order, ?string $transactionId = null): Order
    {
        throw new RuntimeException('Pembayaran manual diselesaikan lewat unggah bukti dan konfirmasi admin.');
    }
}
