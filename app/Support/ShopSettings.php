<?php

namespace App\Support;

use App\Models\ShopSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

/**
 * Pengaturan toko yang bisa diubah dari panel admin.
 * Nilai DB mengoverride config/.env; jika kosong, pakai default config.
 */
class ShopSettings
{
    public const CACHE_KEY = 'shop_settings.all';

    public const KEY_BANK_NAME = 'bank_name';

    public const KEY_BANK_ACCOUNT = 'bank_account';

    public const KEY_BANK_HOLDER = 'bank_holder';

    public const KEY_WHATSAPP = 'whatsapp';

    public const KEY_FLASH_SALE_ENDS_AT = 'flash_sale_ends_at';

    public const KEY_MAIL_ENABLED = 'mail_notifications_enabled';

    public const KEY_MAIL_STATUS = 'mail_status_enabled';

    public const KEY_MAIL_SHIPMENT = 'mail_shipment_enabled';

    public const KEY_MAIL_PAYMENT_PROOF = 'mail_payment_proof_customer_enabled';

    public const KEY_QRIS_PATH = 'qris_image_path';

    public const KEY_FAQ_ITEMS = 'faq_items';

    public const KEY_ABOUT_TITLE = 'about_title';

    public const KEY_ABOUT_BODY = 'about_body';

    public const KEY_PAYMENT_DRIVER = 'payment_driver';

    /**
     * @return array<string, string|null>
     */
    public static function all(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function (): array {
            return ShopSetting::query()
                ->pluck('value', 'key')
                ->all();
        });
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        $stored = self::all()[$key] ?? null;

        if ($stored !== null && $stored !== '') {
            return $stored;
        }

