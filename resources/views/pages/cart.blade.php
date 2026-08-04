<?php

use App\Support\Cart;
use Livewire\Component;

new class extends Component {
    /**
     * @var array<int, array{key: string, product: \App\Models\Product, variant: \App\Models\ProductVariant|null, quantity: int, size: string, color: string}>
     */
    public array $products = [];

    public function mount(): void
    {
        $this->loadCart();
    }

    public function loadCart(): void
    {
        $this->products = Cart::lines()->values()->all();
    }

    public function updateQuantity(string $lineKey, int $quantity): void
    {
        $before = Cart::raw();
        Cart::updateQuantity($lineKey, $quantity);
        $after = Cart::raw();

        if (isset($before[$lineKey]) && isset($after[$lineKey]) && $after[$lineKey] < $quantity) {
            $line = Cart::lines()->firstWhere('key', $lineKey);

            if ($line) {
                $remaining = $line['variant']?->stock ?? $line['product']->stock;
                session()->flash('stockWarning', "Stok {$line['product']->name} tersisa {$remaining}, jumlah disesuaikan.");
            }
        }

        $this->loadCart();
        $this->dispatch('cart-updated', count: Cart::totalQuantity());
    }

    public function removeItem(string $lineKey): void
    {
        Cart::remove($lineKey);
        $this->loadCart();
        $this->dispatch('cart-updated', count: Cart::totalQuantity());
    }

    public function getSubtotalProperty(): float
    {
        return (float) collect($this->products)->sum(
            fn (array $item): int => $item['product']->price * $item['quantity']
        );
    }

    public function getTotalProperty(): float
    {
        return $this->subtotal;
    }

    public function render()
    {
        return view('pages.cart');
    }
};
?>

