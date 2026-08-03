<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Kemeja & T-Shirt', 'slug' => 'kemeja-t-shirt'],
            ['name' => 'Jaket & Hoodie', 'slug' => 'jaket-hoodie'],
            ['name' => 'Celana', 'slug' => 'celana'],
            ['name' => 'Sepatu', 'slug' => 'sepatu'],
            ['name' => 'Aksesoris', 'slug' => 'aksesoris'],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}
