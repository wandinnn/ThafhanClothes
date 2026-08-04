<?php

use App\Mail\OrderShippedMail;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Support\OrderAccess;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

beforeEach(function () {
    Mail::fake();
    $this->actingAs(User::factory()->admin()->create());
});

function shippableOrder(): Order
{
    $product = Product::factory()->stock(10)->create();

    $order = Order::create([
        'order_number' => 'SHIP'.fake()->unique()->numerify('######'),
        'customer_name' => 'Naufal Thafhan',
        'customer_email' => 'pembeli@example.com',
        'customer_phone' => '081324825060',
        'city' => 'Bandung',
        'address' => 'Jalan Merdeka Nomor 10',
        'subtotal' => $product->price,
        'discount' => 0,
        'shipping_cost' => 0,
        'total' => $product->price,
        'status' => 'processing',
    ]);

    OrderItem::create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'product_name' => $product->name,
        'product_price' => $product->price,
        'quantity' => 1,
        'subtotal' => $product->price,
    ]);

    return $order;
}

test('saving a tracking number stores it and emails the buyer', function () {
    $order = shippableOrder();

    Livewire::test('pages::admin-orders')
        ->call('viewOrder', $order->id)
        ->set('shippingCourier', 'JNE')
        ->set('trackingNumber', 'JP1234567890')
        ->call('saveShipment');

    $order->refresh();

    expect($order->shipping_courier)->toBe('JNE')
        ->and($order->tracking_number)->toBe('JP1234567890')
        ->and($order->shipped_at)->not->toBeNull()
        ->and($order->status)->toBe('shipped');

    Mail::assertSent(OrderShippedMail::class);
});

test('a tracking number without a courier is refused', function () {
    $order = shippableOrder();

    Livewire::test('pages::admin-orders')
        ->call('viewOrder', $order->id)
        ->set('trackingNumber', 'JP1234567890')
        ->call('saveShipment')
        ->assertHasErrors('shippingCourier');

    expect($order->fresh()->tracking_number)->toBeNull();
    Mail::assertNothingSent();
});

test('an unknown courier is refused', function () {
    $order = shippableOrder();

    Livewire::test('pages::admin-orders')
        ->call('viewOrder', $order->id)
        ->set('shippingCourier', 'Kurir Karangan')
        ->set('trackingNumber', 'JP1234567890')
        ->call('saveShipment')
        ->assertHasErrors(['shippingCourier' => 'in']);

    expect($order->fresh()->tracking_number)->toBeNull();
});

test('saving the same tracking number again does not email the buyer twice', function () {
    $order = shippableOrder();

    $component = Livewire::test('pages::admin-orders')
        ->call('viewOrder', $order->id)
        ->set('shippingCourier', 'SiCepat')
        ->set('trackingNumber', 'SC998877')
        ->call('saveShipment');

    Mail::fake();

    $component->call('saveShipment');

    Mail::assertNothingSent();
});

test('opening an order fills the shipment fields already saved', function () {
    $order = shippableOrder();
    $order->update(['shipping_courier' => 'TIKI', 'tracking_number' => 'TK55443322']);

    Livewire::test('pages::admin-orders')
        ->call('viewOrder', $order->id)
        ->assertSet('shippingCourier', 'TIKI')
        ->assertSet('trackingNumber', 'TK55443322');
});

test('the buyer sees the tracking number on the order page', function () {
    $order = shippableOrder();
    $order->update(['shipping_courier' => 'AnterAja', 'tracking_number' => 'AA123456789', 'status' => 'shipped']);

    auth()->logout();
    OrderAccess::grant($order->order_number);

    $this->get(route('order.detail', $order->order_number))
        ->assertSuccessful()
        ->assertSee('AA123456789')
        ->assertSee('AnterAja');
});
