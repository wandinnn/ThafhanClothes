<?php

namespace Database\Seeders;

use App\Models\Coupon;
use Illuminate\Database\Seeder;

class CouponSeeder extends Seeder
{
    public function run(): void
    {
        Coupon::insert([
            [
                'code' => 'THAFHAN10',
                'type' => 'percent',
                'value' => 10,
                'min_order' => 100000,
                'description' => 'Diskon 10% min. belanja Rp100.000',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'GRATIS50K',
                'type' => 'fixed',
                'value' => 50000,
                'min_order' => 300000,
                'description' => 'Potongan Rp50.000 min. belanja Rp300.000',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
