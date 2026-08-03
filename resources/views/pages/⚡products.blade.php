<?php

use App\Models\Category;
use App\Models\Product;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public ?string $selectedCategory = null;

    public string $search = '';

    public string $sortBy = 'latest';

    public int $priceMin = 0;

    public int $priceMax = 0;

    public bool $filterBestSeller = false;

    public bool $filterNewArrival = false;

    public bool $filterFlashSale = false;

    public bool $showFilters = false;

    protected $queryString = [
        'selectedCategory' => ['except' => null],
        'search' => ['except' => ''],
        'sortBy' => ['except' => 'latest'],
    ];

    public function mount(): void
    {
        $this->selectedCategory = request()->query('category', $this->selectedCategory);
        $this->search = request()->query('search', '');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedSortBy(): void
    {
        $this->resetPage();
    }

    public function updatedSelectedCategory(): void
    {
        $this->resetPage();
    }

    public function updatedPriceMin(): void
    {
        $this->resetPage();
    }

    public function updatedPriceMax(): void
    {
        $this->resetPage();
    }

    public function updatedFilterBestSeller(): void
    {
        $this->resetPage();
    }

    public function updatedFilterNewArrival(): void
    {
        $this->resetPage();
    }

    public function updatedFilterFlashSale(): void
    {
        $this->resetPage();
    }

    public function filterByCategory(?string $slug): void
    {
        $this->selectedCategory = $slug;
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->priceMin = 0;
        $this->priceMax = 0;
        $this->filterBestSeller = false;
        $this->filterNewArrival = false;
        $this->filterFlashSale = false;
        $this->resetPage();
    }

    public function applySuggestion(string $suggestion): void
    {
        $this->search = $suggestion;
        $this->resetPage();
    }

    public function getHasActiveFiltersProperty(): bool
    {
        return $this->priceMin > 0
            || $this->priceMax > 0
            || $this->filterBestSeller
            || $this->filterNewArrival
            || $this->filterFlashSale;
    }

    public function render()
    {
        $perPage = 12;
        $page = (int) request()->query('page', 1);

        if (trim($this->search) === '') {
            $query = Product::with('category')
                ->when($this->selectedCategory, function ($q) {
                    $q->whereHas('category', fn ($c) => $c->where('slug', $this->selectedCategory));
                })
                ->when($this->priceMin > 0, fn ($q) => $q->where('price', '>=', $this->priceMin))
                ->when($this->priceMax > 0, fn ($q) => $q->where('price', '<=', $this->priceMax))
                ->when($this->filterBestSeller, fn ($q) => $q->where('is_best_seller', true))
                ->when($this->filterNewArrival, fn ($q) => $q->where('is_new_arrival', true))
                ->when($this->filterFlashSale, fn ($q) => $q->where('is_flash_sale', true));

            $query = match ($this->sortBy) {
                'price-low-high' => $query->orderBy('price', 'asc'),
                'price-high-low' => $query->orderBy('price', 'desc'),
                'name-asc' => $query->orderBy('name', 'asc'),
                'name-desc' => $query->orderBy('name', 'desc'),
                'popular' => $query->orderByDesc('is_best_seller')->latest(),
                default => $query->latest(),
            };

            return view('pages.⚡products', [
                'categories' => Category::all(),
                'products' => $query->paginate($perPage),
            ]);
        }

        $this->searchSuggestion = null;
        $search = trim(mb_strtolower($this->search));

        if (config('meilisearch.enabled')) {
            try {
                $meili = app(\App\Services\MeiliSearchService::class);
                $hits = $meili->search($search, ['limit' => 1000]);
                $ids = array_map(fn ($h) => $h['id'], $hits);
                $products = Product::with('category')->whereIn('id', $ids)->get()->keyBy('id');
                $ordered = [];

                foreach ($ids as $id) {
                    if (isset($products[$id])) {
                        $ordered[] = $products[$id];
                    }
                }

                $top = $ordered[0] ?? null;
                if ($top && mb_strtolower($top->name) !== $search) {
                    $this->searchSuggestion = $top->name;
                }

                $total = count($ordered);
                $slice = array_slice($ordered, ($page - 1) * $perPage, $perPage);
                $paginator = new LengthAwarePaginator(
                    $slice,
                    $total,
                    $perPage,
                    $page,
                    ['path' => request()->url(), 'query' => request()->query()]
                );

                return view('pages.⚡products', [
                    'categories' => Category::all(),
                    'products' => $paginator,
                ]);
            } catch (\Throwable $e) {
                // Fall back to local fuzzy scoring below.
            }
        }

        $substr = mb_substr($search, 0, 2);
        $candidates = Product::with('category')
            ->when($this->selectedCategory, function ($q) {
                $q->whereHas('category', fn ($c) => $c->where('slug', $this->selectedCategory));
            })
            ->where(function ($q) use ($substr) {
                $q->where('name', 'like', '%'.$substr.'%')
                    ->orWhere('description', 'like', '%'.$substr.'%')
                    ->orWhereHas('category', fn ($c) => $c->where('name', 'like', '%'.$substr.'%'));
            })
            ->limit(1000)
            ->get();

        $scored = [];
        foreach ($candidates as $p) {
            $name = mb_strtolower($p->name);
            $desc = mb_strtolower($p->description ?? '');
            $cat = mb_strtolower($p->category->name ?? '');

            similar_text($search, $name, $namePct);
            similar_text($search, $desc, $descPct);
            similar_text($search, $cat, $catPct);

            $lev = @levenshtein($search, $name);
            $maxLen = max(mb_strlen($search), mb_strlen($name), 1);
            $normLev = 1 - ($lev / $maxLen);
            $normLevPct = max(0, min(100, $normLev * 100));

            $score = ($namePct * 0.6) + ($normLevPct * 0.2) + ($descPct * 0.15) + ($catPct * 0.05);

            if (mb_stripos($name, $search) !== false) {
                $score += 20;
            }

            if ($name === $search) {
                $score += 40;
            }

            $scored[] = ['product' => $p, 'score' => $score];
        }

        usort($scored, fn ($a, $b) => $b['score'] <=> $a['score']);

        $top = $scored[0] ?? null;
        if ($top && $top['score'] >= 45 && mb_strtolower($top['product']->name) !== $search) {
            $this->searchSuggestion = $top['product']->name;
        }

        $ordered = array_map(fn ($r) => $r['product'], $scored);
        $total = count($ordered);
        $slice = array_slice($ordered, ($page - 1) * $perPage, $perPage);
        $paginator = new LengthAwarePaginator(
            $slice,
            $total,
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        return view('pages.⚡products', [
            'categories' => Category::all(),
            'products' => $paginator,
        ]);
    }
};
?>

<div class="animate-fade-in">
    {{-- Page header --}}
    <section class="bg-[#8BDFDD] py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h1 class="text-3xl font-bold text-[#4F252E]">Semua Produk</h1>
            <div class="gold-line mt-2 mb-0"></div>
        </div>
    </section>

    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        {{-- Category pills --}}
        <div class="flex flex-wrap gap-2 mb-5">
            <button wire:click="filterByCategory(null)"
                    class="px-4 py-1.5 rounded-full text-sm font-medium transition-all
                           {{ is_null($selectedCategory)
                              ? 'bg-[#FFF6DE] text-[#1F2A2A] shadow'
                              : 'bg-white text-[#4F252E] hover:bg-gray-100 shadow-sm border border-gray-200' }}">
                Semua
            </button>
            @foreach($categories as $cat)
                <button wire:click="filterByCategory('{{ $cat->slug }}')"
                        class="px-4 py-1.5 rounded-full text-sm font-medium transition-all
                               {{ $selectedCategory === $cat->slug
                                  ? 'bg-[#FFF6DE] text-[#1F2A2A] shadow'
                                  : 'bg-white text-[#4F252E] hover:bg-gray-100 shadow-sm border border-gray-200' }}">
                    {{ $cat->name }}
                </button>
            @endforeach
        </div>

        {{-- Sort + Filter Controls --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
            <div class="flex items-center gap-2">
                <button wire:click="$toggle('showFilters')"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border text-sm font-medium transition-all
                               {{ $showFilters ? 'bg-[#8BDFDD] text-[#4F252E] border-[#1F2A2A]' : 'bg-white text-gray-700 border-gray-200 hover:bg-gray-50' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-9.75 0h9.75"/>
                    </svg>
                    Filter
                    @if($this->hasActiveFilters)
                        <span class="bg-[#FFF6DE] text-[#1F2A2A] text-xs font-bold rounded-full w-4.5 h-4.5 flex items-center justify-center leading-none">!</span>
                    @endif
                </button>
                @if($this->hasActiveFilters)
                    <button wire:click="resetFilters"
                            class="text-sm text-red-500 hover:text-red-600 font-medium underline underline-offset-2 transition-colors">
                        Reset Filter
                    </button>
                @endif
            </div>

            <div class="flex items-center gap-2">
                <label for="sortBy" class="text-sm font-medium text-gray-700 whitespace-nowrap">Urutkan:</label>
                <select id="sortBy" wire:model.live="sortBy"
                        class="rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm text-gray-700 shadow-sm focus:border-[#FFF6DE] focus:outline-none focus:ring-2 focus:ring-[#FFF6DE]/20">
                    <option value="latest">Terbaru</option>
                    <option value="popular">Terpopuler</option>
                    <option value="price-low-high">Harga Terendah</option>
                    <option value="price-high-low">Harga Tertinggi</option>
                    <option value="name-asc">A–Z</option>
                    <option value="name-desc">Z–A</option>
                </select>
            </div>
        </div>

        {{-- Suggestion / fuzzy notice --}}
        @if(trim($search) !== '')
            @if($searchSuggestion)
                <div class="mb-4 text-sm text-gray-700">
                    <span class="text-[#4F252E]">Mungkin yang Anda maksud:</span>
                    <button wire:click="applySuggestion('{{ $searchSuggestion }}')" class="font-semibold text-[#1F2A2A] ml-2">{{ $searchSuggestion }}</button>
                </div>
            @else
                <div class="mb-4 text-sm text-[#4F252E]">Menampilkan hasil yang paling mendekati pencarian Anda</div>
            @endif
        @endif

        {{-- Filter Panel --}}
        @if($showFilters)
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 mb-6 animate-fade-up">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">

                    {{-- Price Range --}}
                    <div>
                        <p class="text-xs font-semibold text-[#4F252E] uppercase tracking-wider mb-3">Rentang Harga</p>
                        <div class="flex items-center gap-2">
                            <div class="flex-1">
                                <label class="text-xs text-[#4F252E] mb-1 block">Min (Rp)</label>
                                <input wire:model.blur="priceMin"
                                       type="number" min="0" step="10000"
                                       placeholder="0"
                                       class="w-full px-3 py-2 text-sm rounded-lg border border-gray-200 focus:border-[#FFF6DE] focus:outline-none focus:ring-2 focus:ring-[#FFF6DE]/20">
                            </div>
                            <span class="text-[#4F252E] mt-5">—</span>
                            <div class="flex-1">
                                <label class="text-xs text-[#4F252E] mb-1 block">Max (Rp)</label>
                                <input wire:model.blur="priceMax"
                                       type="number" min="0" step="10000"
                                       placeholder="0 = semua"
                                       class="w-full px-3 py-2 text-sm rounded-lg border border-gray-200 focus:border-[#FFF6DE] focus:outline-none focus:ring-2 focus:ring-[#FFF6DE]/20">
                            </div>
                        </div>
                        @if($priceMin > 0 || $priceMax > 0)
                            <p class="text-xs text-[#1F2A2A] mt-2 font-medium">
                                @if($priceMin > 0 && $priceMax > 0)
                                    Rp{{ number_format($priceMin, 0, ',', '.') }} – Rp{{ number_format($priceMax, 0, ',', '.') }}
                                @elseif($priceMin > 0)
                                    Min Rp{{ number_format($priceMin, 0, ',', '.') }}
                                @else
                                    Max Rp{{ number_format($priceMax, 0, ',', '.') }}
                                @endif
                            </p>
                        @endif
                    </div>

                    {{-- Label Filter --}}
                    <div>
                        <p class="text-xs font-semibold text-[#4F252E] uppercase tracking-wider mb-3">Label Produk</p>
                        <div class="space-y-2.5">
                            <label class="flex items-center gap-2.5 cursor-pointer group">
                                <input wire:model.live="filterBestSeller" type="checkbox"
                                       class="w-4 h-4 rounded border-gray-300 text-[#1F2A2A] focus:ring-[#FFF6DE]/20 cursor-pointer">
                                <span class="text-sm text-gray-700 group-hover:text-[#1F2A2A] transition-colors flex items-center gap-1.5">
                                    <span class="inline-block w-2.5 h-2.5 rounded-full bg-yellow-400"></span>
                                    Best Seller
                                </span>
                            </label>
                            <label class="flex items-center gap-2.5 cursor-pointer group">
                                <input wire:model.live="filterNewArrival" type="checkbox"
                                       class="w-4 h-4 rounded border-gray-300 text-[#1F2A2A] focus:ring-[#FFF6DE]/20 cursor-pointer">
                                <span class="text-sm text-gray-700 group-hover:text-[#1F2A2A] transition-colors flex items-center gap-1.5">
                                    <span class="inline-block w-2.5 h-2.5 rounded-full bg-blue-400"></span>
                                    New Arrival
                                </span>
                            </label>
                            <label class="flex items-center gap-2.5 cursor-pointer group">
                                <input wire:model.live="filterFlashSale" type="checkbox"
                                       class="w-4 h-4 rounded border-gray-300 text-[#1F2A2A] focus:ring-[#FFF6DE]/20 cursor-pointer">
                                <span class="text-sm text-gray-700 group-hover:text-[#1F2A2A] transition-colors flex items-center gap-1.5">
                                    <span class="inline-block w-2.5 h-2.5 rounded-full bg-[#FFF6DE]"></span>
                                    Flash Sale
                                </span>
                            </label>
                        </div>
                    </div>

                    {{-- Active Filter Summary --}}
                    <div>
                        <p class="text-xs font-semibold text-[#4F252E] uppercase tracking-wider mb-3">Filter Aktif</p>
                        @if($this->hasActiveFilters)
                            <div class="flex flex-wrap gap-1.5">
                                @if($priceMin > 0)
                                    <span class="inline-flex items-center gap-1 bg-[#FFF6DE]/10 text-[#F48F68] text-xs font-medium px-2.5 py-1 rounded-full border border-[#FFF6DE]/20">
                                        Min Rp{{ number_format($priceMin, 0, ',', '.') }}
                                        <button wire:click="$set('priceMin', 0)" class="hover:text-red-500">&times;</button>
                                    </span>
                                @endif
                                @if($priceMax > 0)
                                    <span class="inline-flex items-center gap-1 bg-[#FFF6DE]/10 text-[#F48F68] text-xs font-medium px-2.5 py-1 rounded-full border border-[#FFF6DE]/20">
                                        Max Rp{{ number_format($priceMax, 0, ',', '.') }}
                                        <button wire:click="$set('priceMax', 0)" class="hover:text-red-500">&times;</button>
                                    </span>
                                @endif
                                @if($filterBestSeller)
                                    <span class="inline-flex items-center gap-1 bg-yellow-50 text-yellow-700 text-xs font-medium px-2.5 py-1 rounded-full border border-yellow-200">
                                        Best Seller
                                        <button wire:click="$set('filterBestSeller', false)" class="hover:text-red-500">&times;</button>
                                    </span>
                                @endif
                                @if($filterNewArrival)
                                    <span class="inline-flex items-center gap-1 bg-blue-50 text-blue-700 text-xs font-medium px-2.5 py-1 rounded-full border border-blue-200">
                                        New Arrival
                                        <button wire:click="$set('filterNewArrival', false)" class="hover:text-red-500">&times;</button>
                                    </span>
                                @endif
                                @if($filterFlashSale)
                                    <span class="inline-flex items-center gap-1 bg-[#FFF6DE]/10 text-[#F48F68] text-xs font-medium px-2.5 py-1 rounded-full border border-[#FFF6DE]/20">
                                        Flash Sale
                                        <button wire:click="$set('filterFlashSale', false)" class="hover:text-red-500">&times;</button>
                                    </span>
                                @endif
                            </div>
                        @else
                            <p class="text-sm text-[#4F252E]">Belum ada filter aktif.</p>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        {{-- Product grid --}}
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 sm:gap-5">
            @forelse($products as $product)
                <div class="product-card bg-white rounded-xl overflow-hidden shadow-sm">
                    <a href="{{ route('product.detail', $product->slug) }}"
                       class="card-img-wrap block relative aspect-[4/5]">
                        <img src="{{ $product->image_url }}"
                             alt="{{ $product->name }}"
                             loading="lazy"
                             class="w-full h-full object-cover">
                        @if($product->discount_percent)
                            <span class="absolute top-2 left-2 bg-[#FFF6DE] text-[#1F2A2A] text-xs font-bold px-2 py-0.5 rounded">SALE</span>
                            <span class="absolute top-2 right-2 bg-[#FFF6DE] text-[#1F2A2A] text-xs font-bold px-1.5 py-0.5 rounded">-{{ $product->discount_percent }}%</span>
                        @endif
                        {{-- Label badges --}}
                        <div class="absolute bottom-2 left-2 flex flex-col gap-1">
                            @if($product->is_best_seller)
                                <span class="bg-yellow-400/90 text-yellow-900 text-[10px] font-bold px-2 py-0.5 rounded-full">⭐ Best</span>
                            @endif
                            @if($product->is_new_arrival)
                                <span class="bg-blue-500/90 text-[#4F252E] text-[10px] font-bold px-2 py-0.5 rounded-full">New</span>
                            @endif
                        </div>
                    </a>

                    <div class="p-3 sm:p-4">
                        <a href="{{ route('product.detail', $product->slug) }}">
                            <p class="text-[10px] sm:text-xs text-[#4F252E] uppercase tracking-wide">{{ $product->category->name }}</p>
                            <h3 class="mt-0.5 text-sm font-semibold text-gray-900 transition-colors line-clamp-1">{{ $product->name }}</h3>
                            <div class="mt-1 flex items-center gap-1.5 flex-wrap">
                                <span class="text-[#1F2A2A] font-bold text-sm">{{ $product->formatted_price }}</span>
                                @if($product->formatted_original_price)
                                    <span class="text-[#4F252E] text-xs line-through">{{ $product->formatted_original_price }}</span>
                                @endif
                            </div>
                            {{-- Mini rating --}}
                            @if($product->reviews_count > 0)
                                <div class="flex items-center gap-1 mt-1">
                                    <svg class="size-3 text-yellow-400 fill-yellow-400" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                    <span class="text-[10px] text-[#4F252E] font-medium">{{ $product->average_rating }} ({{ $product->reviews_count }})</span>
                                </div>
                            @endif
                        </a>

                        <div class="mt-3 grid grid-cols-2 gap-1.5">
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
                                    class="flex items-center justify-center bg-[#FFF6DE] hover:bg-[#F48F68] text-[#1F2A2A] py-2 rounded transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full py-16 text-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-14 text-[#4F252E] mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5m6 4.125 2.25 2.25m0 0 2.25 2.25M12 13.875l2.25-2.25M12 13.875l-2.25 2.25"/>
                    </svg>
                    <p class="text-[#4F252E] font-medium">Tidak ada produk ditemukan.</p>
                    @if($this->hasActiveFilters)
                        <button wire:click="resetFilters" class="mt-3 text-sm text-[#1F2A2A] hover:underline">Reset semua filter</button>
                    @endif
                </div>
            @endforelse
        </div>

        <div class="mt-8">
            {{ $products->links() }}
        </div>
    </section>
</div>



