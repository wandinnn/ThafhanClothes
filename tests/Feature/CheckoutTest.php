<?php

use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Support\Cart;
use App\Support\OrderAccess;
use Illuminate\Support\Facades\Mail;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

beforeEach(function () {
    Mail::fake();
});

/**
 * Siapkan komponen checkout dengan data pengiriman yang valid.
 */
function checkoutComponent(): Testable
{
    return Livewire::test('pages::checkout')
        ->set('name', 'Naufal Thafhan')
        ->set('email', 'pembeli@example.com')
        ->set('phone', '081324825060')
        ->set('city', 'Bandung')
        ->set('address', 'Jalan Merdeka Nomor 10 Bandung');
}

function seedCart(Product $product, int $quantity = 1, string $size = 'M', string $color = 'Hitam'): void
{
    session(['cart' => [Cart::lineKey($product->id, $size, $color) => $quantity]]);
}

test('checkout stores the order and reduces the stock', function () {
    $product = Product::factory()->stock(10)->create(['price' => 100000]);
    seedCart($product, 2);

    checkoutComponent()->call('createOrder');

    $order = Order::sole();

    expect($order->subtotal)->toBe(200000)
        ->and($order->shipping_cost)->toBe(0)
        ->and($order->total)->toBe(200000)
        ->and($order->items)->toHaveCount(1)
        ->and($order->items->first()->selected_size)->toBe('M')
        ->and($order->items->first()->selected_color)->toBe('Hitam')
        ->and($product->fresh()->stock)->toBe(8)
        ->and(session('cart'))->toBeNull()
        ->and(OrderAccess::has($order->order_number))->toBeTrue();
});

test('checkout keeps separate lines for different sizes', function () {
    $product = Product::factory()->stock(10)->create(['price' => 100000]);
    session(['cart' => [
        Cart::lineKey($product->id, 'L', 'Hitam') => 1,
        Cart::lineKey($product->id, 'XL', 'Hitam') => 2,
    ]]);

    checkoutComponent()->call('createOrder');

    $order = Order::sole();

    expect($order->items)->toHaveCount(2)
        ->and($order->subtotal)->toBe(300000)
        ->and($product->fresh()->stock)->toBe(7);
});

test('checkout redirects to the payment page using the order number', function () {
    $product = Product::factory()->stock(3)->create(['price' => 100000]);
    seedCart($product);

    $component = checkoutComponent()->call('createOrder');

    $component->assertRedirect(route('payment', ['order' => Order::sole()->order_number]));
});

test('checkout charges the shipping rate of the selected city', function () {
    $product = Product::factory()->stock(3)->create(['price' => 100000]);
    seedCart($product);

    checkoutComponent()->set('city', 'Jakarta')->call('createOrder');

    $order = Order::sole();

    expect($order->shipping_cost)->toBe(25000)
        ->and($order->total)->toBe(125000);
});

test('checkout rejects a city that is not served', function () {
    $product = Product::factory()->stock(3)->create();
    seedCart($product);

    checkoutComponent()
        ->set('city', 'Kota Fiktif')
        ->call('createOrder')
        ->assertHasErrors(['city' => 'in']);

    expect(Order::count())->toBe(0);
});

test('checkout refuses an order when the stock is not enough', function () {
    $product = Product::factory()->stock(1)->create(['price' => 100000]);
    seedCart($product, 3);

    checkoutComponent()
        ->call('createOrder')
        ->assertNoRedirect();

    expect(Order::count())->toBe(0)
        ->and($product->fresh()->stock)->toBe(1);
});

test('checkout leaves the stock untouched when one item of many is unavailable', function () {
    $available = Product::factory()->stock(10)->create(['price' => 100000]);
    $unavailable = Product::factory()->stock(0)->create(['price' => 50000]);
    session(['cart' => [
        Cart::lineKey($available->id, 'M', 'Hitam') => 1,
        Cart::lineKey($unavailable->id, 'M', 'Hitam') => 1,
    ]]);

    checkoutComponent()->call('createOrder');

    expect(Order::count())->toBe(0)
        ->and($available->fresh()->stock)->toBe(10);
});

test('checkout skips invalid quantities stored in the session', function () {
    $product = Product::factory()->stock(5)->create(['price' => 100000]);
    seedCart($product, -3);

    checkoutComponent()->call('createOrder');

    expect(Order::count())->toBe(0)
        ->and($product->fresh()->stock)->toBe(5);
});

test('checkout claims the coupon quota exactly once', function () {
    $product = Product::factory()->stock(5)->create(['price' => 200000]);
    $coupon = Coupon::factory()->create(['code' => 'HEMAT10', 'value' => 20000]);
    seedCart($product);

    checkoutComponent()
        ->set('appliedCouponCode', 'HEMAT10')
        ->call('createOrder');

    $order = Order::sole();

    expect($order->discount)->toBe(20000)
        ->and($order->coupon_code)->toBe('HEMAT10')
        ->and($order->total)->toBe(180000)
        ->and($coupon->fresh()->used_count)->toBe(1);
});

test('checkout ignores a coupon that has reached its usage limit', function () {
    $product = Product::factory()->stock(5)->create(['price' => 200000]);
    $coupon = Coupon::factory()->create([
        'code' => 'HABIS',
        'value' => 20000,
        'max_uses' => 1,
        'used_count' => 1,
    ]);
    seedCart($product);

    checkoutComponent()
        ->set('appliedCouponCode', 'HABIS')
        ->call('createOrder');

    $order = Order::sole();

    expect($order->discount)->toBe(0)
        ->and($order->coupon_code)->toBeNull()
        ->and($order->total)->toBe(200000)
        ->and($coupon->fresh()->used_count)->toBe(1);
});

test('checkout ignores an unknown coupon code injected into the component state', function () {
    $product = Product::factory()->stock(5)->create(['price' => 200000]);
    seedCart($product);

    checkoutComponent()
        ->set('appliedCouponCode', 'KODEPALSU')
        ->call('createOrder');

    expect(Order::sole()->discount)->toBe(0);
});

test('checkout stores prices from the database instead of the browser state', function () {
    $product = Product::factory()->stock(5)->create(['price' => 150000]);
    seedCart($product, 2);

    checkoutComponent()->call('createOrder');

    $item = Order::sole()->items->first();

    expect($item->product_price)->toBe(150000)
        ->and($item->quantity)->toBe(2)
        ->and($item->subtotal)->toBe(300000);
});

test('checkout reports an empty cart without creating an order', function () {
    checkoutComponent()
        ->call('createOrder')
        ->assertNoRedirect();

    expect(Order::count())->toBe(0);
});

test('applying a coupon below the minimum order shows an error', function () {
    $product = Product::factory()->stock(5)->create(['price' => 50000]);
    Coupon::factory()->create(['code' => 'BESAR', 'value' => 20000, 'min_order' => 300000]);
    seedCart($product);

    checkoutComponent()
        ->set('couponCode', 'besar')
        ->call('applyCoupon')
        ->assertSet('appliedCouponCode', '');
});

test('applying a valid coupon keeps it in the component state', function () {
    $product = Product::factory()->stock(5)->create(['price' => 200000]);
    Coupon::factory()->create(['code' => 'HEMAT10', 'value' => 20000]);
    seedCart($product);

    checkoutComponent()
        ->set('couponCode', 'hemat10')
        ->call('applyCoupon')
        ->assertSet('appliedCouponCode', 'HEMAT10');
});
