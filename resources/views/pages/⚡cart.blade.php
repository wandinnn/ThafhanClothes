<?php

use Livewire\Component;
use App\Models\Product;

new class extends Component {
    public array $products = [];

    public function mount(): void
    {
        $this->loadCart();
    }

    public function loadCart(): void
    {
        $cart = session('cart', []);
        $this->products = [];

        foreach ($cart as $productId => $quantity) {
            $product = Product::find($productId);
            if ($product) {
                $this->products[$productId] = [
                    'product' => $product,
                    'quantity' => $quantity,
                ];
            }
        }
    }

    public function updateQuantity(int $productId, int $quantity): void
    {
        if ($quantity <= 0) {
            $this->removeItem($productId);
            return;
        }

        $cart = session('cart', []);
        $cart[$productId] = $quantity;
        session(['cart' => $cart]);
        $this->loadCart();
        $this->dispatch('cart-updated', count: count($cart));
    }

    public function removeItem(int $productId): void
    {
        $cart = session('cart', []);
        unset($cart[$productId]);
        session(['cart' => $cart]);
        $this->loadCart();
        $this->dispatch('cart-updated', count: count($cart));
    }

    public function getSubtotalProperty(): float
    {
        $total = 0;
        foreach ($this->products as $item) {
            $total += $item['product']->price * $item['quantity'];
        }
        return $total;
    }

    public function getTotalProperty(): float
    {
        return $this->subtotal + 0;
    }

    public function render()
    {
        return view('pages.⚡cart');
    }
};
?>

<div class="min-h-screen bg-gray-50">
    {{-- Page Header --}}
    <section class="bg-[#8BDFDD] py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h1 class="text-3xl font-bold text-[#4F252E]">Keranjang Belanja</h1>
            <div class="gold-line mt-2 mb-0"></div>
        </div>
    </section>

    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        @if (empty($products))
            {{-- Empty Cart --}}
            <div class="bg-white rounded-lg shadow-sm p-12 text-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="size-16 mx-auto mb-4 text-[#4F252E]"
                     fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z"/>
                </svg>
                <h2 class="text-xl font-semibold text-gray-700 mb-2">Keranjang Anda Kosong</h2>
                <p class="text-[#4F252E] mb-6">Mulai berbelanja dan tambahkan produk favorit ke keranjang Anda</p>
                <a href="{{ route('products') }}"
                   class="inline-block px-6 py-2.5 bg-[#FFF6DE] hover:bg-[#F48F68] text-[#1F2A2A] font-semibold rounded-lg transition-colors">
                    Lanjut Belanja
                </a>
            </div>
        @else
            {{-- Cart Items --}}
            <div class="grid lg:grid-cols-3 gap-8">
                {{-- Items List --}}
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                        <div class="p-6 border-b border-gray-200">
                            <h2 class="text-lg font-semibold text-gray-900">
                                Daftar Produk ({{ count($products) }})
                            </h2>
                        </div>

                        <div class="divide-y divide-gray-200">
                            @foreach ($products as $productId => $item)
                                @php
                                    $product = $item['product'];
                                    $quantity = $item['quantity'];
                                    $subtotal = $product->price * $quantity;
                                @endphp
                                <div class="p-6 flex gap-4 hover:bg-gray-50 transition-colors">
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
                                                <a href="{{ route('product.detail', $product->slug) }}"
                                                   class="text-base font-semibold text-gray-900 hover:text-[#1F2A2A] transition-colors">
                                                    {{ $product->name }}
                                                </a>
                                                <p class="text-sm text-[#4F252E] mt-0.5">
                                                    {{ $product->category->name }}
                                                </p>
                                            </div>
                                            <button wire:click="removeItem({{ $productId }})"
                                                    class="text-[#4F252E] hover:text-red-500 transition-colors">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="size-5"
                                                     fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
                                                </svg>
                                            </button>
                                        </div>

                                        <div class="flex items-end justify-between">
                                            {{-- Price --}}
                                            <div class="flex flex-col">
                                                <span class="text-sm text-[#4F252E] line-through"
                                                      @if (!$product->original_price) style="display:none" @endif>
                                                    {{ $product->formatted_original_price }}
                                                </span>
                                                <span class="text-lg font-bold text-[#1F2A2A]">
                                                    {{ $product->formatted_price }}
                                                </span>
                                            </div>

                                            {{-- Quantity Control --}}
                                            <div class="flex items-center gap-3 bg-gray-100 rounded-lg p-1">
                                                <button wire:click="updateQuantity({{ $productId }}, {{ $quantity - 1 }})"
                                                        class="w-8 h-8 flex items-center justify-center text-[#4F252E] hover:text-[#1F2A2A] transition-colors">
                                                    −
                                                </button>
                                                <span class="w-6 text-center font-semibold text-gray-900">
                                                    {{ $quantity }}
                                                </span>
                                                <button wire:click="updateQuantity({{ $productId }}, {{ $quantity + 1 }})"
                                                        class="w-8 h-8 flex items-center justify-center text-[#4F252E] hover:text-[#1F2A2A] transition-colors">
                                                    +
                                                </button>
                                            </div>
                                        </div>

                                        <div class="text-right text-sm text-[#4F252E] mt-2">
                                            Subtotal: <span class="font-semibold text-gray-900">
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
                    <div class="bg-white rounded-lg shadow-sm overflow-hidden sticky top-20">
                        <div class="p-6 border-b border-gray-200 bg-gray-50">
                            <h3 class="text-lg font-semibold text-gray-900">Ringkasan Order</h3>
                        </div>

                        <div class="p-6 space-y-4">
                            {{-- Subtotal --}}
                            <div class="flex justify-between items-center">
                                <span class="text-[#4F252E]">Subtotal</span>
                                <span class="font-semibold text-gray-900">
                                    Rp{{ number_format($this->subtotal, 0, ',', '.') }}
                                </span>
                            </div>

                            {{-- Shipping --}}
                            <div class="flex justify-between items-center pb-4 border-b border-gray-200">
                                <span class="text-[#4F252E]">Estimasi Ongkir</span>
                                <span class="text-sm text-[#4F252E]">Dihitung nanti</span>
                            </div>

                            {{-- Total --}}
                            <div class="flex justify-between items-center pt-2">
                                <span class="font-semibold text-gray-900 text-lg">Total</span>
                                <span class="font-bold text-[#1F2A2A] text-xl">
                                    Rp{{ number_format($this->total, 0, ',', '.') }}
                                </span>
                            </div>

                            {{-- Buttons --}}
                            <div class="space-y-3 pt-4">
                                <a href="{{ route('products') }}"
                                   class="block w-full px-4 py-2.5 border-2 border-[#FFF6DE] text-[#1F2A2A] font-semibold rounded-lg hover:bg-[#FFF6DE]/10 transition-colors text-center">
                                    Lanjut Belanja
                                </a>
                                <a href="{{ route('checkout') }}"
                                   class="block w-full px-4 py-2.5 bg-[#FFF6DE] hover:bg-[#F48F68] text-[#1F2A2A] font-semibold rounded-lg transition-colors text-center">
                                    Checkout Sekarang
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </section>
</div>



