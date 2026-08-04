<?php

use App\Support\ShopSettings;
use Livewire\Component;

new class extends Component
{
    public function render()
    {
        $body = ShopSettings::aboutBody();
        $paragraphs = preg_split("/\n\s*\n/", trim($body)) ?: [$body];

        return view('pages.about', [
            'aboutTitle' => ShopSettings::aboutTitle(),
            'aboutParagraphs' => array_values(array_filter(array_map('trim', $paragraphs))),
        ]);
    }
};
?>

<div class="animate-fade-in">
    <section class="bg-teal py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h1 class="text-3xl font-bold text-ink">Tentang Kami</h1>
            <div class="gold-line mt-2 mb-0"></div>
            <p class="mt-3 max-w-2xl text-sm !text-white leading-relaxed">
                Kenalan sama ThafhanClothes — toko fashion preloved dengan kualitas oke dan harga yang bersahabat.
            </p>
        </div>
    </section>

    <section class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-8">
        <div class="bg-panel rounded-3xl shadow-sm border border-gray-100 p-8">
            <h2 class="text-2xl font-semibold text-gray-900">{{ $aboutTitle }}</h2>
            @foreach ($aboutParagraphs as $paragraph)
                <p class="mt-4 text-ink leading-relaxed whitespace-pre-line">{{ $paragraph }}</p>
            @endforeach
        </div>
 
        <div class="grid gap-6 lg:grid-cols-3">
            <div class="rounded-3xl border border-deep/10 bg-panel p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-gray-900">Preloved pilihan</h3>
                <p class="mt-3 text-sm text-ink">
                    Bukan asal second. Tiap item diseleksi biar kualitasnya masih worth it buat dipakai ulang.
                </p>
            </div>
            <div class="rounded-3xl border border-deep/10 bg-panel p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-gray-900">Prosesnya simple</h3>
                <p class="mt-3 text-sm text-ink">
                    Pilih → keranjang → bayar → upload bukti. Status pesanan juga bisa dilacak biar ga penasaran.
                </p>
            </div>
            <div class="rounded-3xl border border-deep/10 bg-panel p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-gray-900">Chat-nya responsif</h3>
                <p class="mt-3 text-sm text-ink">
                    Bingung size, kondisi, atau mau rekomendasi outfit? Langsung tanya via WhatsApp aja.
                </p>
            </div>
        </div>
    </section>
</div>
