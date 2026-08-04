<?php

namespace App\Contracts;

use App\Data\ShippingQuote;

interface ShippingRateProvider
{
    /**
     * @return array<string, int> Kota => tarif dasar (Rp)
     */
    public function cities(): array;

    /**
     * @return list<ShippingQuote>
     */
    public function quotesFor(string $city, int $weightGrams = 500): array;

    public function quoteByCode(string $city, string $serviceCode, int $weightGrams = 500): ?ShippingQuote;
}
