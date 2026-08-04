<?php

use Livewire\Component;
use App\Models\Product;

new class extends Component {
    public string $query = '';

    public array $suggestions = [];

    public bool $open = false;

    public function updatedQuery(): void
    {
        if (mb_strlen(trim($this->query)) >= 2) {
            $this->suggestions = Product::with('category')
                ->where('name', 'like', '%' . $this->query . '%')
                ->take(6)
                ->get()
                ->map(fn ($p) => [
                    'name'     => $p->name,
                    'slug'     => $p->slug,
                    'price'    => $p->formatted_price,
                    'image'    => $p->image_url,
                    'category' => $p->category->name,
                ])
                ->toArray();
            $this->open = count($this->suggestions) > 0;
        } else {
            $this->suggestions = [];
            $this->open = false;
        }
    }

    public function search(): mixed
    {
        $this->open = false;
        if (trim($this->query)) {
            return redirect()->route('products', ['search' => $this->query]);
        }

        return null;
    }

    public function close(): void
    {
        $this->open = false;
    }

    public function render()
    {
        return view('components.search-bar');
    }
};
?>

<div class="relative flex-1 hidden sm:block max-w-lg" x-data @click.outside="$wire.close()">
    <form wire:submit="search">
        <div class="relative">
            <input
                wire:model.live.debounce.300ms="query"
                type="text"
                placeholder="Cari produk..."
                autocomplete="off"
                class="w-full h-9 pl-4 pr-10 rounded-md bg-beige-dark border border-deep/15 text-sm text-deep placeholder:text-ink/55 outline-none focus:ring-2 focus:ring-teal/35"
            >
            <button type="submit"
                    class="absolute inset-y-0 right-0 w-10 flex items-center justify-center bg-cream hover:bg-coral text-on-cream hover:text-on-coral rounded-r-md transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24"
                     stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
                </svg>
            </button>
        </div>
    </form>

    @if($open && count($suggestions) > 0)
        <div class="absolute top-full left-0 right-0 mt-1 bg-panel rounded-xl shadow-2xl border border-gray-100 z-[200] overflow-hidden">
            @foreach($suggestions as $s)
                <a wire:navigate href="{{ route('product.detail', $s['slug']) }}"
                   wire:click="close"
                   class="flex items-center gap-3 px-4 py-3 hover:bg-beige/5 transition-colors border-b border-gray-50 last:border-0">
                    <img src="{{ $s['image'] }}" alt="{{ $s['name'] }}"
                         class="w-10 h-10 rounded-lg object-cover flex-shrink-0 border border-gray-100">
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 truncate">{{ $s['name'] }}</p>
                        <p class="text-xs text-ink">{{ $s['category'] }}
                            <span class="text-deep font-semibold">· {{ $s['price'] }}</span>
                        </p>
                    </div>
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-4 text-ink flex-shrink-0" fill="none"
                         viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/>
                    </svg>
                </a>
            @endforeach
            <a wire:navigate href="{{ route('products', ['search' => $query]) }}"
               wire:click="close"
               class="flex items-center justify-center gap-2 px-4 py-2.5 bg-beige/5 hover:bg-beige/10 text-sm font-medium transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="size-4 text-deep" fill="none" viewBox="0 0 24 24"
                     stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
                </svg>
                <span class="text-ink">Lihat semua hasil untuk
                    <strong class="text-gray-800">"{{ $query }}"</strong>
                </span>
            </a>
        </div>
    @endif
</div>
