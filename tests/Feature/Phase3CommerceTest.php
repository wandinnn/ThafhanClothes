<?php

use App\Contracts\PaymentGateway;
use App\Contracts\ShippingRateProvider;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Support\Cart;
use App\Support\OrderAccess;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

beforeEach(function () {
    Mail::fake();
    config([
        'shop.payment_driver' => 'manual',
        'shop.shipping_driver' => 'static',
    ]);
    app()->forgetInstance(ShippingRateProvider::class);
    app()->forgetInstance(PaymentGateway::class);
});

test('fake shipping driver returns multiple courier quotes', function () {
    config(['shop.shipping_driver' => 'fake']);

    $quotes = app(ShippingRateProvider::class)->quotesFor('Jakarta');

    expect($quotes)->not->toBeEmpty()
        ->and(count($quotes))->toBeGreaterThan(1)
        ->and($quotes[0]->courier)->not->toBeEmpty();
});

test('checkout stores selected shipping service metadata', function () {
    $product = Product::factory()->stock(5)->create(['price' => 100000]);
    session(['cart' => [Cart::lineKey($product->id, 'M', 'Hitam') => 1]]);

    Livewire::test('pages::checkout')
        ->set('name', 'Naufal Thafhan')
        ->set('email', 'pembeli@example.com')
        ->set('phone', '081324825060')
        ->set('city', 'Jakarta')
        ->set('address', 'Jalan Merdeka Nomor 10 Jakarta')
        ->set('shippingServiceCode', 'static-reg')
        ->call('createOrder');

    $order = Order::sole();

    expect($order->shipping_cost)->toBe(25000)
        ->and($order->shipping_service)->toContain('Thafhan Kurir')
        ->and($order->shipping_etd)->not->toBeEmpty()
        ->and($order->payment_expires_at)->not->toBeNull();
});

test('fake payment gateway can settle an order instantly', function () {
    config(['shop.payment_driver' => 'fake']);

    $product = Product::factory()->stock(5)->create(['price' => 120000]);
    session(['cart' => [Cart::lineKey($product->id, 'M', 'Hitam') => 1]]);

    Livewire::test('pages::checkout')
        ->set('name', 'Naufal Thafhan')
        ->set('email', 'pembeli@example.com')
        ->set('phone', '081324825060')
        ->set('city', 'Bandung')
        ->set('address', 'Jalan Merdeka Nomor 10 Bandung')
        ->call('createOrder');

    $order = Order::sole();
    OrderAccess::grant($order->order_number);

    Livewire::test('pages::payment', ['order' => $order->order_number])
        ->call('payWithGateway')
        ->assertRedirect(route('order.success', $order->order_number));

    expect($order->fresh()->status)->toBe('confirmed')
        ->and($order->fresh()->payment_gateway)->toBe('fake')
        ->and($order->fresh()->paid_at)->not->toBeNull();
});

test('invoice page is available after order access is granted', function () {
    $order = Order::query()->create([
        'order_number' => 'INVTEST001',
        'customer_name' => 'Naufal',
        'customer_email' => 'a@example.com',
        'customer_phone' => '081324825060',
        'city' => 'Bandung',
        'address' => 'Jl. Test No. 1 Bandung',
        'subtotal' => 100000,
        'discount' => 0,
        'shipping_cost' => 0,
        'total' => 100000,
        'status' => 'confirmed',
        'payment_gateway' => 'manual',
    ]);

    $this->get(route('order.invoice', $order->order_number))->assertForbidden();

    OrderAccess::grant($order->order_number);

    $this->get(route('order.invoice', $order->order_number))
        ->assertSuccessful()
        ->assertSee('INVTEST001')
        ->assertSee('Naufal');
});

test('expired unpaid orders are cancelled and stock is restored', function () {
    $product = Product::factory()->stock(3)->create();
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'size' => 'M',
        'color' => 'Hitam',
        'stock' => 2,
    ]);
    $product->syncStockFromVariants();

    $order = Order::query()->create([
        'order_number' => 'EXPIRE001',
        'customer_name' => 'Naufal',
        'customer_email' => 'a@example.com',
        'customer_phone' => '081324825060',
        'city' => 'Bandung',
        'address' => 'Jl. Test No. 1 Bandung',
        'subtotal' => 100000,
        'discount' => 0,
        'shipping_cost' => 0,
        'total' => 100000,
        'status' => 'pending_payment',
        'payment_gateway' => 'manual',
        'payment_expires_at' => now()->subHour(),
    ]);

    $order->items()->create([
        'product_id' => $product->id,
        'product_variant_id' => $variant->id,
        'product_name' => $product->name,
        'product_image' => $product->image_url,
        'product_price' => $product->price,
        'selected_size' => 'M',
        'selected_color' => 'Hitam',
        'quantity' => 1,
        'subtotal' => $product->price,
    ]);

    $variant->decrement('stock', 1);
    $product->syncStockFromVariants();

    Artisan::call('shop:cancel-expired-orders');

    expect($order->fresh()->status)->toBe('cancelled')
        ->and($variant->fresh()->stock)->toBe(2);
});

