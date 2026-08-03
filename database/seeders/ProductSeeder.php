<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $kemeja = Category::where('slug', 'kemeja-t-shirt')->first()->id;
        $jaket = Category::where('slug', 'jaket-hoodie')->first()->id;
        $celana = Category::where('slug', 'celana')->first()->id;
        $sepatu = Category::where('slug', 'sepatu')->first()->id;
        $aksesoris = Category::where('slug', 'aksesoris')->first()->id;

        $products = [
            [
                'category_id' => $kemeja,
                'name' => 'Kemeja Flanel Kotak-Kotak',
                'slug' => 'kemeja-flanel-kotak-kotak',
                'description' => 'Kemeja flanel premium dengan motif kotak-kotak klasik. Bahan katun lembut dan nyaman dipakai sehari-hari. Cocok untuk gaya casual dan semi-formal.',
                'price' => 185000,
                'image_url' => 'https://images.unsplash.com/photo-1607345366928-199ea26cfe3e?w=600&q=80',
                'is_best_seller' => true,
                'is_new_arrival' => false,
            ],
            [
                'category_id' => $kemeja,
                'name' => 'Kemeja Putih Slim Fit',
                'slug' => 'kemeja-putih-slim-fit',
                'description' => 'Kemeja putih formal dengan potongan slim fit. Cocok untuk ke kantor atau acara resmi. Bahan katun twill yang adem dan tidak mudah kusut.',
                'price' => 225000,
                'image_url' => 'https://images.unsplash.com/photo-1596755094514-f87e34085b2c?w=600&q=80',
                'is_best_seller' => true,
                'is_new_arrival' => false,
            ],
            [
                'category_id' => $kemeja,
                'name' => 'T-Shirt Premium Cotton',
                'slug' => 't-shirt-premium-cotton',
                'description' => 'Kaos polos premium dengan bahan 100% katun combed 30s. Nyaman dipakai dan tidak mudah melar. Tersedia berbagai warna.',
                'price' => 99000,
                'image_url' => 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?w=600&q=80',
                'is_best_seller' => true,
                'is_new_arrival' => true,
            ],
            [
                'category_id' => $kemeja,
                'name' => 'Kemeja Denim Lengan Panjang',
                'slug' => 'kemeja-denim-lengan-panjang',
                'description' => 'Kemeja denim kekinian yang cocok dipadukan dengan berbagai outfit. Bahan denim premium yang nyaman dan awet.',
                'price' => 265000,
                'image_url' => 'https://images.unsplash.com/photo-1596755094514-f87e34085b2c?w=600&q=80',
                'is_best_seller' => false,
                'is_new_arrival' => true,
            ],
            [
                'category_id' => $jaket,
                'name' => 'Jaket Denim Classic',
                'slug' => 'jaket-denim-classic',
                'description' => 'Jaket denim model classic yang timeless. Cocok untuk gaya casual sehari-hari. Bahan denim tebal dan awet.',
                'price' => 350000,
                'image_url' => 'https://images.unsplash.com/photo-1576995853123-5a10305d93c0?w=600&q=80',
                'is_best_seller' => true,
                'is_new_arrival' => false,
            ],
            [
                'category_id' => $jaket,
                'name' => 'Jaket Bomber Hitam',
                'slug' => 'jaket-bomber-hitam',
                'description' => 'Jaket bomber hitam dengan bahan parasut berkualitas. Ringan, hangat, dan stylish. Dilengkapi resleting yang kuat.',
                'price' => 275000,
                'image_url' => 'https://images.unsplash.com/photo-1591047139829-d91aecb6caea?w=600&q=80',
                'is_best_seller' => false,
                'is_new_arrival' => true,
            ],
            [
                'category_id' => $jaket,
                'name' => 'Hoodie Premium Grey',
                'slug' => 'hoodie-premium-grey',
                'description' => 'Hoodie dengan bahan fleece tebal dan lembut. Hoodie ini cocok untuk santai maupun beraktivitas di luar ruangan.',
                'price' => 229000,
                'image_url' => 'https://images.unsplash.com/photo-1556821840-3a63f95609a7?w=600&q=80',
                'is_best_seller' => true,
                'is_new_arrival' => false,
            ],
            [
                'category_id' => $celana,
                'name' => 'Celana Chinos Slim Fit',
                'slug' => 'celana-chinos-slim-fit',
                'description' => 'Celana chinos slim fit yang nyaman dan stylish. Cocok untuk ke kantor maupun hangout. Bahan stretch yang fleksibel.',
                'price' => 199000,
                'image_url' => 'https://images.unsplash.com/photo-1473966968600-fa801b869a1a?w=600&q=80',
                'is_best_seller' => true,
                'is_new_arrival' => false,
            ],
            [
                'category_id' => $celana,
                'name' => 'Celana Jeans Straight',
                'slug' => 'celana-jeans-straight',
                'description' => 'Celana jeans dengan model straight leg yang klasik. Bahan denim stretch yang nyaman dipakai seharian.',
                'price' => 249000,
                'image_url' => 'https://images.unsplash.com/photo-1541099649105-f69ad21f3246?w=600&q=80',
                'is_best_seller' => false,
                'is_new_arrival' => true,
            ],
            [
                'category_id' => $celana,
                'name' => 'Celana Pendek Casual',
                'slug' => 'celana-pendek-casual',
                'description' => 'Celana pendek casual yang nyaman untuk aktivitas sehari-hari. Bahan katun yang adem dan ringan.',
                'price' => 129000,
                'image_url' => 'https://images.unsplash.com/photo-1594938298603-c8148c4dae35?w=600&q=80',
                'is_best_seller' => false,
                'is_new_arrival' => false,
            ],
            [
                'category_id' => $sepatu,
                'name' => 'Sepatu Sneakers Putih',
                'slug' => 'sepatu-sneakers-putih',
                'description' => 'Sneakers putih casual yang cocok untuk berbagai gaya. Sol karet anti slip dan insole empuk untuk kenyamanan maksimal.',
                'price' => 379000,
                'image_url' => 'https://images.unsplash.com/photo-1549298916-b41d501d3772?w=600&q=80',
                'is_best_seller' => true,
                'is_new_arrival' => false,
            ],
            [
                'category_id' => $sepatu,
                'name' => 'Sepatu Formal Hitam',
                'slug' => 'sepatu-formal-hitam',
                'description' => 'Sepatu formal kulit hitam untuk acara resmi dan kantor. Bahan kulit sintetis berkualitas dengan sol anti slip.',
                'price' => 425000,
                'image_url' => 'https://images.unsplash.com/photo-1606107557195-0e29a4b5b4aa?w=600&q=80',
                'is_best_seller' => false,
                'is_new_arrival' => true,
            ],
            [
                'category_id' => $aksesoris,
                'name' => 'Topi Baseball Hitam',
                'slug' => 'topi-baseball-hitam',
                'description' => 'Topi baseball hitam dengan desain simple dan elegan. Cocok untuk melengkapi gaya casual sehari-hari.',
                'price' => 89000,
                'image_url' => 'https://images.unsplash.com/photo-1521369909029-2afed882baee?w=600&q=80',
                'is_best_seller' => false,
                'is_new_arrival' => false,
            ],
            [
                'category_id' => $aksesoris,
                'name' => 'Tas Selempang Kulit',
                'slug' => 'tas-selempang-kulit',
                'description' => 'Tas selempang kulit sintetis dengan desain modern. Memiliki beberapa kompartemen untuk menyimpan barang-barang penting.',
                'price' => 159000,
                'image_url' => 'https://images.unsplash.com/photo-1547949003-9792a18a2601?w=600&q=80',
                'is_best_seller' => true,
                'is_new_arrival' => true,
            ],
            [
                'category_id' => $aksesoris,
                'name' => 'Jam Tangan Kasual',
                'slug' => 'jam-tangan-kasual',
                'description' => 'Jam tangan dengan desain minimalis yang cocok untuk gaya sehari-hari. Tali jam berbahan stainless steel.',
                'price' => 299000,
                'image_url' => 'https://images.unsplash.com/photo-1524592094714-0f0654e20314?w=600&q=80',
                'is_best_seller' => false,
                'is_new_arrival' => true,
            ],
        ];

        foreach ($products as $product) {
            Product::create($product);
        }
    }
}
