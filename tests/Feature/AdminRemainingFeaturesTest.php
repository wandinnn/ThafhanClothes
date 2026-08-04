<?php

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Support\ShopSettings;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    ShopSettings::forgetCache();
    $this->actingAs(User::factory()->admin()->create());
});

test('faq page shows green whatsapp button and editable faqs from settings', function () {
    ShopSettings::putMany([
        ShopSettings::KEY_FAQ_ITEMS => json_encode([
            ['q' => 'Apa itu Thafhan?', 'a' => 'Toko preloved kece.'],
        ], JSON_UNESCAPED_UNICODE),
        ShopSettings::KEY_WHATSAPP => '6281999888777',
    ]);

    $this->get(route('faq'))
        ->assertSuccessful()
        ->assertSee('Apa itu Thafhan?')
        ->assertSee('Toko preloved kece.')
        ->assertSee('wa.me/6281999888777')
        ->assertSee('bg-green-500');
});

test('about page uses content from shop settings', function () {
    ShopSettings::putMany([
        ShopSettings::KEY_ABOUT_TITLE => 'Judul About Custom',
        ShopSettings::KEY_ABOUT_BODY => "Paragraf satu.\n\nParagraf dua.",
    ]);

    $this->get(route('about'))
        ->assertSuccessful()
        ->assertSee('Judul About Custom')
        ->assertSee('Paragraf satu.')
        ->assertSee('Paragraf dua.');
});

test('admin can upload qris image and payment page shows it', function () {
    Storage::fake('public');

    Livewire::test('pages::admin-settings')
        ->set('qrisFile', UploadedFile::fake()->create('qris-gopay.jpg', 120, 'image/jpeg'))
        ->call('save')
        ->assertHasNoErrors();

    expect(ShopSettings::qrisPath())->not->toBeNull()
        ->and(Storage::disk('public')->exists(ShopSettings::qrisPath()))->toBeTrue();
});

test('admin can duplicate a product with variants', function () {
    $product = Product::factory()->create(['name' => 'Hoodie Hitam']);
    ProductVariant::factory()->create([
        'product_id' => $product->id,
        'size' => 'L',
        'color' => 'Hitam',
        'stock' => 3,
    ]);

    Livewire::test('pages::admin-products')
        ->call('duplicate', $product->slug);

    expect(Product::where('name', 'Hoodie Hitam (Salinan)')->exists())->toBeTrue();

    $clone = Product::where('name', 'Hoodie Hitam (Salinan)')->first();

    expect($clone->variants)->toHaveCount(1)
        ->and($clone->variants->first()->size)->toBe('L');
});

test('admin order detail shows chat wa buyer link', function () {
    $order = Order::create([
        'order_number' => 'WA'.fake()->unique()->numerify('######'),
        'customer_name' => 'Budi',
        'customer_email' => 'budi@example.com',
        'customer_phone' => '081234567890',
        'city' => 'Bandung',
        'address' => 'Jl Test',
        'subtotal' => 100000,
        'discount' => 0,
        'shipping_cost' => 0,
        'total' => 100000,
        'status' => 'processing',
    ]);

    Livewire::test('pages::admin-orders')
        ->call('viewOrder', $order->id)
        ->assertSee('Chat WA Pembeli')
        ->assertSee('wa.me/6281234567890');
});
