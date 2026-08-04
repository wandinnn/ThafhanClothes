<?php

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Livewire\Livewire;

function makeAdminOrder(Product $product, int $quantity = 2, string $status = 'processing'): Order
{
    $order = Order::create([
        'order_number' => 'ADM'.fake()->unique()->numerify('#######'),
        'customer_name' => 'Naufal Thafhan',
        'customer_phone' => '081324825060',
        'city' => 'Bandung',
        'address' => 'Jalan Merdeka Nomor 10',
        'subtotal' => $product->price * $quantity,
        'discount' => 0,
        'shipping_cost' => 0,
        'total' => $product->price * $quantity,
        'status' => $status,
    ]);

    OrderItem::create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'product_name' => $product->name,
        'product_price' => $product->price,
        'quantity' => $quantity,
        'subtotal' => $product->price * $quantity,
    ]);

    return $order;
}

beforeEach(function () {
    $this->actingAs(User::factory()->admin()->create());
});

test('cancelling an order returns the stock to the products', function () {
    $product = Product::factory()->stock(8)->create();
    $order = makeAdminOrder($product);

    Livewire::test('pages::admin-orders')->call('updateStatus', $order->id, 'cancelled');

    expect($order->fresh()->status)->toBe('cancelled')
        ->and($product->fresh()->stock)->toBe(10);
});

test('cancelling an order twice does not restock it twice', function () {
    $product = Product::factory()->stock(8)->create();
    $order = makeAdminOrder($product);

    Livewire::test('pages::admin-orders')
        ->call('updateStatus', $order->id, 'cancelled')
        ->call('updateStatus', $order->id, 'cancelled');

    expect($product->fresh()->stock)->toBe(10);
});

test('reactivating a cancelled order takes the stock again', function () {
    $product = Product::factory()->stock(8)->create();
    $order = makeAdminOrder($product);

    Livewire::test('pages::admin-orders')
        ->call('updateStatus', $order->id, 'cancelled')
        ->call('updateStatus', $order->id, 'processing');

    expect($order->fresh()->status)->toBe('processing')
        ->and($product->fresh()->stock)->toBe(8);
});

test('reactivating is blocked when the stock is no longer available', function () {
    $product = Product::factory()->stock(0)->create();
    $order = makeAdminOrder($product, 2, 'cancelled');

    Livewire::test('pages::admin-orders')->call('updateStatus', $order->id, 'processing');

    expect($order->fresh()->status)->toBe('cancelled')
        ->and($product->fresh()->stock)->toBe(0);
});

test('an unknown status is rejected', function () {
    $product = Product::factory()->stock(8)->create();
    $order = makeAdminOrder($product);

    Livewire::test('pages::admin-orders')->call('updateStatus', $order->id, 'lunas-banget');

    expect($order->fresh()->status)->toBe('processing');
});

test('opening an order shows its detail panel', function () {
    $product = Product::factory()->stock(8)->create();
    $order = makeAdminOrder($product);

    Livewire::test('pages::admin-orders')
        ->call('viewOrder', $order->id)
        ->assertSee($order->customer_name)
        ->assertSee($product->name)
        ->call('closeDetail')
        ->assertSet('viewingId', null);
});

test('the order list keeps the status filter while searching', function () {
    $product = Product::factory()->stock(20)->create();
    $matching = makeAdminOrder($product, 1, 'cancelled');
    $other = makeAdminOrder($product, 1, 'processing');

    Livewire::test('pages::admin-orders')
        ->set('search', 'Naufal')
        ->set('filterStatus', 'cancelled')
        ->assertSee($matching->order_number)
        ->assertDontSee($other->order_number);
});
