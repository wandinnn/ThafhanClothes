<?php
use Livewire\Component;

new class extends Component {
    public function render()
    {
        return view('pages.⚡how-to-shop');
    }
};
?>

<div class="animate-fade-in">
    <section class="bg-[#8BDFDD] py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h1 class="text-3xl font-bold text-[#4F252E]">Cara Belanja</h1>
            <div class="gold-line mt-2 mb-0"></div>
        </div>
    </section>

    <section class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-8">
        <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-8">
            <h2 class="text-2xl font-semibold text-gray-900">Langkah Belanja di ThafhanClothes</h2>
            <p class="mt-4 text-[#4F252E] leading-relaxed">Belanja di sini mudah, cepat, dan aman. Ikuti langkah mudah berikut untuk menyelesaikan pesanan Anda.</p>
        </div>

        <div class="grid gap-6">
            <div class="rounded-3xl border border-gray-100 bg-[#f8fafc] p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-gray-900">1. Pilih Produk</h3>
                <p class="mt-3 text-sm text-[#4F252E]">Jelajahi kategori produk, gunakan pencarian, dan pilih produk yang cocok untuk gaya Anda.</p>
            </div>
            <div class="rounded-3xl border border-gray-100 bg-[#f8fafc] p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-gray-900">2. Tambahkan ke Keranjang</h3>
                <p class="mt-3 text-sm text-[#4F252E]">Setelah menemukan produk, klik tombol tambah ke keranjang dan lanjutkan belanja atau langsung checkout.</p>
            </div>
            <div class="rounded-3xl border border-gray-100 bg-[#f8fafc] p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-gray-900">3. Pilih Metode Pengiriman</h3>
                <p class="mt-3 text-sm text-[#4F252E]">Pilih kota pengiriman. Bandung mendapatkan gratis ongkir, sementara kota lainnya mengikuti tarif yang tertera.</p>
            </div>
            <div class="rounded-3xl border border-gray-100 bg-[#f8fafc] p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-gray-900">4. Konfirmasi dan Bayar</h3>
                <p class="mt-3 text-sm text-[#4F252E]">Isi data pengiriman dengan jelas, lalu konfirmasi pesanan. Anda akan langsung diarahkan ke halaman sukses.</p>
            </div>
            <div class="rounded-3xl border border-gray-100 bg-[#f8fafc] p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-gray-900">5. Lacak Pesanan</h3>
                <p class="mt-3 text-sm text-[#4F252E]">Gunakan kode pesanan Anda untuk melihat status pengiriman di halaman Lacak Pesanan.</p>
            </div>
        </div>
    </section>
</div>



