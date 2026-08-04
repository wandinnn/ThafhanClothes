<?php

namespace App\Data;

final readonly class ShippingQuote
{
    public function __construct(
        public string $code,
        public string $courier,
        public string $service,
        public int $cost,
        public string $etd,
    ) {}

    public function label(): string
    {
        $costLabel = $this->cost === 0
            ? 'Gratis'
            : 'Rp'.number_format($this->cost, 0, ',', '.');

        return "{$this->courier} {$this->service} — {$costLabel} ({$this->etd})";
    }
}
