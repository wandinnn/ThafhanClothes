<?php

use Livewire\Component;
use App\Models\Category;
use App\Models\Product;
use App\Models\Wishlist;
use Carbon\Carbon;

new class extends Component {
    public function render()
    {
        $endsAt = $this->flashSaleEndsAt();

        return view('pages.home', [
            'categories' => Category::withCount('products')
                ->with(['products' => fn ($query) => $query->select('id', 'category_id', 'image_url')->oldest('id')])
                ->get(),
            'flashSales' => Product::with('category')->where('is_flash_sale', true)->take(4)->get(),
            'newArrivals' => Product::with('category')->where('is_new_arrival', true)->take(4)->get(),
            'flashSaleEndsAt' => $endsAt->timestamp * 1000,
            'flashSaleActive' => $endsAt->isFuture(),
            'wishlistIds' => Wishlist::where('session_id', session()->getId())->pluck('product_id')->all(),
        ]);
    }

    private function flashSaleEndsAt(): Carbon
    {
        $configured = \App\Support\ShopSettings::flashSaleEndsAt();
        $timezone = config('shop.timezone', 'Asia/Jakarta');

        if (filled($configured)) {
            return Carbon::parse($configured, $timezone);
        }

        return Carbon::now($timezone)->endOfDay();
    }
};
?>

