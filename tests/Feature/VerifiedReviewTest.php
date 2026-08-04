<?php

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Review;
use Livewire\Livewire;

function makeDeliveredOrderForProduct(Product $product, string $orderNumber = 'REV1234567', string $phone = '081324825060'): Order
{
    $order = Order::create([
        'order_number' => $orderNumber,
        'customer_name' => 'Naufal Thafhan',
        'customer_email' => 'pembeli@example.com',
        'customer_phone' => $phone,
        'city' => 'Bandung',
        'address' => 'Jalan Merdeka Nomor 10',
        'subtotal' => $product->price,
        'discount' => 0,
        'shipping_cost' => 0,
        'total' => $product->price,
        'status' => 'delivered',
    ]);

    OrderItem::create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'product_name' => $product->name,
        'product_price' => $product->price,
        'selected_size' => 'M',
        'selected_color' => 'Hitam',
        'quantity' => 1,
        'subtotal' => $product->price,
    ]);

    return $order;
}

test('a delivered buyer can submit a verified review', function () {
    $product = Product::factory()->create();
    $order = makeDeliveredOrderForProduct($product);

    Livewire::test('pages::product-detail', ['product' => $product])
        ->set('reviewOrderNumber', $order->order_number)
        ->set('reviewPhoneTail', '5060')
        ->set('reviewRating', 5)
        ->set('reviewComment', 'Bahannya nyaman dan sesuai foto.')
        ->call('submitReview')
        ->assertHasNoErrors()
        ->assertSet('reviewSubmitted', true);

    $review = Review::sole();

    expect($review->order_id)->toBe($order->id)
        ->and($review->reviewer_name)->toBe('Naufal Thafhan')
        ->and($review->is_verified)->toBeTrue()
        ->and($review->rating)->toBe(5);
});

test('verified reviews show the buyer badge on the product page', function () {
    $product = Product::factory()->create();
    $order = makeDeliveredOrderForProduct($product, 'BADGE00001');

    Review::create([
        'product_id' => $product->id,
        'order_id' => $order->id,
        'reviewer_name' => 'Naufal Thafhan',
        'rating' => 4,
        'comment' => 'Mantap.',
    ]);

    Livewire::test('pages::product-detail', ['product' => $product->fresh(['reviews'])])
        ->assertSee('Pembeli terverifikasi')
        ->assertSee('Naufal Thafhan');
});

test('reviews are rejected when the order is not delivered', function () {
    $product = Product::factory()->create();
    $order = makeDeliveredOrderForProduct($product, 'PEND00001');
    $order->update(['status' => 'shipped']);

    Livewire::test('pages::product-detail', ['product' => $product])
        ->set('reviewOrderNumber', $order->order_number)
        ->set('reviewPhoneTail', '5060')
        ->set('reviewRating', 5)
        ->call('submitReview')
        ->assertHasErrors(['reviewOrderNumber']);

    expect(Review::count())->toBe(0);
});

test('reviews are rejected when the phone tail does not match', function () {
    $product = Product::factory()->create();
    $order = makeDeliveredOrderForProduct($product, 'PHONE0001');

    Livewire::test('pages::product-detail', ['product' => $product])
        ->set('reviewOrderNumber', $order->order_number)
        ->set('reviewPhoneTail', '9999')
        ->set('reviewRating', 4)
        ->call('submitReview')
        ->assertHasErrors(['reviewOrderNumber']);

    expect(Review::count())->toBe(0);
});

test('the same delivered order cannot review the same product twice', function () {
    $product = Product::factory()->create();
    $order = makeDeliveredOrderForProduct($product, 'DUP0000001');

    Livewire::test('pages::product-detail', ['product' => $product])
        ->set('reviewOrderNumber', $order->order_number)
        ->set('reviewPhoneTail', '5060')
        ->set('reviewRating', 5)
        ->call('submitReview')
        ->assertHasNoErrors();

    Livewire::test('pages::product-detail', ['product' => $product->fresh()])
        ->set('reviewOrderNumber', $order->order_number)
        ->set('reviewPhoneTail', '5060')
        ->set('reviewRating', 3)
        ->call('submitReview')
        ->assertHasErrors(['reviewOrderNumber']);

    expect(Review::count())->toBe(1);
});