test('midtrans webhook can confirm payment', function () {
    $order = Order::query()->create([
        'order_number' => 'MIDHOOK001',
        'customer_name' => 'Naufal',
        'customer_email' => 'a@example.com',
        'customer_phone' => '081324825060',
        'city' => 'Bandung',
        'address' => 'Jl. Test No. 1 Bandung',
        'subtotal' => 150000,
        'discount' => 0,
        'shipping_cost' => 0,
        'total' => 150000,
        'status' => 'pending_payment',
        'payment_gateway' => 'midtrans',
    ]);

    $this->postJson(route('webhooks.midtrans'), [
        'order_id' => 'MIDHOOK001',
        'transaction_status' => 'settlement',
        'transaction_id' => 'TX-123',
        'fraud_status' => 'accept',
    ])->assertSuccessful();

    expect($order->fresh()->status)->toBe('confirmed')
        ->and($order->fresh()->payment_transaction_id)->toBe('TX-123');
});

test('midtrans snap session includes shipping and discount in item details', function () {
    config([
        'shop.payment_driver' => 'midtrans',
        'shop.payment.midtrans.server_key' => 'SB-mid-server-test',
        'shop.payment.midtrans.client_key' => 'SB-mid-client-test',
        'shop.payment.midtrans.is_production' => false,
    ]);
    app()->forgetInstance(PaymentGateway::class);

    Http::fake([
        'app.sandbox.midtrans.com/*' => Http::response([
            'token' => 'snap-token-xyz',
            'redirect_url' => 'https://app.sandbox.midtrans.com/snap/v2/vtweb/snap-token-xyz',
        ], 201),
    ]);

    $order = Order::query()->create([
        'order_number' => 'MIDSNAP001',
        'customer_name' => 'Naufal',
        'customer_email' => 'a@example.com',
        'customer_phone' => '081324825060',
        'city' => 'Jakarta',
        'address' => 'Jl. Test No. 1 Jakarta',
        'subtotal' => 200000,
        'discount' => 20000,
        'shipping_cost' => 25000,
        'total' => 205000,
        'coupon_code' => 'HEMAT',
        'status' => 'pending_payment',
    ]);

    $order->items()->create([
        'product_name' => 'Kaos Premium',
        'product_price' => 100000,
        'quantity' => 2,
        'subtotal' => 200000,
    ]);

    $session = app(PaymentGateway::class)->createSession($order->fresh(['items']));

    expect($session->mode)->toBe('snap')
        ->and($session->transactionId)->toBe('snap-token-xyz')
        ->and($session->meta['token'])->toBe('snap-token-xyz');

    Http::assertSent(function ($request) {
        $items = $request['item_details'] ?? [];
        $sum = collect($items)->sum(fn (array $row): int => $row['price'] * $row['quantity']);

        return $request->url() === 'https://app.sandbox.midtrans.com/snap/v1/transactions'
            && $request['transaction_details']['order_id'] === 'MIDSNAP001'
            && $request['transaction_details']['gross_amount'] === 205000
            && $sum === 205000
            && collect($items)->contains(fn (array $row): bool => $row['id'] === 'SHIPPING')
            && collect($items)->contains(fn (array $row): bool => $row['id'] === 'DISCOUNT');
    });
});

test('midtrans webhook rejects invalid signature when server key is set', function () {
    config(['shop.payment.midtrans.server_key' => 'SB-mid-server-secret']);

    $order = Order::query()->create([
        'order_number' => 'MIDSIG001',
        'customer_name' => 'Naufal',
        'customer_email' => 'a@example.com',
        'customer_phone' => '081324825060',
        'city' => 'Bandung',
        'address' => 'Jl. Test No. 1 Bandung',
        'subtotal' => 100000,
        'discount' => 0,
        'shipping_cost' => 0,
        'total' => 100000,
        'status' => 'pending_payment',
        'payment_gateway' => 'midtrans',
    ]);

    $this->postJson(route('webhooks.midtrans'), [
        'order_id' => 'MIDSIG001',
        'status_code' => '200',
        'gross_amount' => '100000',
        'signature_key' => 'invalid',
        'transaction_status' => 'settlement',
        'transaction_id' => 'TX-BAD',
    ])->assertForbidden();

    expect($order->fresh()->status)->toBe('pending_payment');
});

test('midtrans webhook accepts valid signature and amount', function () {
    $serverKey = 'SB-mid-server-secret';
    config(['shop.payment.midtrans.server_key' => $serverKey]);

    $order = Order::query()->create([
        'order_number' => 'MIDOK001',
        'customer_name' => 'Naufal',
        'customer_email' => 'a@example.com',
        'customer_phone' => '081324825060',
        'city' => 'Bandung',
        'address' => 'Jl. Test No. 1 Bandung',
        'subtotal' => 100000,
        'discount' => 0,
        'shipping_cost' => 0,
        'total' => 100000,
        'status' => 'pending_payment',
        'payment_gateway' => 'midtrans',
    ]);

    $statusCode = '200';
    $grossAmount = '100000';
    $signature = hash('sha512', 'MIDOK001'.$statusCode.$grossAmount.$serverKey);

    $this->postJson(route('webhooks.midtrans'), [
        'order_id' => 'MIDOK001',
        'status_code' => $statusCode,
        'gross_amount' => $grossAmount,
        'signature_key' => $signature,
        'transaction_status' => 'settlement',
        'transaction_id' => 'TX-OK',
        'fraud_status' => 'accept',
    ])->assertSuccessful();

    expect($order->fresh()->status)->toBe('confirmed')
        ->and($order->fresh()->payment_transaction_id)->toBe('TX-OK');
});
