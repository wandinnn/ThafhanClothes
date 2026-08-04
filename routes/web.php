<?php

use App\Http\Middleware\AdminMiddleware;
use App\Models\Order;
use App\Models\Product;
use App\Support\Cart;
use App\Support\ProductOptions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

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
Route::livewire('/wishlist', 'pages::wishlist')->name('wishlist');

// Opsi ukuran/warna untuk modal add-to-cart (harus sama dengan halaman detail).
Route::get('/products/{product}/options', function (Product $product) {
    $product->loadMissing(['category', 'variants']);

    $palette = collect(ProductOptions::colorsWithHex())->keyBy('name');

    return response()->json([
        'sizes' => $product->availableSizes(),
        'colors' => collect($product->availableColors())
            ->map(fn (string $name): array => [
                'name' => $name,
                'hex' => $palette[$name]['hex'] ?? '#9ca3af',
            ])
            ->values(),
        'variants' => $product->hasVariants() ? $product->variantStockMap() : null,
    ]);
})->name('product.options');

// AJAX add to cart (route key = slug, bukan id)
Route::post('/cart/add/{product}', function (Request $request, Product $product) {
    $result = Cart::add(
        $product,
        max(1, (int) $request->input('quantity', 1)),
        (string) $request->input('size', ''),
        (string) $request->input('color', ''),
    );

    return response()->json($result, $result['success'] ? 200 : 422);
})->name('cart.add');

// Admin routes
Route::prefix('admin')->name('admin.')->group(function () {
    Route::livewire('/login', 'pages::admin-login')->name('login')->middleware('guest');

    Route::middleware([AdminMiddleware::class])->group(function () {
        Route::livewire('/dashboard', 'pages::admin-dashboard')->name('dashboard');
        Route::livewire('/products', 'pages::admin-products')->name('products');
        Route::livewire('/categories', 'pages::admin-categories')->name('categories');
        Route::livewire('/coupons', 'pages::admin-coupons')->name('coupons');
        Route::livewire('/orders', 'pages::admin-orders')->name('orders');

        // Bukti bayar hanya bisa dibuka oleh admin, bukan lewat URL publik tebakable.
        Route::get('/payment-proofs/{order}', function (Order $order) {
            abort_unless($order->payment_proof_path, 404);
            abort_unless(Storage::disk('public')->exists($order->payment_proof_path), 404);

            return Storage::disk('public')->response($order->payment_proof_path);
        })->name('payment-proof');

        Route::post('/logout', function () {
            auth()->logout();
            session()->invalidate();
            session()->regenerateToken();

            return redirect()->route('admin.login');
        })->name('logout');
    });
});
