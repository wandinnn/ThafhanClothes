<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->admin()->create());
    $this->category = Category::factory()->create();
});

function adminProductForm(int $categoryId, string $name = 'Kemeja Flanel Kotak'): Testable
{
    return Livewire::test('pages::admin-products')
        ->set('name', $name)
        ->set('image_url', 'https://example.test/kemeja.jpg')
        ->set('price', 150000)
        ->set('stock', 12)
        ->set('description', 'Kemeja flanel bahan katun yang nyaman dipakai seharian.')
        ->set('category_id', (string) $categoryId);
}

test('a new product stores its stock and flash sale flag', function () {
    adminProductForm($this->category->id)
        ->set('original_price', 200000)
        ->set('is_flash_sale', true)
        ->call('save');

    $product = Product::sole();

    expect($product->stock)->toBe(12)
        ->and($product->original_price)->toBe(200000)
        ->and($product->is_flash_sale)->toBeTrue();
});

test('two products with the same name get different slugs', function () {
    adminProductForm($this->category->id)->call('save');
    adminProductForm($this->category->id)->call('save');

    expect(Product::pluck('slug')->all())->toBe(['kemeja-flanel-kotak', 'kemeja-flanel-kotak-2']);
});

test('editing a product keeps its own slug', function () {
    $product = Product::factory()->create([
        'name' => 'Kemeja Flanel Kotak',
        'slug' => 'kemeja-flanel-kotak',
        'category_id' => $this->category->id,
    ]);

    Livewire::test('pages::admin-products')
        ->call('edit', $product->slug)
        ->set('price', 175000)
        ->call('save');

    expect($product->fresh()->slug)->toBe('kemeja-flanel-kotak')
        ->and($product->fresh()->price)->toBe(175000);
});

test('a crossed out price must be higher than the selling price', function () {
    adminProductForm($this->category->id)
        ->set('original_price', 100000)
        ->call('save')
        ->assertHasErrors(['original_price' => 'gt']);

    expect(Product::count())->toBe(0);
});

test('a negative stock is rejected', function () {
    adminProductForm($this->category->id)
        ->set('stock', -5)
        ->call('save')
        ->assertHasErrors(['stock' => 'min']);

    expect(Product::count())->toBe(0);
});

test('the product list shows every product when the search box is empty', function () {
    Product::factory()->count(3)->create(['category_id' => $this->category->id]);

    Livewire::test('pages::admin-products')->assertSee(Product::first()->name);
});

test('two category names that slugify the same get different slugs', function () {
    Livewire::test('pages::admin-categories')->set('name', 'Kemeja Pria')->call('save');
    Livewire::test('pages::admin-categories')->set('name', 'Kemeja / Pria')->call('save');

    expect(Category::where('slug', 'kemeja-pria')->exists())->toBeTrue()
        ->and(Category::where('slug', 'kemeja-pria-2')->exists())->toBeTrue();
});
