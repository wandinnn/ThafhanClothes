<?php

namespace Database\Factories;

use App\Models\Coupon;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Coupon>
 */
class CouponFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => Str::upper(fake()->unique()->bothify('KODE####')),
            'type' => 'fixed',
            'value' => 10000,
            'min_order' => 0,
            'max_uses' => null,
            'used_count' => 0,
            'is_active' => true,
            'expires_at' => null,
            'description' => 'Kupon uji coba',
        ];
    }

    public function percent(int $value): static
    {
        return $this->state(['type' => 'percent', 'value' => $value]);
    }

    public function expired(): static
    {
        return $this->state(['expires_at' => now()->subDay()]);
    }
}
