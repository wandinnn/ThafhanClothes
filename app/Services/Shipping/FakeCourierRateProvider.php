<?php

namespace App\Services\Shipping;

use App\Contracts\ShippingRateProvider;
use App\Data\ShippingQuote;

/**
 * Driver lokal yang meniru respons ongkir multi-kurir (tanpa API key).
 * Tarif dasar tetap memakai daftar kota di config, lalu dipecah jadi opsi kurir.
 */
class FakeCourierRateProvider implements ShippingRateProvider
{
    public function __construct(private StaticCityRateProvider $static) {}

    public function cities(): array
    {
        return $this->static->cities();
    }

    public function quotesFor(string $city, int $weightGrams = 500): array
    {
        $base = $this->cities()[$city] ?? null;

        if ($base === null) {
            return [];
        }

        if ($base === 0) {
            return [
                new ShippingQuote('jne-reg', 'JNE', 'REG', 0, '1 hari'),
                new ShippingQuote('sicepat-reg', 'SiCepat', 'REG', 0, '1 hari'),
            ];
        }

        $weightFactor = max(1, (int) ceil($weightGrams / 1000));

        return [
            new ShippingQuote('jne-reg', 'JNE', 'REG', $base * $weightFactor, '2-3 hari'),
            new ShippingQuote('jnt-eco', 'J&T Express', 'ECO', (int) round($base * 0.9) * $weightFactor, '3-4 hari'),
            new ShippingQuote('sicepat-reg', 'SiCepat', 'REG', (int) round($base * 1.05) * $weightFactor, '1-2 hari'),
        ];
    }

    public function quoteByCode(string $city, string $serviceCode, int $weightGrams = 500): ?ShippingQuote
    {
        foreach ($this->quotesFor($city, $weightGrams) as $quote) {
            if ($quote->code === $serviceCode) {
                return $quote;
            }
        }

        return null;
    }
}
