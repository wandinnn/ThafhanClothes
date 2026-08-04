<?php

use App\Support\ShopSettings;
use Livewire\Component;

new class extends Component
{
    public function render()
    {
        return view('pages.faq', [
            'faqs' => ShopSettings::faqItems(),
            'whatsapp' => ShopSettings::whatsapp(),
        ]);
    }
};
?>

<div class="animate-fade-in">
    <section class="bg-teal py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h1 class="text-3xl font-bold text-ink">FAQ</h1>
            <div class="gold-line mt-2 mb-0"></div>
            <p class="mt-3 max-w-2xl text-sm !text-white leading-relaxed">
                Hal-hal yang sering ditanya soal belanja preloved di ThafhanClothes — singkat, jelas, no ribet.
            </p>
        </div>
    </section>

    <section class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div class="mb-8 rounded-2xl border border-deep/10 bg-panel p-6 shadow-sm">
            <h2 class="text-xl font-semibold text-gray-900">Yang sering ditanya</h2>
            <p class="mt-2 text-sm text-ink leading-relaxed">
                buat sekarang etalasenya isi fashion preloved pilihan. kedepannya koleksinya bakal nambah barang baru dan tetap bareng sama preloved juga.
            </p>
        </div>

        <div class="space-y-3" x-data="{ open: 0 }">
            @foreach ($faqs as $index => $faq)
                <div class="rounded-2xl border border-deep/10 bg-panel shadow-sm overflow-hidden" wire:key="faq-{{ $index }}">
                    <button type="button"
                            class="w-full flex items-center justify-between gap-4 px-5 py-4 text-left transition-colors hover:bg-beige/40"
                            @click="open = open === {{ $index }} ? null : {{ $index }}"
                            :aria-expanded="open === {{ $index }}">
                        <span class="text-sm sm:text-base font-semibold text-gray-900 leading-snug">
                            {{ $faq['q'] }}
                        </span>
                        <span class="shrink-0 size-8 rounded-full border border-deep/15 bg-beige/50 flex items-center justify-center text-deep"
                              :class="open === {{ $index }} ? 'bg-deep !text-beige border-deep' : ''">
                            <svg xmlns="http://www.w3.org/2000/svg"
                                 class="size-4 transition-transform duration-200"
                                 :class="open === {{ $index }} ? 'rotate-180' : ''"
                                 fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                 aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/>
                            </svg>
                        </span>
                    </button>

                    <div x-show="open === {{ $index }}"
                         x-collapse
                         class="px-5 pb-4">
                        <p class="text-sm text-ink leading-relaxed">{{ $faq['a'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-8 rounded-2xl bg-teal border border-teal-dark/20 p-5 text-center">
            <p class="text-sm !text-beige leading-relaxed">
                Masih bingung? Langsung chat WA aja — kita bantu gercep.
            </p>
            <a href="https://wa.me/{{ $whatsapp }}"
               target="_blank" rel="noopener noreferrer"
               class="mt-3 inline-flex items-center justify-center gap-2 rounded-xl bg-green-500 hover:bg-green-600 !text-white px-5 py-2.5 text-sm font-semibold transition-colors"
               style="background-color: #22c55e; color: #fff;">
                Tanya via WhatsApp
            </a>
        </div>
    </section>
</div>
