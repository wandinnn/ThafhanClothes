<?php

use Livewire\Component;
use App\Models\Product;
use App\Models\Review;

new class extends Component {
    public Product $product;
    public array   $availableColors = ['Hitam', 'Putih', 'Coklat', 'Merah'];
    public array   $availableSizes  = ['XS', 'S', 'M', 'L', 'XL', 'XXL'];
    public string  $selectedColor   = '';
    public string  $selectedSize    = '';    // ← BARU: ukuran wajib dipilih
    public int     $quantity         = 1;

    // Review form
    public string $reviewName      = '';
    public int    $reviewRating    = 5;
    public string $reviewComment   = '';
    public bool   $reviewSubmitted = false;

    protected function rules(): array
    {
        return [
            'reviewName'    => ['required', 'min:2', 'max:100'],
            'reviewRating'  => ['required', 'integer', 'min:1', 'max:5'],
            'reviewComment' => ['nullable', 'max:1000'],
        ];
    }

    protected array $messages = [
        'reviewName.required' => 'Nama wajib diisi.',
        'reviewName.min'      => 'Nama minimal 2 karakter.',
    ];

    public function mount(Product $product): void
    {
        $this->product        = $product->load(['category', 'reviews']);
        $this->availableSizes = $this->getSizesForCategory($product->category->slug, $product);
    }

    private function getSizesForCategory(string $slug, ?Product $product = null): array
    {
        return match (true) {
            str_contains($slug, 'celana') => ['28', '30', '32', '34', '36'],
            str_contains($slug, 'sepatu') => ['38', '39', '40', '41', '42'],
            str_contains($slug, 'aksesoris') => $this->getAccessoriesSizes($product),
            default                        => ['XS', 'S', 'M', 'L', 'XL', 'XXL'],
        };
    }

    private function getAccessoriesSizes(?Product $product): array
    {
        if (!$product) {
            return ['One Size'];
        }

        $name = strtolower($product->name);

        return match (true) {
            str_contains($name, 'jam') || str_contains($name, 'watch') => ['40mm', '44mm'],
            str_contains($name, 'ransel') || str_contains($name, 'backpack') => ['One Size'],
            str_contains($name, 'kacamata') || str_contains($name, 'glasses') || str_contains($name, 'sunglasses') => ['One Size'],
            default => ['One Size'],
        };
    }

    public function updatedQuantity(): void
    {
        if ($this->quantity < 1) {
            $this->quantity = 1;
        }
    }

    public function decreaseQuantity(): void
    {
        if ($this->quantity > 1) {
            $this->quantity--;
        }
    }

    public function increaseQuantity(): void
    {
        $this->quantity++;
    }

    public function addToCart(): void
    {
        // Validasi ukuran wajib dipilih
        if (empty($this->selectedSize)) {
            session()->flash('validationError', 'Silahkan pilih ukuran terlebih dahulu.');
            return;
        }

        // Validasi warna wajib dipilih
        if (empty($this->selectedColor)) {
            session()->flash('validationError', 'Silahkan pilih warna terlebih dahulu.');
            return;
        }

        $cart                      = session('cart', []);
        $cart[$this->product->id]  = ($cart[$this->product->id] ?? 0) + $this->quantity;
        session(['cart' => $cart]);

        // Simpan pilihan warna & ukuran
        $meta                      = session('cart_meta', []);
        $meta[$this->product->id]  = [
            'color' => $this->selectedColor,
            'size'  => $this->selectedSize,
        ];
        session(['cart_meta' => $meta]);

        $this->dispatch('cart-updated', count: count($cart));
        session()->flash('message', 'Produk berhasil ditambahkan ke keranjang!');
    }

    public function submitReview(): void
    {
        $this->validate();

        Review::create([
            'product_id'    => $this->product->id,
            'reviewer_name' => $this->reviewName,
            'rating'        => $this->reviewRating,
            'comment'       => $this->reviewComment ?: null,
        ]);

        $this->reviewName      = '';
        $this->reviewRating    = 5;
        $this->reviewComment   = '';
        $this->reviewSubmitted = true;

        $this->product = $this->product->fresh(['category', 'reviews']);
    }

    public function setReviewRating(int $rating): void
    {
        $this->reviewRating = $rating;
    }

    public function render()
    {
        return view('pages.⚡product-detail', [
            'relatedProducts' => Product::with('category')
                ->where('category_id', $this->product->category_id)
                ->where('id', '!=', $this->product->id)
                ->take(4)->get(),
        ]);
    }
};
?>

