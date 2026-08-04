<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

test('gallery urls include the cover image plus additional photos capped at five', function () {
    $product = Product::factory()->create([
        'image_url' => 'https://example.test/cover.jpg',
    ]);

    foreach (range(1, 5) as $index) {
        ProductImage::factory()->create([
            'product_id' => $product->id,
            'url' => "https://example.test/extra-{$index}.jpg",
            'sort_order' => $index,
        ]);
    }

    $urls = $product->fresh()->galleryUrls();

    expect($urls)->toHaveCount(5)
        ->and($urls[0])->toBe('https://example.test/cover.jpg')
        ->and($urls)->toContain('https://example.test/extra-1.jpg')
        ->and($urls)->not->toContain('https://example.test/extra-5.jpg');
});

test('the product detail page renders gallery thumbnails', function () {
    $product = Product::factory()->create([
        'image_url' => 'https://example.test/cover.jpg',
        'name' => 'Kemeja Gallery Test',
    ]);

    ProductImage::factory()->create([
        'product_id' => $product->id,
        'url' => 'https://example.test/side.jpg',
        'sort_order' => 0,
    ]);

    Livewire::test('pages::product-detail', ['product' => $product->fresh(['images'])])
        ->assertSee('https://example.test/cover.jpg', false)
        ->assertSee('https://example.test/side.jpg', false);
});

test('admin can upload additional gallery images for a product', function () {
    Storage::fake('public');

    $admin = User::factory()->admin()->create();
    $category = Category::factory()->create();
    $jpeg = (string) base64_decode('/9j/4AAQSkZJRgABAQAAAQABAAD/2wCEAAkGBxAQEBUQEBAVFRUVFRUVFRUVFRUVFRUWFxUXFhUYHSggGBolGxUVITEhJSkrLi4uFx8zODMtNygtLisBCgoKDg0OGxAQGy0lHyUtLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLf/AABEIAAEAAQMBIgACEQEDEQH/xAAXAAADAQAAAAAAAAAAAAAAAAAAAQID/8QAFhEBAQEAAAAAAAAAAAAAAAAAAAER/9oADAMBAAIQAxAAAAGlQf/EABYQAQEBAAAAAAAAAAAAAAAAAAEAAv/aAAgBAQABBQJqf//EABYRAQEBAAAAAAAAAAAAAAAAAAABEf/aAAgBAwEBPwFf/8QAFhEBAQEAAAAAAAAAAAAAAAAAAAER/9oACAECAQE/AV//2Q==');

    $this->actingAs($admin);

    Livewire::test('pages::admin-products')
        ->set('name', 'Jaket Bomber Gallery')
        ->set('image_url', 'https://example.test/cover.jpg')
        ->set('price', 250000)
        ->set('stock', 8)
        ->set('description', 'Jaket bomber dengan foto gallery tambahan untuk toko.')
        ->set('category_id', (string) $category->id)
        ->set('galleryFiles', [
            UploadedFile::fake()->createWithContent('extra-1.jpg', $jpeg),
            UploadedFile::fake()->createWithContent('extra-2.jpg', $jpeg),
        ])
        ->call('save')
        ->assertHasNoErrors();

    $product = Product::where('name', 'Jaket Bomber Gallery')->firstOrFail();

    expect($product->images)->toHaveCount(2)
        ->and($product->galleryUrls())->toHaveCount(3);
});

test('admin trims gallery uploads that would exceed five images per product', function () {
    Storage::fake('public');

    $admin = User::factory()->admin()->create();
    $category = Category::factory()->create();
    $jpeg = (string) base64_decode('/9j/4AAQSkZJRgABAQAAAQABAAD/2wCEAAkGBxAQEBUQEBAVFRUVFRUVFRUVFRUVFRUWFxUXFhUYHSggGBolGxUVITEhJSkrLi4uFx8zODMtNygtLisBCgoKDg0OGxAQGy0lHyUtLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLf/AABEIAAEAAQMBIgACEQEDEQH/xAAXAAADAQAAAAAAAAAAAAAAAAAAAQID/8QAFhEBAQEAAAAAAAAAAAAAAAAAAAER/9oADAMBAAIQAxAAAAGlQf/EABYQAQEBAAAAAAAAAAAAAAAAAAEAAv/aAAgBAQABBQJqf//EABYRAQEBAAAAAAAAAAAAAAAAAAABEf/aAAgBAwEBPwFf/8QAFhEBAQEAAAAAAAAAAAAAAAAAAAER/9oACAECAQE/AV//2Q==');

    $this->actingAs($admin);

    $files = collect(range(1, 5))
        ->map(fn (int $index) => UploadedFile::fake()->createWithContent("extra-{$index}.jpg", $jpeg))
        ->all();

    $component = Livewire::test('pages::admin-products')
        ->set('name', 'Produk Batas Gallery')
        ->set('image_url', 'https://example.test/cover.jpg')
        ->set('price', 150000)
        ->set('stock', 3)
        ->set('description', 'Produk uji batas maksimal lima gambar termasuk cover.')
        ->set('category_id', (string) $category->id)
        ->set('galleryFiles', $files)
        ->assertHasErrors(['galleryFiles']);

    expect($component->get('galleryFiles'))->toHaveCount(Product::MAX_GALLERY_IMAGES - 1);

    $component->call('save')->assertHasNoErrors();

    $product = Product::where('name', 'Produk Batas Gallery')->firstOrFail();

    expect($product->images)->toHaveCount(4)
        ->and($product->galleryUrls())->toHaveCount(5);
});
