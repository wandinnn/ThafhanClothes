<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductVariant>
 */
class ProductVariantFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'size' => fake()->randomElement(['S', 'M', 'L', 'XL']),
            'color' => fake()->randomElement(['Hitam', 'Putih', 'Navy']),
            'sku' => null,
            'stock' => 10,
        ];
    }

    public function stock(int $stock): static
    {
        return $this->state(['stock' => $stock]);
    }

    public function combination(string $size, string $color): static
    {
        return $this->state(['size' => $size, 'color' => $color]);
    }
}
