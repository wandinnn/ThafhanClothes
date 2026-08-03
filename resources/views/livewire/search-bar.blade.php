<?php

use App\Models\Product;
use Livewire\Component;

new class extends Component
{
    public string $searchQuery = '';

    public array $searchResults = [];

    public bool $showResults = false;

    public function updatedSearchQuery(): void
    {
        $this->showResults = false;

        $raw = trim($this->searchQuery);
        if (strlen($raw) < 2) {
            $this->searchResults = [];

            return;
        }

        $search = $raw;

        // If Meilisearch is enabled, use it for fast typo-tolerant results
        if (config('meilisearch.enabled')) {
            try {
                $meili = app(\App\Services\MeiliSearchService::class);
                $hits = $meili->search($search, ['limit' => 6]);
                $this->searchResults = array_map(function ($h) {
                    return [
                        'id' => $h['id'] ?? $h['ID'] ?? null,
                        'name' => $h['name'] ?? 'Unknown',
                        'slug' => $h['slug'] ?? null,
                        'price' => $h['price'] ?? 0,
                        'image_url' => $h['image_url'] ?? null,
                    ];
                }, $hits);
                $this->showResults = count($this->searchResults) > 0;
                return;
            } catch (\Throwable $e) {
                // fallback to DB-based search on error
            }
        }

        // 1) First try fast LIKE-based search (best results)
        $initial = Product::where(function ($q) use ($search) {
            $q->where('name', 'like', '%'.$search.'%')
                ->orWhere('description', 'like', '%'.$search.'%');

            $words = preg_split('/\s+/', $search);
            foreach ($words as $word) {
                if (strlen($word) > 1) {
                    $q->orWhere('name', 'like', '%'.$word.'%')
                        ->orWhere('description', 'like', '%'.$word.'%');
                }
            }
        })
            ->select('id', 'name', 'slug', 'price', 'image_url')
            ->limit(6)
            ->get()
            ->toArray();

        // If we already have enough good matches, return them
        if (count($initial) >= 6) {
            $this->searchResults = $initial;
            $this->showResults = true;

            return;
        }

        // 2) Typo-tolerant fuzzy search: fetch candidates and score them in PHP
        // Fetch a broader candidate set (by short substring) to evaluate
        $substr = mb_substr($search, 0, 2);
        $candidates = Product::where(function ($q) use ($substr, $search) {
            $q->where('name', 'like', '%'.$substr.'%')
                ->orWhere('description', 'like', '%'.$substr.'%');

            $words = preg_split('/\s+/', $search);
            foreach ($words as $word) {
                if (strlen($word) > 1) {
                    $q->orWhere('name', 'like', '%'.$word.'%')
                        ->orWhere('description', 'like', '%'.$word.'%');
                }
            }
        })
            ->select('id', 'name', 'slug', 'price', 'image_url', 'description')
            ->limit(200)
            ->get();

        $results = [];
        $seen = [];

        // seed with initial exact/like matches
        foreach ($initial as $row) {
            $results[] = $row;
            $seen[$row['id']] = true;
        }

        $searchLower = mb_strtolower($search);
        foreach ($candidates as $p) {
            if (isset($seen[$p->id])) {
                continue;
            }

            $nameLower = mb_strtolower($p->name);
            $descLower = mb_strtolower($p->description ?? '');

            // similarity percent between whole search and product name
            $percent = 0;
            similar_text($searchLower, $nameLower, $percent);
            $lev = levenshtein($searchLower, $nameLower);

            // also compare against individual words from the product name
            $nameWords = preg_split('/\s+/', $nameLower);
            foreach ($nameWords as $nw) {
                $pct = 0;
                similar_text($searchLower, $nw, $pct);
                $lvw = levenshtein($searchLower, $nw);
                if ($pct > $percent) {
                    $percent = $pct;
                }
                if ($lvw < $lev) {
                    $lev = $lvw;
                }
            }

            // accept if similarity high enough or edit distance small relative to length
            $maxLev = max(2, (int) ceil(mb_strlen($searchLower) * 0.25));
            if ($percent >= 55 || $lev <= $maxLev) {
                $results[] = [
                    'id' => $p->id,
                    'name' => $p->name,
                    'slug' => $p->slug,
                    'price' => $p->price,
                    'image_url' => $p->image_url,
                ];
                $seen[$p->id] = true;
            }

            if (count($results) >= 6) {
                break;
            }
        }

        // limit to top 6
        $this->searchResults = array_slice($results, 0, 6);
        $this->showResults = count($this->searchResults) > 0;
    }

    public function goToProduct(string $slug): void
    {
        $this->redirect(route('product.detail', $slug));
    }

    public function submitSearch(): void
    {
        if (empty(trim($this->searchQuery))) {
            return;
        }
        $this->redirect(route('products', ['search' => trim($this->searchQuery)]));
    }

    public function render()
    {
        return view('livewire.search-bar');
    }
};
?>

<div class="hidden md:block">
    <form wire:submit.prevent="submitSearch" class="relative max-w-md">
        <div class="relative">
            <input 
                wire:model.live="searchQuery"
                wire:keydown.enter="submitSearch"
                type="text" 
                placeholder="Cari produk..." 
                class="w-full h-10 pl-4 pr-10 rounded-xl bg-[#8BDFDD] text-sm text-[#4F252E] placeholder-[#4F252E] outline-none focus:ring-2 focus:ring-[#FFF6DE]/60 transition-all"
            >
            <button 
                type="submit"
                class="absolute inset-y-0 right-0 px-3 flex items-center justify-center text-[#4F252E] hover:text-[#1F2A2A] transition-colors"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
                </svg>
            </button>

            {{-- Search Results Dropdown --}}
            @if($showResults && !empty($searchResults))
                <div class="absolute top-full left-0 right-0 mt-2 bg-white rounded-xl shadow-lg border border-gray-200 z-50 overflow-hidden">
                    <div class="max-h-96 overflow-y-auto">
                        @foreach($searchResults as $result)
                            <button 
                                wire:click="goToProduct('{{ $result['slug'] }}')"
                                type="button"
                                class="w-full px-4 py-3 hover:bg-gray-50 border-b border-gray-100 last:border-b-0 flex items-center gap-3 transition-colors group"
                            >
                                <img 
                                    src="{{ $result['image_url'] }}" 
                                    alt="{{ $result['name'] }}"
                                    class="w-10 h-10 rounded-lg object-cover flex-shrink-0"
                                >
                                <div class="flex-1 text-left min-w-0">
                                    <p class="text-sm font-medium text-gray-900 truncate group-hover:text-[#1F2A2A] transition-colors">
                                        {{ $result['name'] }}
                                    </p>
                                    <p class="text-xs text-[#1F2A2A] font-semibold">
                                        Rp{{ number_format($result['price'], 0, ',', '.') }}
                                    </p>
                                </div>
                            </button>
                        @endforeach
                    </div>

                    {{-- View All Results Link --}}
                    <button 
                        wire:click="submitSearch"
                        type="button"
                        class="w-full px-4 py-2.5 bg-gray-50 hover:bg-gray-100 text-sm font-medium text-gray-700 transition-colors border-t border-gray-200"
                    >
                        Lihat Semua Hasil →
                    </button>
                </div>
            @elseif(strlen(trim($searchQuery)) >= 2 && empty($searchResults))
                <div class="absolute top-full left-0 right-0 mt-2 bg-white rounded-xl shadow-lg border border-gray-200 z-50 p-4">
                    <p class="text-sm text-[#4F252E] text-center">Produk tidak ditemukan</p>
                </div>
            @endif
        </div>
    </form>
</div>




