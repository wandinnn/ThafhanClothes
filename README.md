# ThafhanClothes

Toko fashion online single-brand berbasis **Laravel 13 + Livewire 4**.  
Pembeli belanja tanpa akun (guest checkout). Admin login terpisah untuk kelola produk, pesanan, dan pengaturan toko.

**Repo:** https://github.com/wandinnn/ThafhanClothes

---

## Fitur utama

### Storefront (pembeli)
- Beranda: hero, flash sale, kategori, new arrivals
- Katalog produk + pencarian + filter
- Detail produk: gallery (maks. 5 foto), varian ukuran/warna & stok, wishlist, review terverifikasi, chat WhatsApp
- Keranjang (session, line-item per ukuran/warna)
- Checkout: kupon, ongkir per kota, email & alamat
- Pembayaran manual: transfer SeaBank + QRIS + upload bukti
- Invoice, detail pesanan, lacak pesanan (nomor order + 4 digit terakhir HP)
- Wishlist, FAQ, Tentang Kami
- Notifikasi email status pesanan (opsional)

### Admin (`/admin`)
- Login admin (throttle)
- Dashboard & ringkasan laporan
- CRUD produk (varian, gallery, duplikat produk)
- CRUD kategori & kupon
- Pesanan: update status, nomor resi/kurir, chat WA ke pembeli, lihat bukti bayar
- Pengaturan toko: rekening bank, WhatsApp, flash sale, QRIS, FAQ, About, toggle email, driver pembayaran

> **Catatan:** tidak ada akun login pembeli. Riwayat pesanan lewat **Lacak Pesanan**.

---

## Stack

| Layer | Teknologi |
|-------|-----------|
| Backend | PHP 8.3+, Laravel 13 |
| UI dinamis | Livewire 4 (page components) |
| CSS | Tailwind CSS 4 |
| JS ringan | Alpine.js |
| DB default | SQLite (bisa diganti MySQL/PostgreSQL) |
| Test | Pest 4 |
| Search (opsional) | Meilisearch |
| Bayar (opsional) | Midtrans Snap |

---

## Persyaratan

- PHP 8.3+ (disarankan 8.4) dengan ekstensi Laravel standar
- Composer
- Node.js 18+ & npm
- SQLite (default) atau MySQL/PostgreSQL

---

## Instalasi lokal

```bash
git clone https://github.com/wandinnn/ThafhanClothes.git
cd ThafhanClothes

composer install
copy .env.example .env          # Windows
# cp .env.example .env          # macOS/Linux

php artisan key:generate
php artisan migrate
php artisan storage:link

# Isi ADMIN_PASSWORD di .env, lalu:
php artisan db:seed

npm install
npm run build
php artisan serve
```

Atau ringkas (setelah clone & `.env` siap):

```bash
composer run setup
php artisan db:seed
php artisan serve
```

Buka: http://127.0.0.1:8000

### Development frontend (hot reload)

```bash
composer run dev
# atau: npm run dev  (di terminal terpisah dari php artisan serve)
```

---

## Akun admin

1. Di `.env` set:
   ```env
   ADMIN_EMAIL=admin@thafhanclothes.test
   ADMIN_PASSWORD=password-aman-anda
   ```
2. Jalankan: `php artisan db:seed --class=AdminUserSeeder`  
   (mengubah `.env` saja **tidak** mengganti password di DB kecuali seeder dijalankan ulang)
3. Login: **http://127.0.0.1:8000/admin** (redirect ke login