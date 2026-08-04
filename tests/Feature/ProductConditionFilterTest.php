<?php

use App\Enums\ProductCondition;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Livewire\Livewire;

test('products can be filtered by new condition', function () {
    $brandNew = Product::factory()->brandNew()->create(['name' => 'Kaos Brand New']);
    Product::factory()->secondLikeNew()->create(['name' => 'Jaket Second Like New']);

    Livewire::test('pages::products')
        ->call('filterByCondition', ProductCondition::New->value)
        ->assertSee('Kaos Brand New')
        ->assertDontSee('Jaket Second Like New')
        ->assertSet('selectedCondition', ProductCondition::New->value);

    expect($brandNew->fresh()->condition)->toBe(ProductCondition::New);
});

test('products can be filtered by second like new condition', function () {
    Product::factory()->brandNew()->create(['name' => 'Sepatu New Only']);
    Product::factory()->secondLikeNew()->create(['name' => 'Hoodie Second Like New']);

    Livewire::test('pages::products')
        ->call('filterByCondition', ProductCondition::SecondLikeNew->value)
        ->assertSee('Hoodie Second Like New')
        ->assertDontSee('Sepatu New Only')
        ->assertSee('Second Like New');
});

test('clearing condition filter shows all products again', function () {
    Product::factory()->brandNew()->create(['name' => 'Item New']);
    Product::factory()->secondLikeNew()->create(['name' => 'Item Preloved']);

    Livewire::test('pages::products')
        ->call('filterByCondition', ProductCondition::New->value)
        ->assertDontSee('Item Preloved')
        ->call('filterByCondition', null)
        ->assertSee('Item New')
        ->assertSee('Item Preloved');
});

test('product detail shows the condition label', function () {
    $product = Product::factory()->secondLikeNew()->create([
        'name' => 'Ransel Second Like New',
        'slug' => 'ransel-second-like-new',
    ]);

    $this->get(route('product.detail', $product))
        ->assertSuccessful()
        ->assertSee('Second Like New');
});

test('admin can save a product condition', function () {
    $this->actingAs(User::factory()->admin()->create());
    $category = Category::factory()->create();

    Livewire::test('pages::admin-products')
        ->set('name', 'Kemeja Condition Check')
        ->set('image_url', 'https://example.test/kemeja.jpg')
        ->set('price', 150000)
        ->set('stock', 8)
        ->set('description', 'Kemeja preloved berkualitas untuk tes kondisi.')
        ->set('category_id', (string) $category->id)
        ->set('condition', ProductCondition::New->value)
        ->call('save');

    expect(Product::sole()->condition)->toBe(ProductCondition::New);
});