<div class="animate-fade-in">

    {{-- ======================== HERO ======================== --}}
    <section class="hero-fabric overflow-hidden">
        <svg class="fashion-float left-[4%] top-[18%] size-24" viewBox="0 0 100 100" fill="none" stroke="currentColor" stroke-width="3" aria-hidden="true">
            <path d="M50 18c0-9 13-10 13-1 0 7-13 8-13 17" stroke-linecap="round"/>
            <path d="M50 34 16 62c-5 4-2 11 4 11h60c6 0 9-7 4-11L50 34Z" stroke-linejoin="round"/>
        </svg>
        <svg class="fashion-float right-[7%] top-[10%] size-28" viewBox="0 0 100 100" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
            <path d="M34 20 17 31l9 17 9-5v39h30V43l9 5 9-17-17-11c-4 8-28 8-32 0Z" stroke-linejoin="round"/>
            <path d="M42 25c2 7 14 7 16 0"/>
        </svg>
        <svg class="fashion-float bottom-[8%] left-[46%] h-16 w-32" viewBox="0 0 140 60" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
            <path d="M4 35c18-24 35 20 54-4s37 17 56-5 21 6 22 8" stroke-linecap="round" stroke-dasharray="6 7"/>
        </svg>

        <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 lg:py-24">
            <div class="grid lg:grid-cols-2 gap-10 items-center">
                <div class="animate-fade-up stagger-1">
                    <p class="text-on-teal/90 text-xs font-semibold uppercase tracking-[0.2em] mb-3">Welcome To ThafhanClothes</p>
                    <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold text-on-teal leading-tight">
                        Harga Minimum<br>Kualitas Premium
                    </h1>
                    <div class="gold-line mt-4"></div>
                    <p class="mt-4 text-on-teal/85 max-w-md leading-relaxed">
                        Temukan gaya terbaikmu dengan koleksi fashion premium. Dari kasual hingga formal, semua ada di sini.
                    </p>
                    <div class="flex flex-wrap gap-3 mt-8">
                        <a wire:navigate href="{{ route('products') }}"
                           class="btn-cta px-6 py-3 text-sm shadow-lg hover:-translate-y-0.5">
                            Shop Now
                        </a>
                        <a wire:navigate href="{{ route('products') }}"
                           class="btn-outline btn-outline-on-teal px-8 py-3 text-sm">
                            Lihat Koleksi
                        </a>
                    </div>
                </div>

                <div class="relative animate-fade-up stagger-3 hidden lg:block max-w-sm mx-auto">
                    <div class="rounded-2xl overflow-hidden shadow-2xl aspect-[4/5]">
                        <img src="{{ asset('images/hero-thafhan.png') }}"
                             alt="ThafhanClothes" class="hero-fashion-image w-full h-full object-cover object-center">
                    </div>
                    <div class="absolute -bottom-4 -left-4 bg-cream text-on-cream rounded-xl px-5 py-3 shadow-xl">
                        <p class="text-xs font-semibold uppercase tracking-wide">New Collection</p>
                        <p class="text-lg font-bold">2026</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ======================== FLASH SALE ======================== --}}
    <section class="section-flash relative overflow-hidden py-12">
        <div class="section-deco" aria-hidden="true">
            <span class="flash-spark flash-spark-1"></span>
            <span class="flash-spark flash-spark-2"></span>
            <span class="flash-spark flash-spark-3"></span>
            <span class="flash-ribbon"></span>
            {{-- Fashion line-art --}}
            <svg class="section-fashion top-[12%] left-[3%] size-20 sm:size-24" viewBox="0 0 100 100" fill="none" stroke="currentColor" stroke-width="2.4">
                <path d="M34 20 17 31l9 17 9-5v39h30V43l9 5 9-17-17-11c-4 8-28 8-32 0Z" stroke-linejoin="round"/>
                <path d="M42 25c2 7 14 7 16 0"/>
            </svg>
            <svg class="section-fashion top-[58%] right-[4%] size-16 sm:size-20" viewBox="0 0 100 70" fill="none" stroke="currentColor" stroke-width="2.3" style="--fashion-delay:-2.1s">
                <path d="M8 52 Q14 24 38 20 L62 22 Q80 24 90 40 L90 52 Q78 58 8 56 Z" stroke-linejoin="round"/>
                <path d="M38 20 42 10 56 12 62 22" stroke-linecap="round"/>
                <path d="M10 52h76" stroke-dasharray="5 5"/>
            </svg>
            <svg class="section-fashion bottom-[10%] left-[46%] h-12 w-24 hidden sm:block" viewBox="0 0 120 48" fill="none" stroke="currentColor" stroke-width="2.2" style="--fashion-delay:-4s">
                <path d="M8 30c14-18 28 14 42-4s28 12 44-2" stroke-linecap="round" stroke-dasharray="5 6"/>
            </svg>
        </div>
        <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-start justify-between mb-8 flex-wrap gap-4">
                <div data-reveal="left">
                    <div class="flex items-center gap-2 mb-1">
                        <svg xmlns="http://www.w3.org/2000/svg" class="flash-bolt size-6 text-coral" fill="currentColor" viewBox="0 0 24 24">
                            <path fill-rule="evenodd" d="M14.615 1.595a.75.75 0 0 1 .359.852L12.982 9.75h7.268a.75.75 0 0 1 .548 1.262l-10.5 11.25a.75.75 0 0 1-1.272-.71l1.992-7.302H3.75a.75.75 0 0 1-.548-1.262l10.5-11.25a.75.75 0 0 1 .913-.143Z" clip-rule="evenodd"/>
                        </svg>
                        <h2 class="text-2xl font-bold text-deep">Flash Sale</h2>
                    </div>
                    <div class="gold-line mb-0"></div>
                    <p class="text-ink text-sm mt-1">Jangan lewatkan diskon spesial!</p>
                </div>

                <div class="flex items-center gap-2" data-reveal="scale" @if(! $flashSaleActive) style="opacity:0.5" @endif>
                    <div class="text-center">
                        <div class="countdown-value bg-panel text-deep font-bold text-xl rounded-none border border-deep/15 w-14 h-14 flex items-center justify-center" id="cd-hours">00</div>
                        <p class="text-xs text-ink mt-1 uppercase tracking-wide">JAM</p>
                    </div>
                    <span class="text-deep font-bold text-2xl mb-4">:</span>
                    <div class="text-center">
                        <div class="countdown-value bg-panel text-deep font-bold text-xl rounded-none border border-deep/15 w-14 h-14 flex items-center justify-center" id="cd-minutes">00</div>
                        <p class="text-xs text-ink mt-1 uppercase tracking-wide">MENIT</p>
                    </div>
                    <span class="text-deep font-bold text-2xl mb-4">:</span>
                    <div class="text-center">
                        <div class="countdown-value bg-panel text-deep font-bold text-xl rounded-none border border-deep/15 w-14 h-14 flex items-center justify-center" id="cd-seconds">00</div>
                        <p class="text-xs text-ink mt-1 uppercase tracking-wide">DETIK</p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                @foreach($flashSales as $index => $product)
                    <div class="product-card home-product-card bg-panel rounded-xl overflow-hidden shadow-md"
                         data-reveal style="--reveal-delay: {{ min($index, 5) * 80 }}ms">
                        <div class="card-img-wrap relative aspect-[4/3]">
                            <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="w-full h-full object-cover">
                            <span class="sale-badge absolute top-3 right-3 bg-cream text-on-cream text-xs font-bold px-2.5 py-1">SALE</span>
                            @if($product->discount_percent)
                                <span class="sale-badge absolute top-12 right-3 bg-coral text-on-coral text-xs font-bold px-2 py-1">-{{ $product->discount_percent }}%</span>
                            @endif
                            <x-product-condition-badge :condition="$product->condition" class="absolute top-3 left-3" />
                        </div>
                        <div class="p-4">
                            <p class="text-xs text-ink uppercase tracking-wide">{{ $product->category->name }}</p>
                            <h3 class="mt-0.5 font-semibold text-gray-900 text-sm line-clamp-1">{{ $product->name }}</h3>
                            <div class="mt-1 flex items-center gap-2">
                                <span class="text-coral font-bold text-base">{{ $product->formatted_price }}</span>
                                @if($product->formatted_original_price)
                                    <span class="text-ink text-xs line-through">{{ $product->formatted_original_price }}</span>
                                @endif
                            </div>
                            <div class="card-actions mt-3 grid grid-cols-2 gap-2">
                                <a wire:navigate href="{{ route('product.detail', $product->slug) }}"
                                   class="text-center text-xs font-semibold bg-deep !text-beige border border-deep hover:bg-deep-dark py-2 transition-colors">
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
                                        data-in-wishlist="{{ in_array($product->id, $wishlistIds, true) ? '1' : '0' }}"
                                        class="text-center text-xs font-semibold bg-cream hover:bg-coral text-on-cream hover:text-on-coral py-2 transition-colors flex items-center justify-center gap-1.5">
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
    <section class="section-category relative overflow-hidden py-14">
        <div class="section-deco" aria-hidden="true">
            <span class="weave-blob weave-blob-1"></span>
            <span class="weave-blob weave-blob-2"></span>
            {{-- Fashion line-art --}}
            <svg class="section-fashion top-[14%] right-[5%] size-16 sm:size-20" viewBox="0 0 80 90" fill="none" stroke="currentColor" stroke-width="2.3">
                <path d="M28 12 16 24l8 10v40h32V34l8-10L52 12c-3 7-13 8-24 0Z" stroke-linejoin="round"/>
                <path d="M34 16c2 5 10 5 12 0"/>
            </svg>
            <svg class="section-fashion bottom-[12%] left-[4%] size-16 sm:size-20" viewBox="0 0 70 90" fill="none" stroke="currentColor" stroke-width="2.3" style="--fashion-delay:-2.6s">
                <path d="M18 10h34l-4 34-10 36h-8L26 44 22 44 14 80h-8L18 44Z" stroke-linejoin="round"/>
            </svg>
            <svg class="section-fashion top-[48%] left-[48%] size-14 hidden md:block" viewBox="0 0 70 70" fill="none" stroke="currentColor" stroke-width="2.2" style="--fashion-delay:-4.4s">
                <rect x="26" y="6" width="18" height="12" rx="3"/>
                <rect x="26" y="52" width="18" height="12" rx="3"/>
                <circle cx="35" cy="35" r="14"/>
                <circle cx="35" cy="35" r="2"/>
                <path d="M35 35v-8M35 35h7" stroke-linecap="round"/>
            </svg>
        </div>
        <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="mb-8" data-reveal="left">
                <h2 class="text-2xl font-bold text-gray-900">Kategori</h2>
                <div class="gold-line"></div>
            </div>
            <div class="grid grid-cols-3 sm:grid-cols-6 gap-4">
                @foreach($categories as $category)
                    <a wire:navigate href="{{ route('products', ['category' => $category->slug]) }}"
                       class="category-card group flex flex-col items-center gap-3"
                       data-reveal style="--reveal-delay: {{ min($loop->index, 5) * 70 }}ms">
                        <div class="w-full aspect-square rounded-xl overflow-hidden bg-gray-200 shadow-sm group-hover:shadow-md transition-all duration-300 group-hover:-translate-y-1">
                            <img src="{{ $category->products->first()?->image_url ?? 'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=300&q=80' }}"
                                 alt="{{ $category->name }}"
                                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                        </div>
                        <span class="text-xs font-medium text-gray-700 group-hover:text-deep transition-colors text-center">{{ $category->name }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ======================== PROMO BANNER WITH FASHION ICONS ======================== --}}
    <section class="py-16">
        <div class="max-w-4xl mx-auto px-4 text-center">
            <div class="promo-animated relative overflow-hidden rounded-[2rem] bg-deep text-on-teal p-10 shadow-2xl border border-coral/30"
                 data-reveal="scale">

                {{-- Fashion Icon Decorations (Background) --}}
                {{-- Shirt top-left --}}
                <svg class="promo-icon absolute -top-4 -left-4 w-32 h-32 text-on-teal/15" viewBox="0 0 100 100" fill="currentColor">
                    <path d="M35,10 L20,20 L10,35 L25,40 L25,80 L75,80 L75,40 L90,35 L80,20 L65,10 C62,18 55,22 50,22 C45,22 38,18 35,10 Z"/>
                    <rect x="38" y="10" width="24" height="12" rx="6" fill="currentColor"/>
                </svg>

                {{-- Pants bottom-right --}}
                <svg class="promo-icon absolute -bottom-4 -right-4 w-28 h-28 text-on-teal/15" viewBox="0 0 100 100" fill="currentColor">
                    <path d="M20,10 L80,10 L75,55 L60,90 L50,90 L45,55 L55,55 L50,55 L50,55 L40,90 L50,90 L25,55 Z"/>
                </svg>

                {{-- Hat top-right --}}
                <svg class="promo-icon absolute top-4 -right-6 w-24 h-24 text-on-teal/15 rotate-12" viewBox="0 0 100 60" fill="currentColor">
                    <ellipse cx="50" cy="50" rx="45" ry="10"/>
                    <path d="M25,50 Q30,15 50,10 Q70,15 75,50 Z"/>
                    <rect x="20" y="48" width="60" height="6" rx="3"/>
                </svg>

                {{-- Watch bottom-left --}}
                <svg class="promo-icon absolute bottom-4 -left-6 w-20 h-20 text-on-teal/15 -rotate-12" viewBox="0 0 60 60" fill="currentColor">
                    <rect x="22" y="5" width="16" height="10" rx="3"/>
                    <rect x="22" y="45" width="16" height="10" rx="3"/>
                    <circle cx="30" cy="30" r="16" fill="none" stroke="currentColor" stroke-width="4"/>
                    <circle cx="30" cy="30" r="2"/>
                    <line x1="30" y1="30" x2="30" y2="20" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/>
                    <line x1="30" y1="30" x2="38" y2="30" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>

                {{-- Handbag center-left --}}
                <svg class="absolute top-1/2 -translate-y-1/2 -left-10 w-24 h-24 text-on-teal/10 hidden lg:block" viewBox="0 0 80 80" fill="currentColor">
                    <path d="M15,35 L65,35 L60,65 L20,65 Z"/>
                    <path d="M28,35 C28,22 52,22 52,35" fill="none" stroke="currentColor" stroke-width="4" stroke-linecap="round"/>
                    <rect x="32" y="47" width="16" height="10" rx="5"/>
                </svg>

                {{-- Sneaker center-right --}}
                <svg class="absolute top-1/2 -translate-y-1/2 -right-10 w-28 h-24 text-on-teal/10 hidden lg:block" viewBox="0 0 100 70" fill="currentColor">
                    <path d="M5,55 Q10,25 35,20 L60,22 Q80,24 90,40 L90,55 Q80,60 5,60 Z"/>
                    <path d="M35,20 L38,10 L55,12 L60,22" fill="none" stroke="currentColor" stroke-width="3"/>
                    <path d="M5,55 L90,55" stroke="currentColor" stroke-width="2" stroke-dasharray="6,4" fill="none"/>
                </svg>

                {{-- Small scattered dots decoration --}}
                <div class="absolute inset-0 pointer-events-none" aria-hidden="true">
                    <div class="absolute top-8 left-1/4 w-1.5 h-1.5 rounded-full bg-on-teal/20"></div>
                    <div class="absolute top-16 right-1/3 w-1 h-1 rounded-full bg-on-teal/15"></div>
                    <div class="absolute bottom-8 left-1/3 w-2 h-2 rounded-full bg-on-teal/15"></div>
                    <div class="absolute bottom-12 right-1/4 w-1 h-1 rounded-full bg-on-teal/20"></div>
                    <div class="absolute top-1/2 left-16 w-1.5 h-1.5 rounded-full bg-on-teal/10"></div>
                    <div class="absolute top-1/3 right-16 w-1 h-1 rounded-full bg-on-teal/10"></div>
                </div>

                {{-- Content --}}
                <div class="relative z-10">
                    <p class="text-sm font-semibold uppercase tracking-[0.25em] text-cream mb-2">Fashion Premium</p>
                    <h2 class="text-3xl sm:text-4xl font-bold text-on-teal">Harga Minimun Kualitas Premium</h2>
                    <p class="mt-3 text-on-teal/85 max-w-lg mx-auto leading-relaxed">
                        Temukan gaya terbaikmu dengan koleksi fashion premium. Dari kasual hingga formal, semua ada di sini.
                    </p>
                    <a wire:navigate href="{{ route('products') }}"
                       class="promo-cta btn-cta inline-block mt-8 px-10 py-3.5 text-sm shadow-lg hover:-translate-y-0.5">
                        Belanja Sekarang
                    </a>
                </div>
            </div>
        </div>
    </section>

    {{-- ======================== NEW ARRIVALS ======================== --}}
    <section class="section-arrivals relative overflow-hidden py-14">
        <div class="section-deco" aria-hidden="true">
            <span class="runway-stitch"></span>
            <span class="runway-dot runway-dot-1"></span>
            <span class="runway-dot runway-dot-2"></span>
            <span class="runway-dot runway-dot-3"></span>
            {{-- Fashion line-art --}}
            <svg class="section-fashion top-[16%] left-[4%] size-16 sm:size-20" viewBox="0 0 90 80" fill="none" stroke="currentColor" stroke-width="2.3">
                <path d="M18 38h54l-5 28H23Z" stroke-linejoin="round"/>
                <path d="M32 38c0-12 26-12 26 0" stroke-linecap="round"/>
                <rect x="36" y="48" width="18" height="10" rx="4"/>
            </svg>
            <svg class="section-fashion top-[18%] right-[5%] size-16 sm:size-20" viewBox="0 0 100 60" fill="none" stroke="currentColor" stroke-width="2.2" style="--fashion-delay:-2.3s">
                <ellipse cx="50" cy="48" rx="38" ry="8"/>
                <path d="M28 48 Q32 18 50 14 Q68 18 72 48" stroke-linejoin="round"/>
            </svg>
            <svg class="section-fashion bottom-[14%] right-[12%] size-16 sm:size-20 hidden sm:block" viewBox="0 0 100 70" fill="none" stroke="currentColor" stroke-width="2.2" style="--fashion-delay:-4.2s">
                <path d="M8 52 Q14 24 38 20 L62 22 Q80 24 90 40 L90 52 Q78 58 8 56 Z" stroke-linejoin="round"/>
                <path d="M38 20 42 10 56 12 62 22" stroke-linecap="round"/>
            </svg>
            <svg class="section-fashion bottom-[18%] left-[42%] h-11 w-20 hidden md:block" viewBox="0 0 100 100" fill="none" stroke="currentColor" stroke-width="2.2" style="--fashion-delay:-3.1s">
                <path d="M50 18c0-9 13-10 13-1 0 7-13 8-13 17" stroke-linecap="round"/>
                <path d="M50 34 22 58c-4 3-2 8 3 8h50c5 0 7-5 3-8L50 34Z" stroke-linejoin="round"/>
            </svg>
        </div>
        <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-end justify-between mb-8">
                <div data-reveal="left">
                    <h2 class="text-2xl font-bold text-gray-900">New Arrivals</h2>
                    <div class="gold-line mb-0"></div>
                </div>
                <a wire:navigate href="{{ route('products') }}"
                   class="text-sm font-medium bg-deep !text-beige border border-deep px-4 py-1.5 hover:bg-deep-dark hover:border-deep-dark transition-colors">
                    Lihat Semua
                </a>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                @foreach($newArrivals as $index => $product)
                    <div class="product-card home-product-card bg-panel rounded-xl overflow-hidden shadow-sm"
                         data-reveal style="--reveal-delay: {{ min($index, 5) * 80 }}ms">
                        <div class="card-img-wrap relative aspect-[4/5]">
                            <img src="{{ $product->image_url }}" alt="{{ $product->name }}" loading="lazy" class="w-full h-full object-cover">
                            @if($product->discount_percent)
                                <span class="sale-badge absolute top-3 right-3 bg-coral text-on-coral text-xs font-bold px-2 py-1">-{{ $product->discount_percent }}%</span>
                            @endif
                            <x-product-condition-badge :condition="$product->condition" class="absolute top-3 left-3" />
                        </div>
                        <div class="p-4">
                            <p class="text-xs text-ink uppercase tracking-wide">{{ $product->category->name }}</p>
                            <h3 class="mt-0.5 font-semibold text-gray-900 text-sm line-clamp-1">{{ $product->name }}</h3>
                            <div class="mt-1 flex items-center gap-2">
                                <span class="text-coral font-bold">{{ $product->formatted_price }}</span>
                                @if($product->formatted_original_price)
                                    <span class="text-ink text-xs line-through">{{ $product->formatted_original_price }}</span>
                                @endif
                            </div>
                            <div class="card-actions mt-3 grid grid-cols-2 gap-2">
                                <a wire:navigate href="{{ route('product.detail', $product->slug) }}"
                                   class="text-center text-xs font-semibold bg-deep !text-beige border border-deep hover:bg-deep-dark py-2 transition-colors">
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
                                        data-in-wishlist="{{ in_array($product->id, $wishlistIds, true) ? '1' : '0' }}"
                                        class="text-xs font-semibold bg-cream hover:bg-coral text-on-cream hover:text-on-coral py-2 transition-colors flex items-center justify-center">
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
        const endTime = {{ (int) $flashSaleEndsAt }};
        function pad(n) { return String(n).padStart(2, '0'); }
        function tick() {
            const diff = endTime - Date.now();
            if (diff <= 0) {
                document.getElementById('cd-hours').textContent = '00';
                document.getElementById('cd-minutes').textContent = '00';
                document.getElementById('cd-seconds').textContent = '00';
                return;
            }
            document.getElementById('cd-hours').textContent   = pad(Math.floor(diff / 3600000));
            document.getElementById('cd-minutes').textContent = pad(Math.floor((diff % 3600000) / 60000));
            document.getElementById('cd-seconds').textContent = pad(Math.floor((diff % 60000) / 1000));
        }
        tick();
        setInterval(tick, 1000);
    })();
    </script>
</div>



