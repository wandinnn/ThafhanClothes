<?php

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Support\Cart;

beforeEach(function () {
    $this->category = Category::factory()->create(['name' => 'Kemeja', 'slug' => 'kemeja']);
    $this->product = Product::factory()->stock(10)->create([
        'category_id' => $this->category->id,
        'name' => 'Kemeja Flanel Kotak',
        'slug' => 'kemeja-flanel-kotak',
    ]);

    $this->order = Order::create([
        'order_number' => 'SMOKE00001',
        'customer_name' => 'Naufal Thafhan',
        'customer_phone' => '081324825060',
        'city' => 'Bandung',
        'address' => 'Jalan Merdeka Nomor 10',
        'subtotal' => 150000,
        'discount' => 0,
        'shipping_cost' => 0,
        'total' => 150000,
        'status' => 'pending_payment',
    ]);

    OrderItem::create([
        'order_id' => $this->order->id,
        'product_id' => $this->product->id,
        'product_name' => $this->product->name,
        'product_price' => 75000,
        'quantity' => 2,
        'subtotal' => 150000,
    ]);
});

test('every storefront page renders', function (string $route) {
    $this->get($route)->assertSuccessful();
})->with(fn () => [
    'beranda' => '/',
    'produk' => '/products',
    'detail produk' => '/products/kemeja-flanel-kotak',
    'keranjang' => '/cart',
    'checkout' => '/checkout',
    'wishlist' => '/wishlist',
    'pembayaran' => '/payment/SMOKE00001',
    'sukses' => '/order/success/SMOKE00001',
    'detail pesanan' => '/order/SMOKE00001',
    'tentang' => '/about',
    'faq' => '/faq',
    'lacak pesanan' => '/lacak-pesanan',
]);

test('the checkout page renders with items in the cart', function () {
    session(['cart' => [Cart::lineKey($this->product->id, 'M', 'Hitam') => 2]]);

    $this->get('/checkout')
        ->assertSuccessful()
        ->assertSee('Kemeja Flanel Kotak');
});

test('the cart page renders with items in the cart', function () {
    session(['cart' => [Cart::lineKey($this->product->id, 'M', 'Hitam') => 2]]);

    $this->get('/cart')
        ->assertSuccessful()
        ->assertSee('Kemeja Flanel Kotak')
        ->assertSee('Ukuran M');
});

test('every admin page renders for an administrator', function (string $route) {
    $this->actingAs(User::factory()->admin()->create())
        ->get($route)
        ->assertSuccessful();
})->with([
    'dasbor' => '/admin/dashboard',
    'produk' => '/admin/products',
    'kategori' => '/admin/categories',
    'kupon' => '/admin/coupons',
    'pesanan' => '/admin/orders',
]);

test('the admin area is closed to guests', function () {
    $this->get('/admin/orders')->assertRedirect(route('admin.login'));
});

test('visiting /admin redirects guests to the login page', function () {
    $this->get('/admin')->assertRedirect(route('admin.login'));
});

test('visiting /admin redirects administrators to the dashboard', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get('/admin')
        ->assertRedirect(route('admin.dashboard'));
});

test('the admin area is closed to a non admin user', function () {
    $this->actingAs(User::factory()->create())
        ->get('/admin/products')
        ->assertRedirect(route('admin.login'));
});
