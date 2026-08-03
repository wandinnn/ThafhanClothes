<?php

use App\Http\Middleware\AdminMiddleware;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::livewire('/', 'pages::home')->name('home');
Route::livewire('/products', 'pages::products')->name('products');
Route::livewire('/products/{product}', 'pages::product-detail')->name('product.detail');
Route::livewire('/cart', 'pages::cart')->name('cart');
Route::livewire('/checkout', 'pages::checkout')->name('checkout');
Route::livewire('/payment/{order}', 'pages::payment')->name('payment');
Route::livewire('/order/success/{order}', 'pages::order-success')->name('order.success');
Route::livewire('/order/{order}', 'pages::order-detail')->name('order.detail');
Route::livewire('/about', 'pages::about')->name('about');
Route::livewire('/cara-belanja', 'pages::how-to-shop')->name('how-to-shop');
Route::livewire('/lacak-pesanan', 'pages::track-order')->name('track.order');

// AJAX add to cart (route key = slug, bukan id)
Route::post('/cart/add/{product}', function (Request $request, Product $product) {
    $cart = session('cart', []);
    $qty = max(1, (int) $request->input('quantity', 1));
    $cart[$product->id] = ($cart[$product->id] ?? 0) + $qty;
    session(['cart' => $cart]);

    $meta = session('cart_meta', []);
    $color = $request->input('color');
    $size = $request->input('size');
    if ($color || $size) {
        $meta[$product->id] = ['color' => $color, 'size' => $size];
        session(['cart_meta' => $meta]);
    }

    return response()->json(['success' => true, 'count' => count($cart)]);
})->name('cart.add');

// Admin routes
Route::prefix('admin')->name('admin.')->group(function () {
    Route::livewire('/login', 'pages::admin-login')->name('login')->middleware('guest');

    Route::middleware([AdminMiddleware::class])->group(function () {
        Route::livewire('/dashboard', 'pages::admin-dashboard')->name('dashboard');
        Route::livewire('/products', 'pages::admin-products')->name('products');
        Route::livewire('/categories', 'pages::admin-categories')->name('categories');
        Route::livewire('/orders', 'pages::admin-orders')->name('orders');

        Route::post('/logout', function () {
            auth()->logout();
            session()->invalidate();
            session()->regenerateToken();

            return redirect()->route('admin.login');
        })->name('logout');
    });
});
