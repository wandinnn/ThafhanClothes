<?php

use App\Models\Order;
use App\Models\OrderItem;
use App\Support\OrderAccess;
use Livewire\Livewire;

function makeTrackableOrder(string $orderNumber = 'ABC1234567', string $status = 'processing'): Order
{
    $order = Order::create([
        'order_number' => $orderNumber,
        'customer_name' => 'Naufal Thafhan',
        'customer_phone' => '081324825060',
        'city' => 'Bandung',
        'address' => 'Jalan Merdeka No. 10',
        'subtotal' => 150000,
        'discount' => 10000,
        'shipping_cost' => 5000,
        'total' => 145000,
        'coupon_code' => 'HEMAT10',
        'status' => $status,
    ]);

    OrderItem::create([
        'order_id' => $order->id,
        'product_name' => 'Kemeja Flanel',
        'product_price' => 75000,
        'selected_size' => 'L',
        'selected_color' => 'Hitam',
        'quantity' => 2,
        'subtotal' => 150000,
    ]);

    return $order->load('items');
}

function grantOrderAccess(string $orderNumber = 'ABC1234567'): void
{
    OrderAccess::grant($orderNumber);
}

test('order detail page shows the stored order after verification', function () {
    makeTrackableOrder();
    grantOrderAccess();

    $this->get(route('order.detail', 'ABC1234567'))
        ->assertSuccessful()
        ->assertSee('ABC1234567')
        ->assertSee('Naufal Thafhan')
        ->assertSee('Kemeja Flanel')
        ->assertSee('Sedang Diproses')
        ->assertSee('Rp145.000');
});

test('order detail page asks for phone verification before showing personal data', function () {
    makeTrackableOrder();

    $this->get(route('order.detail', 'ABC1234567'))
        ->assertSuccessful()
        ->assertSee('Verifikasi Akses')
        ->assertDontSee('Jalan Merdeka No. 10');
});

test('order detail page unlocks with the correct phone tail', function () {
    makeTrackableOrder();

    Livewire::test('pages::order-detail', ['order' => 'ABC1234567'])
        ->set('phoneTail', '5060')
        ->call('unlock')
        ->assertSee('Naufal Thafhan')
        ->assertSee('Jalan Merdeka No. 10');
});

test('order detail page finds an order regardless of letter casing', function () {
    makeTrackableOrder();
    grantOrderAccess();

    $this->get(route('order.detail', 'abc1234567'))
        ->assertSuccessful()
        ->assertSee('ABC1234567');
});

test('order detail page reports an unknown order number', function () {
    $this->get(route('order.detail', 'TIDAKADA99'))
        ->assertSuccessful()
        ->assertSee('Pesanan tidak ditemukan.');
});

test('order detail page replaces the timeline for a cancelled order', function () {
    makeTrackableOrder('CANCEL0001', 'cancelled');
    grantOrderAccess('CANCEL0001');

    $this->get(route('order.detail', 'CANCEL0001'))
        ->assertSuccessful()
        ->assertSee('Pesanan ini telah dibatalkan.');
});

test('order success page shows the order created at checkout', function () {
    makeTrackableOrder();
    grantOrderAccess();

    $this->get(route('order.success', 'ABC1234567'))
        ->assertSuccessful()
        ->assertSee('ABC1234567')
        ->assertSee('Naufal Thafhan')
        ->assertSee('Sedang Diproses');
});

test('order success page reports an unknown order number', function () {
    $this->get(route('order.success', 'TIDAKADA99'))
        ->assertSuccessful()
        ->assertSee('Pesanan tidak ditemukan.');
});

test('tracking an existing order redirects to its detail page', function () {
    makeTrackableOrder();

    Livewire::test('pages::track-order')
        ->set('orderCode', 'abc1234567')
        ->set('phoneTail', '5060')
        ->call('goToOrder')
        ->assertRedirect(route('order.detail', 'ABC1234567'));
});

test('tracking rejects a wrong phone number', function () {
    makeTrackableOrder();

    Livewire::test('pages::track-order')
        ->set('orderCode', 'ABC1234567')
        ->set('phoneTail', '9999')
        ->call('goToOrder')
        ->assertNoRedirect()
        ->assertSee('Nomor telepon tidak cocok');
});

test('tracking an unknown order shows an error message', function () {
    Livewire::test('pages::track-order')
        ->set('orderCode', 'TIDAKADA99')
        ->set('phoneTail', '5060')
        ->call('goToOrder')
        ->assertNoRedirect()
        ->assertSee('Kode pesanan tidak ditemukan');
});

test('tracking without an order code shows an error message', function () {
    Livewire::test('pages::track-order')
        ->call('goToOrder')
        ->assertNoRedirect()
        ->assertSee('Masukkan kode pesanan terlebih dahulu.');
});
