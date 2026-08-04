<?php

namespace App\Contracts;

use App\Data\PaymentSession;
use App\Models\Order;

interface PaymentGateway
{
    public function driver(): string;

    public function supportsProofUpload(): bool;

    public function supportsInstantPay(): bool;

    public function createSession(Order $order): PaymentSession;

    public function settle(Order $order, ?string $transactionId = null): Order;
}
