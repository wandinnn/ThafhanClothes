<?php

use App\Models\Product;

test('product seo description is truncated from the product description', function () {
    $product = Product::factory()->create([
        'name' => 'Kemeja Oxford Premium',
        'description' => str_repeat('Deskripsi panjang untuk SEO. ', 20),
    ]);

    $seo = $product->seoDescription();

    expect(mb_strlen($seo))->toBeLessThanOrEqual(158)
        ->and($seo)->toContain('Deskripsi panjang');
});

test('product detail page exposes title description and open graph tags', function () {
    $product = Product::factory()->create([
        'name' => 'T-Shirt SEO Check',
        'description' => 'Kaos katun premium nyaman untuk dipakai sehari-hari di kota.',
        'image_url' => 'https://example.test/seo-shirt.jpg',
        'slug' => 't-shirt-seo-check',
    ]);

    $this->get(route('product.detail', $product))
        ->assertOk()
        ->assertSee('T-Shirt SEO Check - ThafhanClothes', false)
        ->assertSee('<meta name="description" content="Kaos katun premium nyaman untuk dipakai sehari-hari di kota."', false)
        ->assertSee('<meta property="og:title"', false)
        ->assertSee('<meta property="og:description" content="Kaos katun premium nyaman untuk dipakai sehari-hari di kota."', false)
        ->assertSee('https://example.test/seo-shirt.jpg', false)
        ->assertSee('<meta property="og:type" content="website">', false);
});
