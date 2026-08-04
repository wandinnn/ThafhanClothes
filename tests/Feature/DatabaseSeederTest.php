<?php

use App\Models\Category;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Hash;

test('the seeders fill categories, products, coupons and the admin account', function () {
    $this->seed(DatabaseSeeder::class);

    expect(Category::count())->toBe(5)
        ->and(Product::count())->toBeGreaterThan(0)
        ->and(Coupon::count())->toBe(2)
        ->and(User::where('is_admin', true)->count())->toBe(1);
});

test('the seeded products all have stock', function () {
    $this->seed(DatabaseSeeder::class);

    expect(Product::where('stock', '<=', 0)->count())->toBe(0)
        ->and(Product::where('is_flash_sale', true)->count())->toBe(4);
});

test('every flash sale product has a crossed out price', function () {
    $this->seed(DatabaseSeeder::class);

    expect(Product::where('is_flash_sale', true)->whereNull('original_price')->count())->toBe(0);
});

test('running the seeders twice does not duplicate anything', function () {
    $this->seed(DatabaseSeeder::class);
    $productCount = Product::count();

    $this->seed(DatabaseSeeder::class);

    expect(Category::count())->toBe(5)
        ->and(Product::count())->toBe($productCount)
        ->and(Coupon::count())->toBe(2)
        ->and(User::count())->toBe(1);
});

test('the admin password does not fall back to a well known value', function () {
    config(['app.admin_password' => null]);

    $this->seed(DatabaseSeeder::class);

    $admin = User::where('is_admin', true)->sole();

    expect(Hash::check('password', $admin->password))->toBeFalse();
});
