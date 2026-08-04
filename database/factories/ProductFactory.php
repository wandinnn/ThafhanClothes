<?php

namespace Database\Factories;

use App\Enums\ProductCondition;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);

        return [
            'category_id' => Category::factory(),
            'name' => Str::title($name),
            'slug' => Str::slug($name),
            'description' => fake()->sentence(12),
            'price' => fake()->numberBetween(50, 500) * 1000,
            'original_price' => null,
            'image_url' => 'https://example.test/'.Str::slug($name).'.jpg',
            'stock' => 25,
            'condition' => fake()->randomElement(ProductCondition::cases()),
            'is_best_seller' => false,
            'is_new_arrival' => false,
            'is_flash_sale' => false,
        ];
    }

    public function brandNew(): static
    {
        return $this->state(['condition' => ProductCondition::New]);
    }

    public function secondLikeNew(): static
    {
        return $this->state(['condition' => ProductCondition::SecondLikeNew]);
    }

    public function outOfStock(): static
    {
        return $this->state(['stock' => 0]);
    }

    public function stock(int $stock): static
    {
        return $this->state(['stock' => $stock]);
    }

    /**
     * Buat varian untuk tiap kombinasi ukuran dan warna, lalu selaraskan total stok.
     *
     * @param  list<string>  $sizes
     * @param  list<string>  $colors
     */
    public function withVariants(array $sizes = ['S', 'M', 'L'], array $colors = ['Hitam', 'Putih'], int $stockPerVariant = 5): static
    {
        return $this->afterCreating(function (Product $product) use ($sizes, $colors, $stockPerVariant): void {
            foreach ($sizes as $size) {
                foreach ($colors as $color) {
                    $product->variants()->create([
                        'size' => $size,
                        'color' => $color,
                        'stock' => $stockPerVariant,
                    ]);
                }
            }

            $product->syncStockFromVariants();
        });
    }
}
