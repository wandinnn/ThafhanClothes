<?php

namespace App\Services\Shipping;

use App\Contracts\ShippingRateProvider;
use App\Data\ShippingQuote;

class StaticCityRateProvider implements ShippingRateProvider
{
    public function cities(): array
    {
        /** @var array<string, int> $rates */
        $rates = config('shop.shipping.city_rates', []);

        return $rates;
    }

    public function quotesFor(string $city, int $weightGrams = 500): array
    {
        $cost = $this->cities()[$city] ?? null;

        if ($cost === null) {
            return [];
        }

        return [
            new ShippingQuote(
                code: 'static-reg',
                courier: 'Thafhan Kurir',
                service: 'Gacor Mantap',
                cost: $cost,
                etd: $cost === 0 ? '1 hari' : '2-4 hari',
            ),
        ];
    }

    public function quoteByCode(string $city, string $serviceCode, int $weightGrams = 500): ?ShippingQuote
    {
        foreach ($this->quotesFor($city, $weightGrams) as $quote) {
            if ($quote->code === $serviceCode) {
                return $quote;
            }
        }

        return $this->quotesFor($city, $weightGrams)[0] ?? null;
    }
}
