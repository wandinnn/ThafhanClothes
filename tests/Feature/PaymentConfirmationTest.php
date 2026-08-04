<?php

use App\Models\Order;
use App\Models\OrderItem;
use App\Support\OrderAccess;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

function makePayableOrder(string $orderNumber = 'PAY0000001', string $status = 'pending_payment'): Order
{
    $order = Order::create([
        'order_number' => $orderNumber,
        'customer_name' => 'Naufal Thafhan',
        'customer_phone' => '081324825060',
        'city' => 'Bandung',
        'address' => 'Jalan Merdeka Nomor 10',
        'subtotal' => 150000,
        'discount' => 10000,
        'shipping_cost' => 0,
        'total' => 140000,
        'coupon_code' => 'HEMAT10',
        'status' => $status,
    ]);

    OrderItem::create([
        'order_id' => $order->id,
        'product_name' => 'Kemeja Flanel',
        'product_price' => 75000,
        'quantity' => 2,
        'subtotal' => 150000,
    ]);

    return $order;
}

function fakeProofImage(string $name = 'bukti.jpg'): UploadedFile
{
    return UploadedFile::fake()->create($name, 20, 'image/jpeg');
}

function unlockPayment(string $orderNumber = 'PAY0000001'): void
{
    OrderAccess::grant($orderNumber);
}

test('the payment page shows the order number after verification', function () {
    makePayableOrder();
    unlockPayment();

    $this->get(route('payment', 'PAY0000001'))
        ->assertSuccessful()
        ->assertSee('PAY0000001')
        ->assertSee('Rp140.000');
});

test('the payment page asks for verification before showing payment details', function () {
    makePayableOrder();

    $this->get(route('payment', 'PAY0000001'))
        ->assertSuccessful()
        ->assertSee('Verifikasi Akses Pembayaran')
        ->assertDontSee('Upload Bukti Pembayaran');
});

test('the payment page shows the discount line of the order', function () {
    makePayableOrder();
    unlockPayment();

    $this->get(route('payment', 'PAY0000001'))
        ->assertSuccessful()
        ->assertSee('HEMAT10');
});

test('the payment page reports an unknown order number', function () {
    $this->get(route('payment', 'TIDAKADA99'))
        ->assertSuccessful()
        ->assertSee('Pesanan tidak ditemukan.');
});

test('uploading a proof marks the order as payment uploaded', function () {
    Storage::fake('public');
    $order = makePayableOrder();
    unlockPayment();

    Livewire::test('pages::payment', ['order' => 'PAY0000001'])
        ->set('proofPhoto', fakeProofImage())
        ->call('confirmPayment')
        ->assertRedirect(route('order.success', 'PAY0000001'));

    $order->refresh();

    expect($order->status)->toBe('payment_uploaded')
        ->and($order->payment_proof_path)->not->toBeNull();

    Storage::disk('public')->assertExists($order->payment_proof_path);
});

test('a shipped order can no longer receive a payment proof', function () {
    Storage::fake('public');
    $order = makePayableOrder('PAY0000002', 'shipped');
    unlockPayment('PAY0000002');

    Livewire::test('pages::payment', ['order' => 'PAY0000002'])
        ->set('proofPhoto', fakeProofImage())
        ->call('confirmPayment')
        ->assertNoRedirect();

    expect($order->fresh()->status)->toBe('shipped');
});

test('a cancelled order can no longer receive a payment proof', function () {
    Storage::fake('public');
    $order = makePayableOrder('PAY0000003', 'cancelled');
    unlockPayment('PAY0000003');

    Livewire::test('pages::payment', ['order' => 'PAY0000003'])
        ->set('proofPhoto', fakeProofImage())
        ->call('confirmPayment')
        ->assertNoRedirect();

    expect($order->fresh()->status)->toBe('cancelled');
});

test('the upload form is hidden once the order has moved on', function () {
    makePayableOrder('PAY0000004', 'delivered');
    unlockPayment('PAY0000004');

    $this->get(route('payment', 'PAY0000004'))
        ->assertSuccessful()
        ->assertSee('bukti pembayaran tidak bisa diubah lagi');
});

test('only a known payment method can be selected', function () {
    makePayableOrder('PAY0000005');
    unlockPayment('PAY0000005');

    Livewire::test('pages::payment', ['order' => 'PAY0000005'])
        ->call('setTab', 'kartu-kredit')
        ->assertSet('activeTab', 'transfer');
});

test('replacing a proof removes the previous file', function () {
    Storage::fake('public');
    $order = makePayableOrder('PAY0000006');
    unlockPayment('PAY0000006');

    Livewire::test('pages::payment', ['order' => 'PAY0000006'])
        ->set('proofPhoto', fakeProofImage('pertama.jpg'))
        ->call('confirmPayment');

    $firstPath = $order->fresh()->payment_proof_path;

    Livewire::test('pages::payment', ['order' => 'PAY0000006'])
        ->set('proofPhoto', fakeProofImage('kedua.jpg'))
        ->call('confirmPayment');

    Storage::disk('public')->assertMissing($firstPath);
    Storage::disk('public')->assertExists($order->fresh()->payment_proof_path);
});
