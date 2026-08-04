<?php

namespace App\Data;

final readonly class PaymentSession
{
    /**
     * @param  'manual'|'simulate'|'redirect'|'snap'  $mode
     * @param  array<string, mixed>  $meta
     */
    public function __construct(
        public string $mode,
        public ?string $redirectUrl = null,
        public ?string $transactionId = null,
        public array $meta = [],
    ) {}
}
