<?php

use App\Models\Product;
use App\Support\Cart;
use Livewire\Livewire;

test('adding to the cart is refused when the product is out of stock', function () {
    $product = Product::factory()->outOfStock()->create();

    $this->postJson(route('cart.add', $product), [
        'quantity' => 1,
        'size' => 'M',
        'color' => 'Hitam',
    ])->assertStatus(422)->assertJson(['success' => false]);

    expect(session('cart'))->toBeNull();
});

test('adding to the cart never exceeds the available stock', function () {
    $product = Product::factory()->stock(3)->create();

    $this->postJson(route('cart.add', $product), [
        'quantity' => 10,
        'size' => 'M',
        'color' => 'Hitam',
    ])->assertSuccessful()->assertJson(['success' => true, 'count' => 3]);

    expect(session('cart'))->toBe([Cart::lineKey($product->id, 'M', 'Hitam') => 3]);
});

test('different sizes of the same product become separate cart lines', function () {
    $product = Product::factory()->stock(10)->create();

    $this->postJson(route('cart.add', $product), ['quantity' => 1, 'size' => 'L', 'color' => 'Hitam'])->assertSuccessful();
    $this->postJson(route('cart.add', $product), ['quantity' => 2, 'size' => 'XL', 'color' => 'Hitam'])->assertSuccessful();

    expect(session('cart'))->toHaveCount(2)
        ->and(Cart::totalQuantity())->toBe(3);
});

test('the cart badge counts every item, not every product', function () {
    $first = Product::factory()->stock(5)->create();
    $second = Product::factory()->stock(5)->create();
    session(['cart' => [
        Cart::lineKey($first->id, 'M', 'Hitam') => 2,
        Cart::lineKey($second->id, 'L', 'Putih') => 3,
    ]]);

    Livewire::test('cart-badge')->assertSet('count', 5);
});

test('the product detail page caps the quantity added to the cart', function () {
    $product = Product::factory()->stock(2)->create();

    Livewire::test('pages::product-detail', ['product' => $product])
        ->set('selectedSize', 'M')
        ->set('selectedColor', 'Hitam')
        ->set('quantity', 5)
        ->call('addToCart');

    expect(session('cart'))->toBe([Cart::lineKey($product->id, 'M', 'Hitam') => 2]);
});

test('the product detail page refuses an out of stock product', function () {
    $product = Product::factory()->outOfStock()->create();

    Livewire::test('pages::product-detail', ['product' => $product])
        ->set('selectedSize', 'M')
        ->set('selectedColor', 'Hitam')
        ->call('addToCart');

    expect(session('cart', []))->toBe([]);
});

test('the product detail page rejects a size that is not offered', function () {
    $product = Product::factory()->stock(5)->create();

    Livewire::test('pages::product-detail', ['product' => $product])
        ->set('selectedSize', 'XXXXL')
        ->set('selectedColor', 'Hitam')
        ->call('addToCart');

    expect(session('cart', []))->toBe([]);
});

test('the cart page lowers a quantity that is above the stock', function () {
    $product = Product::factory()->stock(2)->create();
    $key = Cart::lineKey($product->id, 'M', 'Hitam');
    session(['cart' => [$key => 1]]);

    Livewire::test('pages::cart')->call('updateQuantity', $key, 9);

    expect(session('cart'))->toBe([$key => 2]);
});

test('removing an item from the cart clears that line only', function () {
    $product = Product::factory()->stock(5)->create();
    $keep = Cart::lineKey($product->id, 'L', 'Hitam');
    $remove = Cart::lineKey($product->id, 'M', 'Hitam');
    session(['cart' => [$keep => 1, $remove => 1]]);

    Livewire::test('pages::cart')->call('removeItem', $remove);

    expect(session('cart'))->toBe([$keep => 1]);
});

test('adding without size and colour is rejected', function () {
    $product = Product::factory()->stock(5)->create();

    $this->postJson(route('cart.add', $product), ['quantity' => 1])
        ->assertStatus(422);

    expect(session('cart'))->toBeNull();
});
