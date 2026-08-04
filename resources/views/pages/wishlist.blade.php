<?php

use App\Models\Wishlist;
use Livewire\Component;

new class extends Component {
    public function remove(int $productId): void
    {
        Wishlist::where('session_id', session()->getId())
            ->where('product_id', $productId)
            ->delete();

        $this->dispatch('wishlist-updated', count: Wishlist::countForSession());
    }

    public function render()
    {
        return view('pages.wishlist', [
            'items' => Wishlist::forSession(),
        ]);
    }
};
?>

<div class="animate-fade-in">
    <section class="bg-teal py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h1 class="text-3xl font-bold text-ink">Wishlist</h1>
            <div class="gold-line mt-2 mb-0"></div>
        </div>
    </section>

    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        @if($items->isEmpty())
            <div class="bg-panel rounded-2xl shadow-sm border border-gray-100 p-12 text-center">
                <p class="text-lg font-semibold text-gray-900">Wishlist masih kosong</p>
                <p class="mt-2 text-sm text-ink">Simpan produk favorit dari halaman detail produk.</p>
                <a wire:navigate href="{{ route('products') }}"
                   class="mt-6 inline-flex rounded-full bg-beige px-6 py-3 text-sm font-semibold text-deep hover:bg-coral transition-colors">
                    Lihat Produk
                </a>
            </div>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                @foreach($items as $item)
                    @php $product = $item->product; @endphp
                    @continue(! $product)
                    <div class="bg-panel rounded-xl overflow-hidden shadow-sm border border-gray-100" wire:key="wish-{{ $item->id }}">
                        <a wire:navigate href="{{ route('product.detail', $product->slug) }}" class="block aspect-[4/3] overflow-hidden bg-gray-100">
                            <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="w-full h-full object-cover">
                        </a>
                        <div class="p-4">
                            <a wire:navigate href="{{ route('product.detail', $product->slug) }}" class="font-semibold text-gray-900 hover:text-deep line-clamp-1">
                                {{ $product->name }}
                            </a>
                            <p class="text-sm text-ink mt-0.5">{{ $product->category?->name }}</p>
                            <p class="text-deep font-bold mt-2">{{ $product->formatted_price }}</p>
                            <div class="mt-4 flex gap-2">
                                <a wire:navigate href="{{ route('product.detail', $product->slug) }}"
                                   class="flex-1 text-center text-sm font-semibold rounded-lg bg-cream hover:bg-coral text-on-cream hover:text-on-coral py-2 transition-colors">
                                    Lihat
                                </a>
                                <button wire:click="remove({{ $product->id }})"
                                        class="px-3 rounded-lg border border-gray-200 text-ink hover:text-red-600 hover:border-red-200 transition-colors"
                                        title="Hapus">
                                    ✕
                                </button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </section>
</div>
