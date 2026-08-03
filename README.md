# yStore

> **⚠️ PROYEK INI BELUM SIAP DEPLOY**
>
> Masih terdapat beberapa bug dan fitur yang belum sempurna. Jangan gunakan untuk production.

## Source Code Siap Jadi

Kunjungi **[https://Thafhanclothes.web.id](https://Thafhanclothes.web.id)** untuk mendapatkan source code versi lengkap dan siap deploy.

---

## Tentang Proyek

yStore adalah aplikasi toko pakaian pria (fashion pria) berbasis Laravel + Livewire. Proyek ini dibuat sebagai demonstrasi pembuatan website menggunakan AI prompt.

### Teknologi

- Laravel 13
- Livewire 4 (Single File Components)
- TailwindCSS 4
- Alpine.js
- SQLite

### Bug yang Diketahui

- [ ] Angka badge keranjang di navbar kadang tidak sinkron
- [ ] Slug produk bisa duplikat saat edit nama
- [ ] Upload gambar masih menggunakan URL eksternal (belum ada file upload)
- [ ] Belum ada validasi slug unik di sisi server saat update produk
- [ ] Session keranjang hilang saat browser ditutup (tidak persist ke database)
- [ ] Halaman 404 tidak menampilkan halaman custom admin untuk rute admin
- [ ] Belum ada pagination di halaman kategori admin
- [ ] Tidak ada konfirmasi logout di admin

---

## Prompt untuk Menghasilkan Website Ini

### Prompt Pertama
```txt
Buatkan web toko online sederhana dengan ketentuan berikut:

Nama toko: yStore
Tema: toko pakaian pria

Halaman:
- Beranda:
  - Hero section
  - Section best seller
  - Section kategori
  - Banner info promo (belanja di atas Rp200.000 gratis ongkir)
  - Section new arrival
  - Tombol WhatsApp fixed di pojok kanan bawah di semua halaman
- Products: tampilkan semua produk dalam bentuk grid card (nama, foto, harga,
  tombol tambah ke cart), bisa filter berdasarkan kategori
- Detail produk: foto produk, nama, deskripsi, harga, kategori, tombol tambah ke cart
- Cart: daftar produk yang dipilih, jumlah, total harga, dan tombol checkout
- Halaman 404 yang branded sesuai tampilan toko
- Semua halaman memiliki navbar dan footer
- Navbar menampilkan icon cart beserta jumlah item

Ketentuan:
- Produk memiliki kategori (buat minimal 3 kategori pakaian pria)
- Produk memiliki field is_best_seller dan is_new_arrival
- Proses checkout mengarah ke WhatsApp dengan pesan otomatis berisi
  daftar produk dan total harga
- Nomor WA yang digunakan: 6281324825060 (ganti sesuai nomormu)
- Cart menggunakan Laravel Session
- Gunakan seeder untuk mengisi data produk contoh minimal 12 produk
  dengan kategori yang bervariasi, is_best_seller dan is_new_arrival
  yang bervariasi
- Gunakan URL gambar dari Unsplash (format: https://images.unsplash.com/photo-ID
  ?w=600&q=80) pastikan setiap URL gambar valid dan bisa diakses
- Gunakan Heroicons untuk kebutuhan icon
- Gunakan font Inter dari Google Fonts
- Format harga dalam Rupiah (contoh: Rp150.000)
- Tambahkan animasi transisi yang subtle
- Semua halaman responsif dan mobile friendly

Color palette:
- Background: #EEEEEE
- Primary: #6FCF97
- Secondary: #2FA084
- Accent: #1F6F5F

```

### Prompt Kedua
```txt
Buatkan halaman admin untuk yStore dengan ketentuan berikut:

Halaman:
- Login: halaman login khusus admin
- Dashboard: tampilkan ringkasan statistik:
  - Total produk
  - Total kategori
  - Total produk best seller
  - Total produk new arrival
  - Total produk per kategori
- Produk: CRUD produk (nama, foto, harga, deskripsi, kategori,
  is_best_seller, is_new_arrival), tabel menampilkan preview gambar produk
- Kategori: CRUD kategori

Ketentuan:
- Gunakan autentikasi yang sesuai dengan setup project ini
- Buat seeder untuk akun admin default (email dan password)
- Halaman admin hanya bisa diakses setelah login
- Gunakan layout admin tersendiri (terpisah dari layout toko)
- Sidebar navigasi untuk berpindah antar menu admin
- Tampilkan konfirmasi sebelum menghapus data
- Validasi form pada tambah dan edit produk maupun kategori
- Tampilkan toast notification setelah CRUD berhasil atau gagal
- Gunakan font Inter dari Google Fonts
- Gunakan Heroicons untuk kebutuhan icon
- Tambahkan animasi transisi yang subtle
- Semua halaman responsif dan mobile friendly

Color palette sama seperti sebelumnya

```

Salin prompt di atas, lalu gunakan di tools AI favoritmu untuk membuat ulang website ini.
