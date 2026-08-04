<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Flash Sale Deadline
    |--------------------------------------------------------------------------
    */

    'flash_sale_ends_at' => env('FLASH_SALE_ENDS_AT'),

    'timezone' => env('SHOP_TIMEZONE', 'Asia/Jakarta'),

    /*
    |--------------------------------------------------------------------------
    | Payment
    |--------------------------------------------------------------------------
    |
    | manual  = transfer/QRIS + unggah bukti (default, cocok produksi awal)
    | fake    = tombol bayar simulasi untuk lokal/demo tanpa Midtrans
    | midtrans = Snap Midtrans (butuh MIDTRANS_SERVER_KEY)
    |
    */

    'payment_driver' => env('PAYMENT_DRIVER', 'manual'),

    'payment' => [
        'expires_after_hours' => (int) env('PAYMENT_EXPIRES_AFTER_HOURS', 24),
        'bank' => [
            'name' => env('SHOP_BANK_NAME', 'Seabank'),
            'account' => env('SHOP_BANK_ACCOUNT', '901550812105'),
            'holder' => env('SHOP_BANK_HOLDER', 'NAUFAL THAFHAN'),
        ],
        'midtrans' => [
            'server_key' => env('MIDTRANS_SERVER_KEY'),
            'client_key' => env('MIDTRANS_CLIENT_KEY'),
            'is_production' => (bool) env('MIDTRANS_IS_PRODUCTION', false),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Shipping
    |--------------------------------------------------------------------------
    |
    | static = tarif flat per kota (perilaku lama)
    | fake   = multi-kurir lokal (simulasi RajaOngkir tanpa API key)
    |
    */

    'shipping_driver' => env('SHIPPING_DRIVER', 'static'),

    'shipping' => [
        'default_weight_grams' => (int) env('SHIPPING_DEFAULT_WEIGHT_GRAMS', 500),
        'city_rates' => [
            'Bandung' => 0, 'Cimahi' => 5000, 'Cianjur' => 10000, 'Garut' => 10000,
            'Tasikmalaya' => 15000, 'Sukabumi' => 15000, 'Ciamis' => 15000,
            'Subang' => 15000, 'Purwakarta' => 15000, 'Indramayu' => 18000,
            'Karawang' => 18000, 'Cirebon' => 20000,
            'Bogor' => 20000, 'Depok' => 22000, 'Bekasi' => 22000,
            'Jakarta' => 25000, 'Tangerang' => 25000, 'Tangerang Selatan' => 25000,
            'Serang' => 28000, 'Cilegon' => 28000, 'Pandeglang' => 28000,
            'Purwokerto' => 28000, 'Cilacap' => 28000, 'Pekalongan' => 30000,
            'Tegal' => 30000, 'Semarang' => 35000, 'Salatiga' => 35000,
            'Magelang' => 35000, 'Kudus' => 37000, 'Solo' => 38000,
            'Yogyakarta' => 38000, 'Sleman' => 38000, 'Bantul' => 38000,
            'Madiun' => 45000, 'Kediri' => 47000, 'Blitar' => 47000,
            'Malang' => 50000, 'Surabaya' => 50000, 'Sidoarjo' => 50000,
            'Jember' => 55000, 'Banyuwangi' => 58000,
            'Bali (Denpasar)' => 58000, 'Mataram (Lombok)' => 65000, 'Kupang' => 85000,
            'Bandar Lampung' => 50000, 'Palembang' => 55000, 'Jambi' => 60000,
            'Padang' => 62000, 'Pekanbaru' => 65000, 'Batam' => 65000,
            'Medan' => 70000, 'Banda Aceh' => 80000,
            'Pontianak' => 68000, 'Banjarmasin' => 72000, 'Balikpapan' => 75000,
            'Samarinda' => 75000, 'Makassar' => 75000, 'Manado' => 82000,
            'Ambon' => 88000, 'Jayapura' => 98000,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Notifikasi (Fase 4)
    |--------------------------------------------------------------------------
    |
    | Email status ke pembeli tanpa akun login. Matikan via .env jika perlu.
    |
    */

    'whatsapp' => env('SHOP_WHATSAPP', '6281324825060'),

    'notifications' => [
        'mail_enabled' => (bool) env('SHOP_MAIL_NOTIFICATIONS', true),
        'customer_status' => (bool) env('SHOP_MAIL_STATUS', true),
        'customer_shipment' => (bool) env('SHOP_MAIL_SHIPMENT', true),
        'customer_payment_proof' => (bool) env('SHOP_MAIL_PAYMENT_PROOF_CUSTOMER', true),
    ],

];
