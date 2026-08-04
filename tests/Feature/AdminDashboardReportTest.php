<?php

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->admin()->create());
});

test('dashboard shows revenue periods and top products', function () {
    $product = Product::factory()->stock(20)->create(['name' => 'Jaket Denim Premium', 'price' => 150000]);

    $order = Order::create([
        'order_number' => 'RPT'.fake()->unique()->numerify('######'),
        'customer_name' => 'Naufal',
        'customer_email' => 'n@example.com',
        'customer_phone' => '081324825060',
        'city' => 'Bandung',
        'address' => 'Jl Merdeka',
        'subtotal' => 300000,
        'discount' => 0,
        'shipping_cost' => 0,
        'total' => 300000,
        'status' => 'confirmed',
    ]);

    OrderItem::create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'product_name' => $product->name,
        'product_price' => 150000,
        'quantity' => 2,
        'subtotal' => 300000,
    ]);

    Livewire::test('pages::admin-dashboard')
        ->assertSee('Omzet Hari Ini')
        ->assertSee('Omzet Minggu Ini')
        ->assertSee('Produk Terlaris')
        ->assertSee('Jaket Denim Premium')
        ->assertSee('Rp300.000');
});