<div class="animate-fade-in">
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        {{-- Breadcrumb --}}
        <nav class="flex items-center gap-2 text-sm text-[#4F252E] mb-6">
            <a href="{{ route('home') }}" class="hover:text-[#1F2A2A] transition-colors">Beranda</a>
            <span>/</span>
            <a href="{{ route('products') }}" class="hover:text-[#1F2A2A] transition-colors">Produk</a>
            <span>/</span>
            <span class="text-gray-700 font-medium">{{ $product->name }}</span>
        </nav>

        <div class="grid lg:grid-cols-2 gap-8 lg:gap-14">

            {{-- ======= KIRI: Gambar Produk ======= --}}
            <div class="aspect-[4/5] rounded-2xl overflow-hidden bg-gray-100 shadow-md">
                <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="w-full h-full object-cover">
            </div>

            {{-- ======= KANAN: Info Produk ======= --}}
            <div class="flex flex-col gap-4">

                {{-- Kategori --}}
                <p class="text-xs text-[#1F2A2A] uppercase tracking-widest font-semibold">
                    {{ $product->category->name }}
                </p>

                {{-- Nama --}}
                <div>
                    <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">{{ $product->name }}</h1>
                    <div class="gold-line mt-3"></div>
                </div>

                {{-- Rating Summary --}}
                @if($product->reviews_count > 0)
                    <div class="flex items-center gap-2">
                        <div class="flex items-center gap-0.5">
                            @for($i = 1; $i <= 5; $i++)
                                <svg class="size-4 {{ $i <= round($product->average_rating) ? 'text-yellow-400 fill-yellow-400' : 'text-[#4F252E] fill-gray-300' }}" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                </svg>
                            @endfor
                        </div>
                        <span class="text-sm font-semibold text-gray-800">{{ $product->average_rating }}</span>
                        <span class="text-sm text-[#4F252E]">({{ $product->reviews_count }} ulasan)</span>
                    </div>
                @endif

                {{-- Flash Messages --}}
                @if(session()->has('message'))
                    <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-4 text-green-600 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                        </svg>
                        {{ session('message') }}
                    </div>
                @endif

                @if(session()->has('validationError'))
                    <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-4 text-red-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z"/>
                        </svg>
                        {{ session('validationError') }}
                    </div>
                @endif

                {{-- Badges --}}
                @if($product->is_best_seller || $product->is_new_arrival || $product->is_flash_sale)
                    <div class="flex flex-wrap gap-2">
                        @if($product->is_best_seller)
                            <span class="bg-amber-100 text-amber-800 text-xs font-semibold px-3 py-1 rounded-full">⭐ Best Seller</span>
                        @endif
                        @if($product->is_new_arrival)
                            <span class="bg-blue-100 text-blue-800 text-xs font-semibold px-3 py-1 rounded-full">🆕 New Arrival</span>
                        @endif
                        @if($product->is_flash_sale)
                            <span class="bg-[#FFF6DE]/20 text-[#F48F68] text-xs font-semibold px-3 py-1 rounded-full">⚡ Flash Sale</span>
                        @endif
                    </div>
                @endif

                {{-- Harga --}}
                <div class="flex items-end gap-3">
                    <p class="text-3xl font-bold text-[#1F2A2A]">{{ $product->formatted_price }}</p>
                    @if($product->formatted_original_price)
                        <p class="text-lg text-[#4F252E] line-through mb-0.5">{{ $product->formatted_original_price }}</p>
                        <span class="bg-red-100 text-red-700 text-xs font-bold px-2 py-1 rounded-full mb-0.5">-{{ $product->discount_percent }}%</span>
                    @endif
                </div>

                {{-- ===== PILIH WARNA ===== --}}
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4">
                    <div class="flex items-center justify-between mb-3">
                        <p class="text-sm font-bold text-gray-800 uppercase tracking-wider">
                            Pilih Warna
                            <span class="text-red-500 ml-0.5">*</span>
                        </p>
                        @if($selectedColor)
                            <span class="text-xs font-semibold bg-[#FFF6DE]/10 text-[#F48F68] px-2.5 py-1 rounded-full border border-[#FFF6DE]/30">
                                {{ $selectedColor }}
                            </span>
                        @else
                            <span class="text-xs text-[#4F252E]">Wajib dipilih</span>
                        @endif
                    </div>
                    <div class="flex flex-wrap gap-2">
                        @foreach($availableColors as $color)
                            <button type="button"
                                    wire:click="$set('selectedColor', '{{ $color }}')"
                                    class="px-4 py-2 rounded-full text-sm font-medium transition-all border-2
                                           {{ $selectedColor === $color
                                              ? 'bg-[#FFF6DE] text-[#1F2A2A] border-[#FFF6DE] shadow-md font-bold'
                                              : 'bg-white text-gray-700 border-gray-200 hover:border-[#FFF6DE] hover:text-[#1F2A2A]' }}">
                                {{ $color }}
                            </button>
                        @endforeach
                    </div>
                </div>

                {{-- ===== PILIH UKURAN (TERPISAH, WAJIB DIPILIH) ===== --}}
                <div class="bg-white rounded-2xl border-2 {{ empty($selectedSize) ? 'border-gray-100' : 'border-[#FFF6DE]' }} shadow-sm p-4 transition-colors">
                    <div class="flex items-center justify-between mb-3">
                        <p class="text-sm font-bold text-gray-800 uppercase tracking-wider">
                            Pilih Ukuran
                            <span class="text-red-500 ml-0.5">*</span>
                        </p>
                        @if($selectedSize)
                            <span class="text-xs font-bold bg-[#FFF6DE] text-[#1F2A2A] px-3 py-1 rounded-full">
                                ✓ {{ $selectedSize }}
                            </span>
                        @else
                            <span class="text-xs text-red-400 font-medium">Belum dipilih</span>
                        @endif
                    </div>
                    <div class="flex flex-wrap gap-2">
                        @foreach($availableSizes as $size)
                            <button type="button"
                                    wire:click="$set('selectedSize', '{{ $size }}')"
                                    class="px-5 py-2.5 rounded-xl text-sm font-semibold transition-all border-2 cursor-pointer
                                           {{ $selectedSize === $size
                                              ? 'bg-[#FFF6DE] text-[#1F2A2A] border-[#FFF6DE] shadow-md scale-105'
                                              : 'bg-white text-gray-700 border-gray-200 hover:border-[#FFF6DE] hover:text-[#1F2A2A] hover:scale-105' }}">
                                {{ $size }}
                            </button>
                        @endforeach
                    </div>
                    @if(empty($selectedSize))
                        <p class="text-xs text-red-400 mt-2.5 flex items-center gap-1">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z"/>
                            </svg>
                            Ukuran harus dipilih sebelum menambah ke keranjang
                        </p>
                    @endif
                </div>

                {{-- ===== JUMLAH / KUANTITAS ===== --}}
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4">
                    <p class="text-sm font-bold text-gray-800 uppercase tracking-wider mb-3">Jumlah</p>
                    <div class="flex items-center gap-3">
                        <button type="button"
                                wire:click="decreaseQuantity"
                                class="w-10 h-10 rounded-full border-2 border-gray-200 text-[#4F252E] hover:border-[#FFF6DE] hover:text-[#1F2A2A] transition-all font-bold text-lg flex items-center justify-center">
                            −
                        </button>
                        <span class="w-10 text-center text-lg font-bold text-gray-900">{{ $quantity }}</span>
                        <button type="button"
                                wire:click="increaseQuantity"
                                class="w-10 h-10 rounded-full border-2 border-gray-200 text-[#4F252E] hover:border-[#FFF6DE] hover:text-[#1F2A2A] transition-all font-bold text-lg flex items-center justify-center">
                            +
                        </button>
                    </div>
                </div>

                {{-- ===== ACTION BUTTONS ===== --}}
                <div class="space-y-3 pt-1">
                    <button wire:click="addToCart"
                            wire:loading.attr="disabled"
                            class="w-full font-bold py-3.5 rounded-xl transition-all duration-200 hover:-translate-y-0.5 shadow-md active:scale-[0.98] flex items-center justify-center gap-2
                                   {{ (empty($selectedSize) || empty($selectedColor))
                                      ? 'bg-gray-300 text-[#4F252E] cursor-not-allowed'
                                      : 'bg-[#FFF6DE] hover:bg-[#F48F68] text-[#1F2A2A] cursor-pointer' }}">
                        <span wire:loading.remove>
                            @if(empty($selectedSize))
                                Pilih Ukuran Dulu
                            @elseif(empty($selectedColor))
                                Pilih Warna Dulu
                            @else
                                + Tambah ke Keranjang
                            @endif
                        </span>
                        <span wire:loading class="inline-flex items-center gap-2">
                            <svg class="animate-spin size-5" viewBox="0 0 24 24" fill="none">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4z"/>
                            </svg>
                            Menambahkan...
                        </span>
                    </button>

                    <a href="https://wa.me/6281324825060?text=Halo%20ThafhanClothes%2C%20saya%20ingin%20tanya%20tentang%20{{ urlencode($product->name) }}"
                       target="_blank" rel="noopener noreferrer"
                       class="flex items-center justify-center gap-2 w-full border-2 border-green-500 text-green-600 font-semibold py-3.5 rounded-xl hover:bg-green-50 transition-all duration-200">
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
                            <path d="M12 0C5.373 0 0 5.373 0 12c0 2.122.555 4.112 1.523 5.837L.057 23.882l6.197-1.624A11.945 11.945 0 0 0 12 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 22c-1.89 0-3.663-.5-5.197-1.373l-.373-.22-3.678.964.98-3.584-.243-.392A9.956 9.956 0 0 1 2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10z"/>
                        </svg>
                        Tanya via WhatsApp
                    </a>
                </div>

            </div>{{-- end right column --}}
        </div>{{-- end grid --}}

        {{-- ===== DESKRIPSI PRODUK (TERPISAH, FULL WIDTH) ===== --}}
        <div class="mt-10 bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-1 h-6 bg-[#FFF6DE] rounded-full"></div>
                <h2 class="text-base font-bold text-gray-900 uppercase tracking-wider">Deskripsi Produk</h2>
            </div>
            <p class="text-sm text-[#4F252E] leading-relaxed">{{ $product->description }}</p>

            {{-- Panduan Ukuran --}}
            <div class="mt-6 pt-5 border-t border-gray-100">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-1 h-5 bg-[#FFF6DE]/50 rounded-full"></div>
                    <h3 class="text-sm font-bold text-gray-800 uppercase tracking-wider">Panduan Ukuran</h3>
                </div>
                <div class="flex flex-wrap gap-2">
                    @foreach($availableSizes as $size)
                        <span class="px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm font-semibold text-gray-700">
                            {{ $size }}
                        </span>
                    @endforeach
                </div>
                <p class="text-xs text-[#4F252E] mt-3">
                    Jika ragu dengan ukuran, hubungi kami via WhatsApp untuk panduan ukuran yang tepat.
                </p>
            </div>
        </div>

        {{-- ===== RATING & ULASAN ===== --}}
        <div class="mt-10">
            <div class="flex items-center justify-between mb-5">
                <div class="flex items-center gap-3">
                    <div class="w-1 h-7 bg-[#FFF6DE] rounded-full"></div>
                    <h2 class="text-xl font-bold text-gray-900">Rating & Ulasan</h2>
                </div>
                @if($product->reviews_count > 0)
                    <div class="flex items-center gap-2 bg-[#8BDFDD] text-[#4F252E] px-4 py-2 rounded-xl">
                        <svg class="size-5 text-yellow-400 fill-yellow-400" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                        </svg>
                        <span class="font-bold">{{ $product->average_rating }}</span>
                        <span class="text-[#4F252E] text-sm">/ 5 ({{ $product->reviews_count }})</span>
                    </div>
                @endif
            </div>

            <div class="grid lg:grid-cols-[1fr_1.4fr] gap-8">
                {{-- Form Ulasan --}}
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                    <h3 class="font-semibold text-gray-900 mb-4">Tulis Ulasan</h3>

                    @if($reviewSubmitted)
                        <div class="flex flex-col items-center justify-center py-8 text-center">
                            <div class="w-14 h-14 bg-green-100 rounded-full flex items-center justify-center mb-3">
                                <svg xmlns="http://www.w3.org/2000/svg" class="size-7 text-green-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                                </svg>
                            </div>
                            <p class="font-semibold text-gray-900">Terima kasih atas ulasanmu!</p>
                            <button wire:click="$set('reviewSubmitted', false)"
                                    class="mt-4 text-sm text-[#1F2A2A] hover:underline">Tulis ulasan lagi</button>
                        </div>
                    @else
                        <form wire:submit="submitReview" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Nama Kamu</label>
                                <input wire:model="reviewName" type="text" placeholder="Masukkan nama..."
                                       class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-[#FFF6DE] focus:outline-none focus:ring-2 focus:ring-[#FFF6DE]/20 text-sm @error('reviewName') border-red-300 bg-red-50 @enderror">
                                @error('reviewName') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Rating</label>
                                <div class="flex items-center gap-1">
                                    @for($i = 1; $i <= 5; $i++)
                                        <button type="button" wire:click="setReviewRating({{ $i }})"
                                                class="transition-transform hover:scale-110">
                                            <svg class="size-7 {{ $i <= $reviewRating ? 'text-yellow-400 fill-yellow-400' : 'text-[#4F252E] fill-gray-300' }} transition-colors" viewBox="0 0 20 20">
                                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                            </svg>
                                        </button>
                                    @endfor
                                    <span class="ml-2 text-sm text-[#4F252E]">{{ ['','Sangat Buruk','Buruk','Cukup','Bagus','Sangat Bagus'][$reviewRating] }}</span>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Komentar <span class="text-[#4F252E]">(opsional)</span></label>
                                <textarea wire:model="reviewComment" rows="3" placeholder="Ceritakan pengalamanmu..."
                                          class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-[#FFF6DE] focus:outline-none focus:ring-2 focus:ring-[#FFF6DE]/20 text-sm resize-none"></textarea>
                            </div>
                            <button type="submit"
                                    class="w-full bg-[#FFF6DE] hover:bg-[#F48F68] text-[#1F2A2A] font-bold py-2.5 rounded-xl transition-all text-sm hover:-translate-y-0.5">
                                Kirim Ulasan
                            </button>
                        </form>
                    @endif
                </div>

                {{-- List Ulasan --}}
                <div class="space-y-4">
                    @forelse($product->reviews->sortByDesc('created_at') as $review)
                        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                            <div class="flex items-start justify-between mb-2">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-full bg-[#8BDFDD] flex items-center justify-center text-[#1F2A2A] font-bold text-sm">
                                        {{ strtoupper(mb_substr($review->reviewer_name, 0, 1)) }}
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-gray-900">{{ $review->reviewer_name }}</p>
                                        <p class="text-xs text-[#4F252E]">{{ $review->created_at->diffForHumans() }}</p>
                                    </div>
                                </div>
                                <div class="flex gap-0.5">
                                    @for($i = 1; $i <= 5; $i++)
                                        <svg class="size-4 {{ $i <= $review->rating ? 'text-yellow-400 fill-yellow-400' : 'text-gray-200 fill-gray-200' }}" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                        </svg>
                                    @endfor
                                </div>
                            </div>
                            @if($review->comment)
                                <p class="text-sm text-[#4F252E] leading-relaxed mt-1">{{ $review->comment }}</p>
                            @else
                                <p class="text-xs text-[#4F252E] italic mt-1">Tidak ada komentar.</p>
                            @endif
                        </div>
                    @empty
                        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-10 text-center">
                            <p class="text-[#4F252E] text-sm">Belum ada ulasan. Jadilah yang pertama!</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Related Products --}}
        @if($relatedProducts->isNotEmpty())
            <div class="mt-14">
                <div class="flex items-center gap-3 mb-5">
                    <div class="w-1 h-6 bg-[#FFF6DE] rounded-full"></div>
                    <h2 class="text-xl font-bold text-gray-900">Produk Terkait</h2>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    @foreach($relatedProducts as $related)
                        <a href="{{ route('product.detail', $related->slug) }}"
                           class="product-card group bg-white rounded-xl overflow-hidden shadow-sm">
                            <div class="card-img-wrap aspect-[4/5]">
                                <img src="{{ $related->image_url }}" alt="{{ $related->name }}" loading="lazy" class="w-full h-full object-cover">
                            </div>
                            <div class="p-3">
                                <h3 class="text-sm font-semibold text-gray-900 group-hover:text-[#1F2A2A] transition-colors line-clamp-1">{{ $related->name }}</h3>
                                <p class="mt-1 font-bold text-[#1F2A2A] text-sm">{{ $related->formatted_price }}</p>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

    </section>
</div>