<div class="min-h-screen bg-gray-50">
    {{-- Page Header --}}
    <section class="bg-teal py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h1 class="text-3xl font-bold text-ink">Keranjang Belanja</h1>
            <div class="gold-line mt-2 mb-0"></div>
            <p class="mt-3 max-w-2xl text-sm !text-white leading-relaxed">
                Review barang pilihanmu sebelum checkout — pastikan ukuran, warna, dan jumlah sudah pas.
            </p>
        </div>
    </section>

    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        @if (session('stockWarning'))
            <div class="mb-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                {{ session('stockWarning') }}
            </div>
        @endif

        @if (empty($products))
            {{-- Empty Cart --}}
            <div class="bg-panel rounded-lg shadow-sm p-12 text-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="size-16 mx-auto mb-4 text-ink"
                     fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z"/>
                </svg>
                <h2 class="text-xl font-semibold text-gray-700 mb-2">Keranjang Anda Kosong</h2>
                <p class="text-ink mb-6">Mulai berbelanja dan tambahkan produk favorit ke keranjang Anda</p>
                <a wire:navigate href="{{ route('products') }}"
                   class="inline-block px-6 py-2.5 bg-cream hover:bg-coral text-on-cream hover:text-on-coral font-semibold rounded-lg transition-colors">
                    Lanjut Belanja
                </a>
            </div>
        @else
            {{-- Cart Items --}}
            <div class="grid lg:grid-cols-3 gap-8">
                {{-- Items List --}}
                <div class="lg:col-span-2">
                    <div class="bg-panel rounded-lg shadow-sm overflow-hidden">
                        <div class="p-6 border-b border-gray-200">
                            <h2 class="text-lg font-semibold text-gray-900">
                                Daftar Produk ({{ count($products) }})
                            </h2>
                        </div>

                        <div class="divide-y divide-gray-200">
                            @foreach ($products as $item)
                                @php
                                    $product = $item['product'];
                                    $quantity = $item['quantity'];
                                    $lineKey = $item['key'];
                                    $subtotal = $product->price * $quantity;
                                @endphp
                                <div class="p-6 flex gap-4 hover:bg-gray-50 transition-colors" wire:key="cart-{{ $lineKey }}">
                                    {{-- Product Image --}}
                                    <div class="w-24 h-24 flex-shrink-0 rounded-lg overflow-hidden bg-gray-100">
                                        <img src="{{ $product->image_url }}"
                                             alt="{{ $product->name }}"
                                             class="w-full h-full object-cover">
                                    </div>

                                    {{-- Product Details --}}
                                    <div class="flex-1">
                                        <div class="flex items-start justify-between mb-2">
                                            <div>
                                                <a wire:navigate href="{{ route('product.detail', $product->slug) }}"
                                                   class="text-base font-semibold text-gray-900 hover:text-deep transition-colors">
                                                    {{ $product->name }}
                                                </a>
                                                <p class="text-sm text-ink mt-0.5">
                                                    {{ $product->category->name }}
                                                </p>
                                                <p class="text-xs text-ink mt-0.5">
                                                    Ukuran {{ $item['size'] }} · Warna {{ $item['color'] }}
                                                </p>
                                                @php($remainingStock = $item['variant']?->stock ?? $product->stock)
                                                <p class="text-xs mt-0.5 {{ $remainingStock <= 5 ? 'text-red-600 font-semibold' : 'text-ink' }}">
                                                    @if($item['variant'])
                                                        {{ $remainingStock > 0 ? 'Stok varian: '.$remainingStock : 'Varian ini habis' }}
                                                    @else
                                                        {{ $product->formatted_stock }}
                                                    @endif
                                                </p>
                                            </div>
                                            <button wire:click="removeItem(@js($lineKey))"
                                                    class="text-ink hover:text-red-500 transition-colors">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="size-5"
                                                     fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
                                                </svg>
                                            </button>
                                        </div>

                                        <div class="flex items-end justify-between">
                                            {{-- Price --}}
                                            <div class="flex flex-col">
                                                <span class="text-sm text-ink line-through"
                                                      @if (!$product->original_price) style="display:none" @endif>
                                                    {{ $product->formatted_original_price }}
                                                </span>
                                                <span class="text-lg font-bold text-deep">
                                                    {{ $product->formatted_price }}
                                                </span>
                                            </div>

                                            {{-- Quantity Control --}}
                                            <div class="flex items-center gap-3 bg-gray-100 rounded-lg p-1">
                                                <button wire:click="updateQuantity(@js($lineKey), {{ $quantity - 1 }})"
                                                        class="w-8 h-8 flex items-center justify-center text-ink hover:text-deep transition-colors">
                                                    −
                                                </button>
                                                <span class="w-6 text-center font-semibold text-gray-900">
                                                    {{ $quantity }}
                                                </span>
                                                <button wire:click="updateQuantity(@js($lineKey), {{ $quantity + 1 }})"
                                                        class="w-8 h-8 flex items-center justify-center text-ink hover:text-deep transition-colors">
                                                    +
                                                </button>
                                            </div>
                                        </div>

                                        {{-- Subtotal --}}
                                        <div class="mt-3 text-right">
                                            <span class="text-sm text-ink">Subtotal: </span>
                                            <span class="font-semibold text-gray-900">
                                                Rp{{ number_format($subtotal, 0, ',', '.') }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Order Summary --}}
                <div class="lg:col-span-1">
                    <div class="bg-panel rounded-lg shadow-sm p-6 sticky top-24">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">Ringkasan Belanja</h2>

                        <div class="space-y-3 mb-6">
                            <div class="flex justify-between text-sm">
                                <span class="text-ink">Subtotal</span>
                                <span class="font-semibold text-gray-900">
                                    Rp{{ number_format($this->subtotal, 0, ',', '.') }}
                                </span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-ink">Ongkir</span>
                                <span class="font-semibold text-green-600">Dihitung di checkout</span>
                            </div>
                            <div class="border-t border-gray-200 pt-3 flex justify-between">
                                <span class="font-semibold text-gray-900">Total</span>
                                <span class="text-xl font-bold text-deep">
                                    Rp{{ number_format($this->total, 0, ',', '.') }}
                                </span>
                            </div>
                        </div>

                        <a wire:navigate href="{{ route('checkout') }}"
                           class="block w-full text-center px-6 py-3 bg-cream hover:bg-coral text-on-cream hover:text-on-coral font-bold rounded-lg transition-colors mb-3">
                            Checkout
                        </a>
                        <a wire:navigate href="{{ route('products') }}"
                           class="block w-full text-center px-6 py-3 bg-deep hover:bg-deep-dark border border-deep !text-cream font-semibold rounded-lg transition-colors">
                            Lanjut Belanja
                        </a>
                    </div>
                </div>
            </div>
        @endif
    </section>
</div>
