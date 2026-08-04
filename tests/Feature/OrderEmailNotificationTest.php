<?php

use App\Mail\OrderPlacedMail;
use App\Mail\OrderShippedMail;
use App\Mail\OrderStatusUpdatedMail;
use App\Mail\PaymentProofReceivedMail;
use App\Mail\PaymentProofUploadedMail;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Support\Cart;
use App\Support\OrderAccess;
use App\Support\ShopSettings;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Mail::fake();
    config(['app.admin_email' => 'admin@thafhanclothes.test']);
});

function makeNotifiableOrder(string $status = 'pending_payment'): Order
{
    $order = Order::create([
        'order_number' => 'MAIL'.fake()->unique()->numerify('#######'),
        'customer_name' => 'Naufal Thafhan',
        'customer_email' => 'pembeli@example.com',
        'customer_phone' => '081324825060',
        'city' => 'Bandung',
        'address' => 'Jalan Merdeka Nomor 10',
        'subtotal' => 100000,
        'discount' => 0,
        'shipping_cost' => 0,
        'total' => 100000,
        'status' => $status,
    ]);

    OrderItem::create([
        'order_id' => $order->id,
        'product_name' => 'Kemeja Flanel',
        'product_price' => 100000,
        'selected_size' => 'M',
        'selected_color' => 'Hitam',
        'quantity' => 1,
        'subtotal' => 100000,
    ]);

    return $order;
}

test('checkout requires an email address', function () {
    $product = Product::factory()->stock(5)->create(['price' => 100000]);
    session(['cart' => [Cart::lineKey($product->id, 'M', 'Hitam') => 1]]);

    Livewire::test('pages::checkout')
        ->set('name', 'Naufal Thafhan')
        ->set('phone', '081324825060')
        ->set('city', 'Bandung')
        ->set('address', 'Jalan Merdeka Nomor 10 Bandung')
        ->call('createOrder')
        ->assertHasErrors(['email' => 'required']);

    expect(Order::count())->toBe(0);
});

test('checkout sends order confirmation emails to the customer and admin', function () {
    $product = Product::factory()->stock(5)->create(['price' => 100000]);
    session(['cart' => [Cart::lineKey($product->id, 'M', 'Hitam') => 1]]);

    Livewire::test('pages::checkout')
        ->set('name', 'Naufal Thafhan')
        ->set('email', 'pembeli@example.com')
        ->set('phone', '081324825060')
        ->set('city', 'Bandung')
        ->set('address', 'Jalan Merdeka Nomor 10 Bandung')
        ->call('createOrder');

    Mail::assertSent(OrderPlacedMail::class, function (OrderPlacedMail $mail) {
        return $mail->hasTo('pembeli@example.com');
    });

    Mail::assertSent(OrderPlacedMail::class, function (OrderPlacedMail $mail) {
        return $mail->hasTo('admin@thafhanclothes.test');
    });
});

test('uploading a payment proof emails the admin and customer', function () {
    Storage::fake('public');
    $order = makeNotifiableOrder();
    OrderAccess::grant($order->order_number);

    Livewire::test('pages::payment', ['order' => $order->order_number])
        ->set('proofPhoto', UploadedFile::fake()->create('bukti.jpg', 20, 'image/jpeg'))
        ->call('confirmPayment');

    Mail::assertSent(PaymentProofUploadedMail::class, function (PaymentProofUploadedMail $mail) use ($order) {
        return $mail->hasTo('admin@thafhanclothes.test')
            && $mail->order->is($order);
    });

    Mail::assertSent(PaymentProofReceivedMail::class, function (PaymentProofReceivedMail $mail) {
        return $mail->hasTo('pembeli@example.com');
    });
});

test('updating an order status emails the customer', function () {
    $this->actingAs(User::factory()->admin()->create());
    $order = makeNotifiableOrder('processing');

    Livewire::test('pages::admin-orders')
        ->call('updateStatus', $order->id, 'shipped');

    Mail::assertSent(OrderStatusUpdatedMail::class, function (OrderStatusUpdatedMail $mail) {
        return $mail->hasTo('pembeli@example.com')
            && $mail->order->fresh()->status === 'shipped'
            && $mail->previousStatus === 'processing';
    });
});

test('moving to shipped with tracking uses the shipment email', function () {
    $this->actingAs(User::factory()->admin()->create());
    $order = makeNotifiableOrder('processing');
    $order->update([
        'shipping_courier' => 'JNE',
        'tracking_number' => 'JP1234567890',
    ]);

    Livewire::test('pages::admin-orders')
        ->call('updateStatus', $order->id, 'shipped');

    Mail::assertSent(OrderShippedMail::class, function (OrderShippedMail $mail) {
        return $mail->hasTo('pembeli@example.com');
    });
    Mail::assertNotSent(OrderStatusUpdatedMail::class);
});

test('confirming payment emails the customer with guidance', function () {
    $this->actingAs(User::factory()->admin()->create());
    $order = makeNotifiableOrder('payment_uploaded');

    Livewire::test('pages::admin-orders')
        ->call('updateStatus', $order->id, 'confirmed');

    Mail::assertSent(OrderStatusUpdatedMail::class, function (OrderStatusUpdatedMail $mail) {
        return $mail->hasTo('pembeli@example.com')
            && $mail->order->status === 'confirmed'
            && $mail->previousStatus === 'payment_uploaded'
            && str_contains($mail->order->customerStatusGuidance(), 'dikonfirmasi');
    });
});

test('status update emails are skipped when the customer has no email', function () {
    $this->actingAs(User::factory()->admin()->create());
    $order = makeNotifiableOrder('processing');
    $order->update(['customer_email' => null]);

    Livewire::test('pages::admin-orders')
        ->call('updateStatus', $order->id, 'shipped');

    Mail::assertNothingSent();
});

test('status emails can be disabled via shop settings', function () {
    ShopSettings::putMany([
        ShopSettings::KEY_MAIL_ENABLED => '0',
    ]);
    $this->actingAs(User::factory()->admin()->create());
    $order = makeNotifiableOrder('processing');

    Livewire::test('pages::admin-orders')
        ->call('updateStatus', $order->id, 'confirmed');

    Mail::assertNothingSent();
});