        return $default;
    }

    public static function set(string $key, ?string $value): void
    {
        ShopSetting::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value],
        );

        self::forgetCache();
    }

    /**
     * @param  array<string, string|null>  $values
     */
    public static function putMany(array $values): void
    {
        foreach ($values as $key => $value) {
            ShopSetting::query()->updateOrCreate(
                ['key' => $key],
                ['value' => $value],
            );
        }

        self::forgetCache();
    }

    public static function forgetCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * @return array{name: string, account: string, holder: string}
     */
    public static function bank(): array
    {
        $defaults = config('shop.payment.bank', []);

        return [
            'name' => (string) (self::get(self::KEY_BANK_NAME, $defaults['name'] ?? 'Seabank') ?? 'Seabank'),
            'account' => (string) (self::get(self::KEY_BANK_ACCOUNT, $defaults['account'] ?? '') ?? ''),
            'holder' => (string) (self::get(self::KEY_BANK_HOLDER, $defaults['holder'] ?? '') ?? ''),
        ];
    }

    public static function whatsapp(): string
    {
        return (string) (self::get(self::KEY_WHATSAPP, (string) config('shop.whatsapp', '6281324825060')) ?? '6281324825060');
    }

    /**
     * Driver pembayaran aktif: manual | fake | midtrans.
     * Nilai admin mengoverride .env; kunci Midtrans tetap dari .env.
     */
    public static function paymentDriver(): string
    {
        $driver = (string) (self::get(self::KEY_PAYMENT_DRIVER, (string) config('shop.payment_driver', 'manual')) ?? 'manual');

        return in_array($driver, ['manual', 'fake', 'midtrans'], true) ? $driver : 'manual';
    }

    public static function flashSaleEndsAt(): ?string
    {
        return self::get(self::KEY_FLASH_SALE_ENDS_AT, config('shop.flash_sale_ends_at'));
    }

    public static function mailEnabled(): bool
    {
        return self::bool(self::KEY_MAIL_ENABLED, (bool) config('shop.notifications.mail_enabled', true));
    }

    public static function mailStatusEnabled(): bool
    {
        return self::bool(self::KEY_MAIL_STATUS, (bool) config('shop.notifications.customer_status', true));
    }

    public static function mailShipmentEnabled(): bool
    {
        return self::bool(self::KEY_MAIL_SHIPMENT, (bool) config('shop.notifications.customer_shipment', true));
    }

    public static function mailPaymentProofCustomerEnabled(): bool
    {
        return self::bool(self::KEY_MAIL_PAYMENT_PROOF, (bool) config('shop.notifications.customer_payment_proof', true));
    }

    public static function qrisPath(): ?string
    {
        return self::get(self::KEY_QRIS_PATH);
    }

    public static function qrisUrl(): ?string
    {
        $path = self::qrisPath();

        if (filled($path) && Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->url($path);
        }

        if (file_exists(public_path('images/qris.png'))) {
            return asset('images/qris.png');
        }

        return null;
    }

    /**
     * @return list<array{q: string, a: string}>
     */
    public static function faqItems(): array
    {
        $raw = self::get(self::KEY_FAQ_ITEMS);

        if (filled($raw)) {
            $decoded = json_decode($raw, true);

            if (is_array($decoded)) {
                return collect($decoded)
                    ->filter(fn ($row): bool => is_array($row) && filled($row['q'] ?? null) && filled($row['a'] ?? null))
                    ->map(fn (array $row): array => [
                        'q' => (string) $row['q'],
                        'a' => (string) $row['a'],
                    ])
                    ->values()
                    ->all();
            }
        }

        return self::defaultFaqItems();
    }

    /**
     * @return list<array{q: string, a: string}>
     */
    public static function defaultFaqItems(): array
    {
        return [
            [
                'q' => 'Barangnya baru atau preloved?',
                'a' => 'Untuk sekarang, fokusnya fashion preloved / second yang kondisinya masih oke banget. Nanti ke depan bakal ada barang baru juga — jadi bisa campur: baru + preloved dalam satu tempat.',
            ],
            [
                'q' => 'Kualitas preloved-nya gimana?',
                'a' => 'Setiap item dicek dulu sebelum naik ke etalase. Intinya: masih layak pakai, bersih, dan detail penting biasanya ada di deskripsi. Masih ragu soal size atau kondisi? Chat WA aja sebelum checkout, biar ga salah pilih.',
            ],
            [
                'q' => 'Cara order & bayarnya gimana?',
                'a' => 'Pilih barang (ukuran & warna kalau ada) → masukin ke keranjang → checkout → bayar transfer atau QRIS → upload bukti bayar → konfirmasi. Di halaman konfirmasi, unduh invoice lalu kirim ke admin via WA bersama bukti bayar. Setelah kami cek, pesanan langsung diproses.',
            ],
            [
                'q' => 'Pengiriman & lacak pesanan?',
                'a' => 'Isi alamat lengkap pas checkout. Area Bandung biasanya gratis ongkir; luar kota ikut tarif yang muncul di checkout. Habis dikirim, cek statusnya di menu Lacak Pesanan pakai kode pesananmu.',
            ],
            [
                'q' => 'Bisa tukar atau retur ga?',
                'a' => 'Karena mayoritas preloved dan stoknya limited, tukar/retur agak terbatas. Tapi kalau barangnya beda jauh dari deskripsi atau foto, langsung chat WA ya — kami bantu carikan solusi yang fair.',
            ],
        ];
    }

    public static function aboutTitle(): string
    {
        return (string) (self::get(self::KEY_ABOUT_TITLE, 'ThafhanClothes: preloved kece, harga masuk akal') ?? 'ThafhanClothes: preloved kece, harga masuk akal');
    }

    public static function aboutBody(): string
    {
        $default = "in this economy, ThafhanClothes lahir buat kamu yang mau fashion tetap stylish tapi harganya tetep bersahabat.\nbuat sekarang, fokus saya jual barang fashion preloved / second yang kondisinya masih 99% masih bagus banget — dipilih, dicek, terus dipajang biar siap pakai.\n\nbuat kedepannya, etalase bakal terus nambah barang barang baru ada ada brand new juga preloved yang masih ready.\nintinya satu tempat buat hunting outfit yang keren — 100% real pict, proses belanja gampang, dan kalau butuh bantuan tinggal chat WA aja.";

        return (string) (self::get(self::KEY_ABOUT_BODY, $default) ?? $default);
    }

    /**
     * Normalisasi nomor telepon Indonesia ke format WhatsApp (62…).
     */
    public static function whatsappDigitsFromPhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if ($digits === '') {
            return self::whatsapp();
        }

        if (str_starts_with($digits, '0')) {
            return '62'.substr($digits, 1);
        }

        if (str_starts_with($digits, '62')) {
            return $digits;
        }

        return $digits;
    }

    private static function bool(string $key, bool $default): bool
    {
        $value = self::get($key);

        if ($value === null) {
            return $default;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}
