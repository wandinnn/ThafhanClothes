<?php

use App\Models\Category;
use App\Models\Product;
use App\Support\ProductSearch;
use Livewire\Livewire;

beforeEach(function () {
    $this->celana = Category::factory()->create(['name' => 'Celana', 'slug' => 'celana']);
    $this->jaket = Category::factory()->create(['name' => 'Jaket & Hoodie', 'slug' => 'jaket-hoodie']);

    $this->jeans = Product::factory()->create([
        'category_id' => $this->celana->id,
        'name' => 'Celana Jeans Straight',
        'slug' => 'celana-jeans-straight',
        'description' => 'Celana jeans potongan straight yang nyaman.',
    ]);

    $this->bomber = Product::factory()->create([
        'category_id' => $this->jaket->id,
        'name' => 'Jaket Bomber Hitam',
        'slug' => 'jaket-bomber-hitam',
        'description' => 'Jaket bomber hitam dengan desain modern.',
    ]);

    Product::factory()->create([
        'category_id' => Category::factory()->create(['name' => 'Aksesoris', 'slug' => 'aksesoris'])->id,
        'name' => 'Topi Baseball Hitam',
        'slug' => 'topi-baseball-hitam',
    ]);
});

test('typo clana still finds celana products', function () {
    $results = ProductSearch::search('clana');

    expect($results->first()->id)->toBe($this->jeans->id)
        ->and($results->pluck('id'))->toContain($this->jeans->id);
});

test('abbreviation jkt still finds jaket products', function () {
    $results = ProductSearch::search('jkt');

    expect($results->pluck('id'))->toContain($this->bomber->id)
        ->and($results->first()->id)->toBe($this->bomber->id);
});

test('search bar suggestions include typo tolerant matches', function () {
    Livewire::test('search-bar')
        ->set('query', 'clana')
        ->assertSet('open', true)
        ->assertSee('Celana Jeans Straight');

    Livewire::test('search-bar')
        ->set('query', 'jkt')
        ->assertSet('open', true)
        ->assertSee('Jaket Bomber Hitam');
});

test('products page lists fuzzy matches for typos', function () {
    Livewire::test('pages::products')
        ->set('search', 'clana')
        ->assertSee('Celana Jeans Straight')
        ->assertDontSee('Topi Baseball Hitam');

    Livewire::test('pages::products')
        ->set('search', 'jkt')
        ->assertSee('Jaket Bomber Hitam');
});

test('exact product name still ranks first', function () {
    $results = ProductSearch::search('Jaket Bomber Hitam');

    expect($results->first()->id)->toBe($this->bomber->id);
});
