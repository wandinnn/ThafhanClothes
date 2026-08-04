<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Flash Sale Deadline
    |--------------------------------------------------------------------------
    |
    | Countdown di beranda memakai timestamp tetap. Isi FLASH_SALE_ENDS_AT
    | dengan format ISO 8601 (contoh: 2026-08-10 23:59:59). Kalau kosong,
    | deadline otomatis jatuh pada pukul 23:59:59 hari ini (Asia/Jakarta).
    |
    */

    'flash_sale_ends_at' => env('FLASH_SALE_ENDS_AT'),

    'timezone' => env('SHOP_TIMEZONE', 'Asia/Jakarta'),

];
