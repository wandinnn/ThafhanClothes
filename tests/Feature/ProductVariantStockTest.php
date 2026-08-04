<?php

use App\Models\Order;
use App\Models\Product;
use App\Support\Cart;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

beforeEach(function () {
    Mail::fake();
});

/**
 * Produk dengan stok terpisah per kombinasi ukuran dan warna.
 */
function variantProduct(int $stockPerVariant = 5): Product
{
    return Product::factory()
        ->withVariants(['M', 'L'], ['Hitam', 'Putih'], $stockPerVariant)
        ->create(['price' => 100000]);
}

test('the product stock mirrors the total stock of its variants', function () {
    $product = variantProduct(3);

    expect($product->fresh()->stock)->toBe(12);
});

test('adding to the cart is limited by the stock of the chosen variant', function () {
    $product = variantProduct(2);

    $this->postJson(route('cart.add', $product), [
        'quantity' => 9,
        'size' => 'M',
        'color' => 'Hitam',
    ])->assertSuccessful()->assertJson(['success' => true, 'count' => 2]);

    expect(session('cart'))->toBe([Cart::lineKey($product->id, 'M', 'Hitam') => 2]);
});

test('one sold out variant does not block the other variants', function () {
    $product = variantProduct(4);
    $product->variants()->where('size', 'M')->where('color', 'Hitam')->update(['stock' => 0]);

    $this->postJson(route('cart.add', $product), ['quantity' => 1, 'size' => 'M', 'color' => 'Hitam'])
        ->assertStatus(422);

    $this->postJson(route('cart.add', $product), ['quantity' => 1, 'size' => 'L', 'color' => 'Putih'])
        ->assertSuccessful();

    expect(session('cart'))->toBe([Cart::lineKey($product->id, 'L', 'Putih') => 1]);
});

test('a combination that was never created is refused', function () {
    $product = variantProduct();

    $this->postJson(route('cart.add', $product), ['quantity' => 1, 'size' => 'XXL', 'color' => 'Merah'])
        ->assertStatus(422);

    expect(session('cart'))->toBeNull();
});

test('two variants of the same product each keep their own stock', function () {
    $product = variantProduct(5);

    $this->postJson(route('cart.add', $product), ['quantity' => 5, 'size' => 'M', 'color' => 'Hitam'])->assertSuccessful();
    $this->postJson(route('cart.add', $product), ['quantity' => 5, 'size' => 'L', 'color' => 'Hitam'])->assertSuccessful();

    expect(Cart::totalQuantity())->toBe(10);
});

test('checkout only reduces the stock of the ordered variant', function () {
    $product = variantProduct(5);
    session(['cart' => [Cart::lineKey($product->id, 'M', 'Hitam') => 2]]);

    Livewire::test('pages::checkout')
        ->set('name', 'Naufal Thafhan')
        ->set('email', 'pembeli@example.com')
        ->set('phone', '081324825060')
        ->set('city', 'Bandung')
        ->set('address', 'Jalan Merdeka Nomor 10 Bandung')
        ->call('createOrder');

    $ordered = $product->variants()->where('size', 'M')->where('color', 'Hitam')->sole();
    $untouched = $product->variants()->where('size', 'L')->where('color', 'Hitam')->sole();

    expect($ordered->stock)->toBe(3)
        ->and($untouched->stock)->toBe(5)
        ->and($product->fresh()->stock)->toBe(18)
        ->and(Order::sole()->items->first()->product_variant_id)->toBe($ordered->id);
});

test('checkout is refused when the chosen variant runs out', function () {
    $product = variantProduct(1);
    session(['cart' => [Cart::lineKey($product->id, 'M', 'Hitam') => 3]]);

    Livewire::test('pages::checkout')
        ->set('name', 'Naufal Thafhan')
        ->set('email', 'pembeli@example.com')
        ->set('phone', '081324825060')
        ->set('city', 'Bandung')
        ->set('address', 'Jalan Merdeka Nomor 10 Bandung')
        ->call('createOrder')
        ->assertNoRedirect();

    expect(Order::count())->toBe(0)
        ->and($product->fresh()->stock)->toBe(4);
});

test('the product detail page offers only the sizes and colours that exist', function () {
    $product = variantProduct();

    Livewire::test('pages::product-detail', ['product' => $product])
        ->assertSet('usesVariants', true)
        ->assertSet('availableSizes', ['M', 'L'])
        ->assertSet('availableColors', ['Hitam', 'Putih']);
});

test('the product detail page caps the quantity to the variant stock', function () {
    $product = variantProduct(2);

    Livewire::test('pages::product-detail', ['product' => $product])
        ->set('selectedSize', 'M')
        ->set('selectedColor', 'Hitam')
        ->set('quantity', 7)
        ->call('addToCart');

    expect(session('cart'))->toBe([Cart::lineKey($product->id, 'M', 'Hitam') => 2]);
});

test('the options endpoint exposes the stock of every variant', function () {
    $product = variantProduct(3);

    $this->getJson(route('product.options', $product))
        ->assertSuccessful()
        ->assertJsonPath('sizes', ['M', 'L'])
        ->assertJsonPath('variants.M|Hitam', 3)
        ->assertJsonPath('variants.L|Putih', 3);
});

test('the options endpoint reports no variants for a single stock product', function () {
    $product = Product::factory()->stock(5)->create();

    $this->getJson(route('product.options', $product))
        ->assertSuccessful()
        ->assertJsonPath('variants', null);
});
