<?php
use Livewire\Component;

new class extends Component {
    public function render()
    {
        return view('pages.about');
    }
};
?>

<div class="animate-fade-in">
    <section class="bg-teal py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h1 class="text-3xl font-bold text-ink">Tentang Kami</h1>
            <div class="gold-line mt-2 mb-0"></div>
        </div>
    </section>

    <section class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-8">
        <div class="bg-panel rounded-3xl shadow-sm border border-gray-100 p-8">
            <h2 class="text-2xl font-semibold text-gray-900">ThafhanClothes: Gaya yang Mudah</h2>
            <p class="mt-4 text-ink leading-relaxed">Kami menghadirkan pilihan busana yang stylish, nyaman, dan terjangkau untuk semua momen. Mulai dari pakaian sehari-hari hingga koleksi special, ThafhanClothes dirancang untuk memberi rasa percaya diri dari pagi hingga malam.</p>
            <p class="mt-4 text-ink leading-relaxed">Nilai kami sederhana: kualitas produk yang jujur, layanan pembelian yang jelas, dan pengiriman cepat. Setiap pelanggan kami dianggap sebagai bagian dari keluarga, dan pengalaman belanja Anda adalah prioritas utama.</p>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="rounded-3xl border border-deep/10 bg-panel p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-gray-900">Produk Berkualitas</h3>
                <p class="mt-3 text-sm text-ink">Setiap produk dipilih dengan perhatian terhadap detail, bahan, dan kenyamanan.</p>
            </div>
            <div class="rounded-3xl border border-deep/10 bg-panel p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-gray-900">Layanan Cepat</h3>
                <p class="mt-3 text-sm text-ink">Pembayaran, konfirmasi, dan pengiriman kami dirancang untuk berjalan mulus.</p>
            </div>
            <div class="rounded-3xl border border-deep/10 bg-panel p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-gray-900">Dukungan Personal</h3>
                <p class="mt-3 text-sm text-ink">Kami siap membantu lewat WhatsApp bila Anda butuh rekomendasi atau bantuan pesanan.</p>
            </div>
        </div>
    </section>
</div>



