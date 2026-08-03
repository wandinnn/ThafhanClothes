<?php

use Livewire\Component;
use App\Models\Category;
use App\Models\Product;

new class extends Component {
    public function render()
    {
        return view('pages.⚡home', [
            'categories'  => Category::withCount('products')->get(),
            'flashSales'  => Product::with('category')->where('is_flash_sale', true)->take(4)->get(),
            'newArrivals' => Product::with('category')->where('is_new_arrival', true)->take(4)->get(),
        ]);
    }
};
?>

<div class="animate-fade-in">

    {{-- ======================== HERO ======================== --}}
    <section class="bg-[#8BDFDD] overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 lg:py-24">
            <div class="grid lg:grid-cols-2 gap-10 items-center">
                <div class="animate-fade-up stagger-1">
                    <p class="text-[#1F2A2A] text-xs font-semibold uppercase tracking-[0.2em] mb-3">KOLEKSI TERBARU</p>
                    <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold text-[#4F252E] leading-tight">
                        Koleksi Terbaru<br>Musim Ini
                    </h1>
                    <div class="gold-line mt-4"></div>
                    <p class="mt-4 text-[#4F252E] max-w-md leading-relaxed">
                        Temukan gaya terbaikmu dengan koleksi fashion premium. Dari kasual hingga formal, semua ada di sini.
                    </p>
                    <div class="flex flex-wrap gap-3 mt-8">
                        <a href="{{ route('products') }}"
                           class="inline-flex items-center gap-2 bg-[#FFF6DE] hover:bg-[#F48F68] text-[#1F2A2A] font-bold px-8 py-3 rounded transition-all duration-200 hover:-translate-y-0.5 shadow-lg text-sm">
                            Shop Now &rarr;
                        </a>
                        <a href="{{ route('products') }}"
                           class="inline-flex items-center gap-2 border border-[#FFF6DE] text-[#1F2A2A] hover:bg-[#FFF6DE]/10 font-semibold px-8 py-3 rounded transition-all duration-200 text-sm">
                            Lihat Koleksi
                        </a>
                    </div>
                </div>

                <div class="relative animate-fade-up stagger-3 hidden lg:block">
                    <div class="rounded-2xl overflow-hidden shadow-2xl aspect-[4/3]">
                        <img src="https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=800&q=80"
                             alt="Koleksi Fashion" class="w-full h-full object-cover">
                    </div>
                    <div class="absolute -bottom-4 -left-4 bg-[#FFF6DE] text-[#1F2A2A] rounded-xl px-5 py-3 shadow-xl">
                        <p class="text-xs font-semibold uppercase tracking-wide">New Collection</p>
                        <p class="text-lg font-bold">2026</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ======================== FLASH SALE ======================== --}}
    <section class="bg-[#8BDFDD] py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-start justify-between mb-8 flex-wrap gap-4">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-6 text-[#1F2A2A]" fill="currentColor" viewBox="0 0 24 24">
                            <path fill-rule="evenodd" d="M14.615 1.595a.75.75 0 0 1 .359.852L12.982 9.75h7.268a.75.75 0 0 1 .548 1.262l-10.5 11.25a.75.75 0 0 1-1.272-.71l1.992-7.302H3.75a.75.75 0 0 1-.548-1.262l10.5-11.25a.75.75 0 0 1 .913-.143Z" clip-rule="evenodd"/>
                        </svg>
                        <h2 class="text-2xl font-bold text-[#4F252E]">Flash Sale</h2>
                    </div>
                    <div class="gold-line mb-0"></div>
                    <p class="text-[#4F252E] text-sm mt-1">Jangan lewatkan diskon spesial!</p>
                </div>

                <div class="flex items-center gap-2">
                    <div class="text-center">
                        <div class="bg-[#FFF6DE] text-[#1F2A2A] font-bold text-xl rounded-lg w-14 h-14 flex items-center justify-center" id="cd-hours">00</div>
                        <p class="text-xs text-[#4F252E] mt-1 uppercase tracking-wide">JAM</p>
                    </div>
                    <span class="text-[#1F2A2A] font-bold text-2xl mb-4">:</span>
                    <div class="text-center">
                        <div class="bg-[#FFF6DE] text-[#1F2A2A] font-bold text-xl rounded-lg w-14 h-14 flex items-center justify-center" id="cd-minutes">00</div>
                        <p class="text-xs text-[#4F252E] mt-1 uppercase tracking-wide">MENIT</p>
                    </div>
                    <span class="text-[#1F2A2A] font-bold text-2xl mb-4">:</span>
                    <div class="text-center">
                        <div class="bg-[#FFF6DE] text-[#1F2A2A] font-bold text-xl rounded-lg w-14 h-14 flex items-center justify-center" id="cd-seconds">00</div>
                        <p class="text-xs text-[#4F252E] mt-1 uppercase tracking-wide">DETIK</p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                @foreach($flashSales as $index => $product)
                    <div class="product-card bg-white rounded-xl overflow-hidden shadow-md animate-fade-up stagger-{{ min($index+1, 6) }}">
                        <div class="card-img-wrap relative aspect-[4/3]">
                            <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="w-full h-full object-cover">
                            <span class="absolute top-3 left-3 bg-[#FFF6DE] text-[#1F2A2A] text-xs font-bold px-2.5 py-1 rounded">SALE</span>
                            @if($product->discount_percent)
                                <span class="absolute top-3 right-3 bg-[#FFF6DE] text-[#1F2A2A] text-xs font-bold px-2 py-1 rounded">-{{ $product->discount_percent }}%</span>
                            @endif
                        </div>
                        <div class="p-4">
                            <p class="text-xs text-[#4F252E] uppercase tracking-wide">{{ $product->category->name }}</p>
                            <h3 class="mt-0.5 font-semibold text-gray-900 text-sm line-clamp-1">{{ $product->name }}</h3>
                            <div class="mt-1 flex items-center gap-2">
                                <span class="text-[#1F2A2A] font-bold text-base">{{ $product->formatted_price }}</span>
                                @if($product->formatted_original_price)
                                    <span class="text-[#4F252E] text-xs line-through">{{ $product->formatted_original_price }}</span>
                                @endif
                            </div>
                            <div class="mt-3 grid grid-cols-2 gap-2">
                                <a href="{{ route('product.detail', $product->slug) }}"
                                   class="text-center text-xs font-semibold border border-[#FFF6DE] text-[#1F2A2A] hover:bg-[#FFF6DE]/10 py-2 rounded transition-colors">
                                    Detail
                                </a>
                                <button type="button"
                                        onclick="openAddToCartFromData(this)"
                                        data-product-id="{{ $product->id }}"
                                        data-product-slug="{{ $product->slug }}"
                                        data-product-name="{{ $product->name }}"
                                        data-product-price="{{ $product->price }}"
                                        data-product-formatted-price="{{ $product->formatted_price }}"
                                        data-product-category="{{ $product->category->slug }}"
                                        data-product-image="{{ $product->image_url }}"
                                        class="text-center text-xs font-semibold bg-[#FFF6DE] hover:bg-[#F48F68] text-[#1F2A2A] py-2 rounded transition-colors flex items-center justify-center gap-1.5">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ======================== CATEGORIES ======================== --}}
    <section class="bg-[#f4f4f4] py-14">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="mb-8">
                <h2 class="text-2xl font-bold text-gray-900">Kategori</h2>
                <div class="gold-line"></div>
            </div>
            <div class="grid grid-cols-3 sm:grid-cols-6 gap-4">
                @foreach($categories as $category)
                    <a href="{{ route('products', ['category' => $category->slug]) }}"
                       class="group flex flex-col items-center gap-3 animate-fade-up stagger-{{ min($loop->index+1, 6) }}">
                        <div class="w-full aspect-square rounded-xl overflow-hidden bg-gray-200 shadow-sm group-hover:shadow-md transition-all duration-300 group-hover:-translate-y-1">
                            <img src="{{ $category->products->first()?->image_url ?? 'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=300&q=80' }}"
                                 alt="{{ $category->name }}"
                                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                        </div>
                        <span class="text-xs font-medium text-gray-700 group-hover:text-[#1F2A2A] transition-colors text-center">{{ $category->name }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ======================== PROMO BANNER WITH FASHION ICONS ======================== --}}
    <section class="py-16">
        <div class="max-w-4xl mx-auto px-4 text-center">
            <div class="relative overflow-hidden rounded-[2rem] bg-[#4a84c8] text-[#4F252E] p-10 shadow-2xl">

                {{-- Fashion Icon Decorations (Background) --}}
                {{-- Shirt top-left --}}
                <svg class="absolute -top-4 -left-4 w-32 h-32 text-[#4F252E]/10" viewBox="0 0 100 100" fill="currentColor">
                    <path d="M35,10 L20,20 L10,35 L25,40 L25,80 L75,80 L75,40 L90,35 L80,20 L65,10 C62,18 55,22 50,22 C45,22 38,18 35,10 Z"/>
                    <rect x="38" y="10" width="24" height="12" rx="6" fill="currentColor"/>
                </svg>

                {{-- Pants bottom-right --}}
                <svg class="absolute -bottom-4 -right-4 w-28 h-28 text-[#4F252E]/10" viewBox="0 0 100 100" fill="currentColor">
                    <path d="M20,10 L80,10 L75,55 L60,90 L50,90 L45,55 L55,55 L50,55 L50,55 L40,90 L50,90 L25,55 Z"/>
                </svg>

                {{-- Hat top-right --}}
                <svg class="absolute top-4 -right-6 w-24 h-24 text-[#4F252E]/10 rotate-12" viewBox="0 0 100 60" fill="currentColor">
                    <ellipse cx="50" cy="50" rx="45" ry="10"/>
                    <path d="M25,50 Q30,15 50,10 Q70,15 75,50 Z"/>
                    <rect x="20" y="48" width="60" height="6" rx="3"/>
                </svg>

                {{-- Watch bottom-left --}}
                <svg class="absolute bottom-4 -left-6 w-20 h-20 text-[#4F252E]/10 -rotate-12" viewBox="0 0 60 60" fill="currentColor">
                    <rect x="22" y="5" width="16" height="10" rx="3"/>
                    <rect x="22" y="45" width="16" height="10" rx="3"/>
                    <circle cx="30" cy="30" r="16" fill="none" stroke="currentColor" stroke-width="4"/>
                    <circle cx="30" cy="30" r="2"/>
                    <line x1="30" y1="30" x2="30" y2="20" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/>
                    <line x1="30" y1="30" x2="38" y2="30" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>

                {{-- Handbag center-left --}}
                <svg class="absolute top-1/2 -translate-y-1/2 -left-10 w-24 h-24 text-[#4F252E]/8 hidden lg:block" viewBox="0 0 80 80" fill="currentColor">
                    <path d="M15,35 L65,35 L60,65 L20,65 Z"/>
                    <path d="M28,35 C28,22 52,22 52,35" fill="none" stroke="currentColor" stroke-width="4" stroke-linecap="round"/>
                    <rect x="32" y="47" width="16" height="10" rx="5"/>
                </svg>

                {{-- Sneaker center-right --}}
                <svg class="absolute top-1/2 -translate-y-1/2 -right-10 w-28 h-24 text-[#4F252E]/8 hidden lg:block" viewBox="0 0 100 70" fill="currentColor">
                    <path d="M5,55 Q10,25 35,20 L60,22 Q80,24 90,40 L90,55 Q80,60 5,60 Z"/>
                    <path d="M35,20 L38,10 L55,12 L60,22" fill="none" stroke="currentColor" stroke-width="3"/>
                    <path d="M5,55 L90,55" stroke="#4F252E" stroke-width="2" stroke-dasharray="6,4" fill="none"/>
                </svg>

                {{-- Small scattered dots decoration --}}
                <div class="absolute inset-0 pointer-events-none" aria-hidden="true">
                    <div class="absolute top-8 left-1/4 w-1.5 h-1.5 rounded-full bg-white/20"></div>
                    <div class="absolute top-16 right-1/3 w-1 h-1 rounded-full bg-white/15"></div>
                    <div class="absolute bottom-8 left-1/3 w-2 h-2 rounded-full bg-white/15"></div>
                    <div class="absolute bottom-12 right-1/4 w-1 h-1 rounded-full bg-white/20"></div>
                    <div class="absolute top-1/2 left-16 w-1.5 h-1.5 rounded-full bg-white/10"></div>
                    <div class="absolute top-1/3 right-16 w-1 h-1 rounded-full bg-white/10"></div>
                </div>

                {{-- Content --}}
                <div class="relative z-10">
                    <p class="text-sm font-semibold uppercase tracking-[0.25em] text-[#4F252E]/70 mb-2">Fashion Premium</p>
                    <h2 class="text-3xl sm:text-4xl font-bold">Belanja Nyaman Kualitas Aman</h2>
                    <p class="mt-3 text-[#4F252E]/80 max-w-lg mx-auto leading-relaxed">
                        Temukan gaya terbaikmu dengan koleksi fashion premium. Dari kasual hingga formal, semua ada di sini.
                    </p>
                    <a href="{{ route('products') }}"
                       class="inline-block mt-8 bg-white text-[#1F2A2A] font-bold px-10 py-3.5 rounded-full transition-all duration-200 hover:-translate-y-0.5 shadow-lg hover:shadow-xl text-sm">
                        Belanja Sekarang
                    </a>
                </div>
            </div>
        </div>
    </section>

    {{-- ======================== NEW ARRIVALS ======================== --}}
    <section class="bg-[#f4f4f4] py-14">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-end justify-between mb-8">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">New Arrivals</h2>
                    <div class="gold-line mb-0"></div>
                </div>
                <a href="{{ route('products') }}"
                   class="text-sm font-medium text-[#1F2A2A] hover:text-[#F48F68] transition-colors border border-[#FFF6DE] px-4 py-1.5 rounded hover:bg-[#FFF6DE]/10">
                    Lihat Semua
                </a>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                @foreach($newArrivals as $index => $product)
                    <div class="product-card bg-white rounded-xl overflow-hidden shadow-sm animate-fade-up stagger-{{ min($index+1, 6) }}">
                        <div class="card-img-wrap relative aspect-[4/5]">
                            <img src="{{ $product->image_url }}" alt="{{ $product->name }}" loading="lazy" class="w-full h-full object-cover">
                            @if($product->discount_percent)
                                <span class="absolute top-3 left-3 bg-[#FFF6DE] text-[#1F2A2A] text-xs font-bold px-2.5 py-1 rounded">SALE</span>
                                <span class="absolute top-3 right-3 bg-[#FFF6DE] text-[#1F2A2A] text-xs font-bold px-2 py-1 rounded">-{{ $product->discount_percent }}%</span>
                            @endif
                            <span class="absolute bottom-3 left-3 bg-blue-500/90 text-[#4F252E] text-[10px] font-bold px-2.5 py-1 rounded-full">New</span>
                        </div>
                        <div class="p-4">
                            <p class="text-xs text-[#4F252E] uppercase tracking-wide">{{ $product->category->name }}</p>
                            <h3 class="mt-0.5 font-semibold text-gray-900 text-sm line-clamp-1">{{ $product->name }}</h3>
                            <div class="mt-1 flex items-center gap-2">
                                <span class="text-[#1F2A2A] font-bold">{{ $product->formatted_price }}</span>
                                @if($product->formatted_original_price)
                                    <span class="text-[#4F252E] text-xs line-through">{{ $product->formatted_original_price }}</span>
                                @endif
                            </div>
                            <div class="mt-3 grid grid-cols-2 gap-2">
                                <a href="{{ route('product.detail', $product->slug) }}"
                                   class="text-center text-xs font-semibold border border-[#FFF6DE] text-[#1F2A2A] hover:bg-[#FFF6DE]/10 py-2 rounded transition-colors">
                                    Detail
                                </a>
                                <button type="button"
                                        onclick="openAddToCartFromData(this)"
                                        data-product-id="{{ $product->id }}"
                                        data-product-slug="{{ $product->slug }}"
                                        data-product-name="{{ $product->name }}"
                                        data-product-price="{{ $product->price }}"
                                        data-product-formatted-price="{{ $product->formatted_price }}"
                                        data-product-category="{{ $product->category->slug }}"
                                        data-product-image="{{ $product->image_url }}"
                                        class="text-xs font-semibold bg-[#FFF6DE] hover:bg-[#F48F68] text-[#1F2A2A] py-2 rounded transition-colors flex items-center justify-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <script>
    (function () {
        const endTime = new Date().getTime() + (8 * 60 * 60 * 1000);
        function pad(n) { return String(n).padStart(2, '0'); }
        function tick() {
            const diff = endTime - new Date().getTime();
            if (diff <= 0) { return; }
            document.getElementById('cd-hours').textContent   = pad(Math.floor(diff / 3600000));
            document.getElementById('cd-minutes').textContent = pad(Math.floor((diff % 3600000) / 60000));
            document.getElementById('cd-seconds').textContent = pad(Math.floor((diff % 60000) / 1000));
        }
        tick();
        setInterval(tick, 1000);
    })();
    </script>
</div>



