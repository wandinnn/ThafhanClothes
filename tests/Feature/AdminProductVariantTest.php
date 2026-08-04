<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->admin()->create());
    $this->category = Category::factory()->create(['name' => 'Kemeja', 'slug' => 'kemeja-t-shirt']);
});

function productFormWithVariants(int $categoryId, array $variantRows): Testable
{
    return Livewire::test('pages::admin-products')
        ->set('name', 'Kemeja Flanel Kotak')
        ->set('image_url', 'https://example.test/kemeja.jpg')
        ->set('price', 150000)
        ->set('stock', 0)
        ->set('description', 'Kemeja flanel bahan katun yang nyaman dipakai seharian.')
        ->set('category_id', (string) $categoryId)
        ->set('variantRows', $variantRows);
}

test('saving variants stores them and totals the product stock', function () {
    productFormWithVariants($this->category->id, [
        ['size' => 'M', 'color' => 'Hitam', 'stock' => 4, 'sku' => 'TC-M-HIT'],
        ['size' => 'L', 'color' => 'Hitam', 'stock' => 6, 'sku' => ''],
    ])->call('save');

    $product = Product::sole();

    expect($product->variants)->toHaveCount(2)
        ->and($product->stock)->toBe(10)
        ->and($product->variantFor('M', 'Hitam')->sku)->toBe('TC-M-HIT')
        ->and($product->variantFor('L', 'Hitam')->sku)->toBeNull();
});

test('a duplicated size and colour combination is rejected', function () {
    productFormWithVariants($this->category->id, [
        ['size' => 'M', 'color' => 'Hitam', 'stock' => 4, 'sku' => ''],
        ['size' => 'M', 'color' => 'Hitam', 'stock' => 2, 'sku' => ''],
    ])->call('save')->assertHasErrors('variantRows');

    expect(Product::count())->toBe(0);
});

test('a negative variant stock is rejected', function () {
    productFormWithVariants($this->category->id, [
        ['size' => 'M', 'color' => 'Hitam', 'stock' => -1, 'sku' => ''],
    ])->call('save')->assertHasErrors('variantRows.0.stock');

    expect(Product::count())->toBe(0);
});

test('editing a product removes the variants that were deleted from the form', function () {
    $product = Product::factory()
        ->withVariants(['M', 'L'], ['Hitam'], 5)
        ->create(['category_id' => $this->category->id]);

    Livewire::test('pages::admin-products')
        ->call('edit', $product->slug)
        ->assertCount('variantRows', 2)
        ->call('removeVariantRow', 1)
        ->call('save');

    expect($product->fresh()->variants)->toHaveCount(1)
        ->and($product->fresh()->stock)->toBe(5);
});

test('clearing every variant lets the product fall back to a single stock', function () {
    $product = Product::factory()
        ->withVariants(['M'], ['Hitam'], 5)
        ->create(['category_id' => $this->category->id]);

    Livewire::test('pages::admin-products')
        ->call('edit', $product->slug)
        ->set('variantRows', [])
        ->set('stock', 20)
        ->call('save');

    expect($product->fresh()->variants)->toHaveCount(0)
        ->and($product->fresh()->stock)->toBe(20)
        ->and($product->fresh()->hasVariants())->toBeFalse();
});

test('filling every size for a colour creates one row per size', function () {
    Livewire::test('pages::admin-products')
        ->set('category_id', (string) $this->category->id)
        ->set('variantColorToAdd', 'Navy')
        ->call('addSizesForColor')
        ->assertCount('variantRows', 6)
        ->assertSet('variantColorToAdd', '');
});

test('filling sizes without choosing a colour shows an error', function () {
    Livewire::test('pages::admin-products')
        ->set('category_id', (string) $this->category->id)
        ->call('addSizesForColor')
        ->assertHasErrors('variantColorToAdd')
        ->assertCount('variantRows', 0);
});
