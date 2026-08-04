<?php

use App\Models\Product;
use App\Models\Wishlist;
use Livewire\Livewire;

test('a product can be added to and removed from the wishlist', function () {
    $product = Product::factory()->create();

    Livewire::test('pages::product-detail', ['product' => $product])
        ->call('toggleWishlist')
        ->assertSet('inWishlist', true);

    expect(Wishlist::countForSession())->toBe(1);

    Livewire::test('pages::product-detail', ['product' => $product])
        ->call('toggleWishlist')
        ->assertSet('inWishlist', false);

    expect(Wishlist::countForSession())->toBe(0);
});

test('the wishlist page lists saved products', function () {
    $product = Product::factory()->create(['name' => 'Jaket Bomber Hitam']);

    Livewire::test('pages::product-detail', ['product' => $product])
        ->call('toggleWishlist');

    Livewire::test('pages::wishlist')
        ->assertSee('Jaket Bomber Hitam');
});

test('wishlist can be toggled via ajax endpoint', function () {
    $product = Product::factory()->create();

    $this->postJson(route('wishlist.toggle', $product))
        ->assertSuccessful()
        ->assertJsonStructure(['success', 'added', 'count', 'message'])
        ->assertJson([
            'success' => true,
            'added' => true,
            'count' => 1,
        ]);

    $this->assertDatabaseHas('wishlists', [
        'product_id' => $product->id,
    ]);
});
