<?php

use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Models\Wishlist;
use App\Support\Cart;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

new class extends Component {
    public Product $product;

    public array $availableColors = [];

    public array $availableSizes = [];

    public string $selectedColor = '';

    public string $selectedSize = '';

    /** @var array<string, int> peta stok varian, format "{size}|{color}" */
    public array $variantStock = [];

    public bool $usesVariants = false;

    public int $quantity = 1;

    public bool $inWishlist = false;

    // Review form — hanya untuk pembeli dengan pesanan delivered
    public string $reviewOrderNumber = '';

    public string $reviewPhoneTail = '';

    public int $reviewRating = 5;

    public string $reviewComment = '';

    public bool $reviewSubmitted = false;

    protected function rules(): array
    {
        return [
            'reviewOrderNumber' => ['required', 'string', 'min:6', 'max:32'],
            'reviewPhoneTail' => ['required', 'digits:4'],
            'reviewRating' => ['required', 'integer', 'min:1', 'max:5'],
            'reviewComment' => ['nullable', 'max:1000'],
        ];
    }

    protected array $messages = [
        'reviewOrderNumber.required' => 'Nomor pesanan wajib diisi.',
        'reviewOrderNumber.min' => 'Nomor pesanan tidak valid.',
        'reviewPhoneTail.required' => '4 digit terakhir nomor telepon wajib diisi.',
        'reviewPhoneTail.digits' => 'Masukkan tepat 4 digit terakhir nomor telepon.',
        'reviewRating.required' => 'Rating wajib dipilih.',
        'reviewRating.min' => 'Rating minimal 1.',
        'reviewRating.max' => 'Rating maksimal 5.',
        'reviewComment.max' => 'Komentar maksimal 1000 karakter.',
    ];

    public function mount(Product $product): void
    {
        $this->product = $product->load(['category', 'reviews', 'variants', 'images']);
        $this->usesVariants = $this->product->hasVariants();
        $this->availableSizes = $this->product->availableSizes();
        $this->availableColors = $this->product->availableColors();
        $this->variantStock = $this->product->variantStockMap();
        $this->inWishlist = Wishlist::where('session_id', session()->getId())
            ->where('product_id', $product->id)
            ->exists();
    }

    /**
     * Stok yang bisa dipesan untuk pilihan saat ini. Selama ukuran atau warna
     * belum dipilih, batas atas memakai total stok produk.
     */
    public function getMaxQuantityProperty(): int
    {
        if (! $this->usesVariants || $this->selectedSize === '' || $this->selectedColor === '') {
            return max(0, (int) $this->product->stock);
        }

        return $this->stockFor($this->selectedSize, $this->selectedColor);
    }

    public function stockFor(string $size, string $color): int
    {
        return (int) ($this->variantStock[$size.'|'.$color] ?? 0);
    }

    /**
     * Sisa stok satu ukuran; ikut warna yang sedang dipilih bila ada.
     */
    public function stockForSize(string $size): int
    {
        if (! $this->usesVariants) {
            return (int) $this->product->stock;
        }

        if ($this->selectedColor !== '') {
            return $this->stockFor($size, $this->selectedColor);
        }

        return $this->sumStock(fn (string $key): bool => str_starts_with($key, $size.'|'));
    }

    /**
     * Sisa stok satu warna; ikut ukuran yang sedang dipilih bila ada.
     */
    public function stockForColor(string $color): int
    {
        if (! $this->usesVariants) {
            return (int) $this->product->stock;
        }

        if ($this->selectedSize !== '') {
            return $this->stockFor($this->selectedSize, $color);
        }

        return $this->sumStock(fn (string $key): bool => str_ends_with($key, '|'.$color));
    }

    /**
     * @param  callable(string): bool  $matches
     */
    private function sumStock(callable $matches): int
    {
        $total = 0;

        foreach ($this->variantStock as $key => $stock) {
            if ($matches((string) $key)) {
                $total += (int) $stock;
            }
        }

        return $total;
    }

    public function updatedSelectedSize(): void
    {
        $this->clampQuantity();
    }

    public function updatedSelectedColor(): void
    {
        $this->clampQuantity();
    }

    public function updatedQuantity(): void
    {
        $this->clampQuantity();
    }

    private function clampQuantity(): void
    {
        $this->quantity = max(1, min($this->quantity, max($this->maxQuantity, 1)));
    }

    public function decreaseQuantity(): void
    {
        if ($this->quantity > 1) {
            $this->quantity--;
        }
    }

    public function increaseQuantity(): void
    {
        if ($this->quantity < $this->maxQuantity) {
            $this->quantity++;
        }
    }

    public function toggleWishlist(): void
    {
        $this->inWishlist = Wishlist::toggleForSession($this->product->id);
        $this->dispatch('wishlist-updated', count: Wishlist::countForSession());
        session()->flash(
            'message',
            $this->inWishlist ? 'Ditambahkan ke wishlist.' : 'Dihapus dari wishlist.'
        );
    }

    public function addToCart(): void
    {
        if ($this->selectedSize === '') {
            session()->flash('validationError', 'Silahkan pilih ukuran terlebih dahulu.');

            return;
        }

        if ($this->selectedColor === '') {
            session()->flash('validationError', 'Silahkan pilih warna terlebih dahulu.');

            return;
        }

        if (! in_array($this->selectedSize, $this->availableSizes, true)
            || ! in_array($this->selectedColor, $this->availableColors, true)) {
            session()->flash('validationError', 'Pilihan ukuran atau warna tidak valid.');

            return;
        }

        $this->product = $this->product->fresh(['category', 'reviews', 'variants']);
        $this->variantStock = $this->product->variantStockMap();
        $result = Cart::add($this->product, $this->quantity, $this->selectedSize, $this->selectedColor);

        if (! $result['success']) {
            session()->flash('validationError', $result['message']);

            return;
        }

        $this->dispatch('cart-updated', count: $result['count']);
        session()->flash('message', $result['message']);
    }

    public function submitReview(): void
    {
        $this->validate();

        $orderNumber = strtoupper(trim($this->reviewOrderNumber));
        $phoneTail = $this->reviewPhoneTail;

        $order = Order::query()
            ->whereRaw('UPPER(order_number) = ?', [$orderNumber])
            ->where('status', 'delivered')
            ->whereHas('items', fn ($query) => $query->where('product_id', $this->product->id))
            ->first();

        if (! $order || ! str_ends_with(preg_replace('/\D+/', '', (string) $order->customer_phone) ?? '', $phoneTail)) {
            throw ValidationException::withMessages([
                'reviewOrderNumber' => 'Pesanan tidak ditemukan, belum diterima, atau nomor telepon tidak cocok.',
            ]);
        }

        if (Review::where('product_id', $this->product->id)->where('order_id', $order->id)->exists()) {
            throw ValidationException::withMessages([
                'reviewOrderNumber' => 'Pesanan ini sudah pernah memberi ulasan untuk produk ini.',
            ]);
        }

        $throttleKey = 'review:'.$this->product->id.'|'.request()->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 3)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            $this->addError('reviewOrderNumber', "Terlalu banyak ulasan dikirim. Coba lagi dalam {$seconds} detik.");

            return;
        }

        RateLimiter::hit($throttleKey, 600);

        Review::create([
            'product_id' => $this->product->id,
            'order_id' => $order->id,
            'reviewer_name' => $order->customer_name,
            'rating' => $this->reviewRating,
            'comment' => $this->reviewComment ?: null,
        ]);

        $this->reviewOrderNumber = '';
        $this->reviewPhoneTail = '';
        $this->reviewRating = 5;
        $this->reviewComment = '';
        $this->reviewSubmitted = true;

        $this->product = $this->product->fresh(['category', 'reviews', 'variants', 'images']);
    }

    public function setReviewRating(int $rating): void
    {
        $this->reviewRating = $rating;
    }

    public function render()
    {
        $seoDescription = $this->product->seoDescription();
        $seoImage = $this->product->image_url;
        if (filled($seoImage) && ! str_starts_with($seoImage, 'http://') && ! str_starts_with($seoImage, 'https://')) {
            $seoImage = url($seoImage);
        }

        return view('pages.product-detail', [
            'relatedProducts' => Product::with('category')
                ->where('category_id', $this->product->category_id)
                ->where('id', '!=', $this->product->id)
                ->take(4)->get(),
            'galleryUrls' => $this->product->galleryUrls(),
        ])
            ->title($this->product->name)
            ->layoutData([
                'seoDescription' => $seoDescription,
                'seoImage' => $seoImage,
                'seoUrl' => url()->current(),
            ]);
    }
};
?>

<div class="animate-fade-in">
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        {{-- Breadcrumb --}}
        <nav class="flex items-center gap-2 text-sm text-ink mb-6">
            <a wire:navigate href="{{ route('home') }}" class="hover:text-deep transition-colors">Beranda</a>
            <span>/</span>
            <a wire:navigate href="{{ route('products') }}" class="hover:text-deep transition-colors">Produk</a>
            <span>/</span>
            <span class="text-gray-700 font-medium">{{ $product->name }}</span>
        </nav>

        <div class="grid lg:grid-cols-2 gap-8 lg:gap-14">

            {{-- ======= KIRI: Gallery Produk ======= --}}
            @php($gallery = $galleryUrls !== [] ? $galleryUrls : [$product->image_url])
            <div class="space-y-3" x-data="{ active: @js($gallery[0]) }">
                <div class="aspect-[4/5] rounded-2xl overflow-hidden bg-gray-100 shadow-md relative">
                    <img :src="active" alt="{{ $product->name }}" class="w-full h-full object-cover transition-opacity duration-200">
                    <x-product-condition-badge :condition="$product->condition" class="absolute top-3 left-3 z-10" />
                </div>

                @if(count($gallery) > 1)
                    <div class="grid grid-cols-5 gap-2">
                        @foreach($gallery as $url)
                            <button type="button"
                                    @click="active = @js($url)"
                                    :class="active === @js($url) ? 'ring-2 ring-deep border-deep' : 'border-gray-200 hover:border-beige'"
                                    class="aspect-square rounded-xl overflow-hidden border-2 bg-gray-100 transition-all focus:outline-none">
                                <img src="{{ $url }}" alt="" class="w-full h-full object-cover" loading="lazy">
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- ======= KANAN: Info Produk ======= --}}
            <div class="flex flex-col gap-4">

                {{-- Kategori --}}
                <p class="text-xs text-deep uppercase tracking-widest font-semibold">
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
                                <svg class="size-4 {{ $i <= round($product->average_rating) ? 'text-yellow-400 fill-yellow-400' : 'text-ink fill-gray-300' }}" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                </svg>
                            @endfor
                        </div>
                        <span class="text-sm font-semibold text-gray-800">{{ $product->average_rating }}</span>
                        <span class="text-sm text-ink">({{ $product->reviews_count }} ulasan)</span>
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
                <div class="flex flex-wrap gap-2">
                    <x-product-condition-badge :condition="$product->condition" class="!text-xs px-3 py-1 rounded-full" />
                    @if($product->is_best_seller)
                        <span class="bg-amber-100 text-amber-800 text-xs font-semibold px-3 py-1 rounded-full">⭐ Best Seller</span>
                    @endif
                    @if($product->is_new_arrival)
                        <span class="bg-blue-100 text-blue-800 text-xs font-semibold px-3 py-1 rounded-full">🆕 New Arrival</span>
                    @endif
                    @if($product->is_flash_sale)
                        <span class="bg-beige/20 text-coral text-xs font-semibold px-3 py-1 rounded-full">⚡ Flash Sale</span>
                    @endif
                </div>

                {{-- Harga --}}
                <div class="flex items-end gap-3">
                    <p class="text-3xl font-bold text-deep">{{ $product->formatted_price }}</p>
                    @if($product->formatted_original_price)
                        <p class="text-lg text-ink line-through mb-0.5">{{ $product->formatted_original_price }}</p>
                        <span class="bg-red-100 text-red-700 text-xs font-bold px-2 py-1 rounded-full mb-0.5">-{{ $product->discount_percent }}%</span>
                    @endif
                </div>

                {{-- ===== PILIH WARNA ===== --}}
                <div class="bg-panel rounded-2xl border border-gray-100 shadow-sm p-4">
                    <div class="flex items-center justify-between mb-3">
                        <p class="text-sm font-bold text-gray-800 uppercase tracking-wider">
                            Pilih Warna
                            <span class="text-red-500 ml-0.5">*</span>
                        </p>
                        @if($selectedColor)
                            <span class="text-xs font-bold bg-deep text-beige px-3 py-1 rounded-full">
                                ✓ {{ $selectedColor }}
                            </span>
                        @else
                            <span class="text-xs text-ink">Wajib dipilih</span>
                        @endif
                    </div>
                    <div class="flex flex-wrap gap-2">
                        @foreach($availableColors as $color)
                            @php($colorStock = $this->stockForColor($color))
                            <button type="button"
                                    wire:click="$set('selectedColor', '{{ $color }}')"
                                    @disabled($usesVariants && $colorStock <= 0)
                                    class="px-4 py-2 rounded-full text-sm font-medium transition-all border-2
                                           {{ $usesVariants && $colorStock <= 0
                                              ? 'bg-gray-50 text-gray-400 border-gray-100 line-through cursor-not-allowed'
                                              : ($selectedColor === $color
                                                 ? 'bg-deep !text-beige border-deep shadow-md font-bold'
                                                 : 'bg-panel text-gray-700 border-gray-200 hover:border-beige hover:text-deep') }}">
                                {{ $color }}
                            </button>
                        @endforeach
                    </div>
                </div>

                {{-- ===== PILIH UKURAN (TERPISAH, WAJIB DIPILIH) ===== --}}
                <div class="bg-panel rounded-2xl border-2 {{ empty($selectedSize) ? 'border-gray-100' : 'border-beige' }} shadow-sm p-4 transition-colors">
                    <div class="flex items-center justify-between mb-3">
                        <p class="text-sm font-bold text-gray-800 uppercase tracking-wider">
                            Pilih Ukuran
                            <span class="text-red-500 ml-0.5">*</span>
                        </p>
                        @if($selectedSize)
                            <span class="text-xs font-bold bg-deep text-beige px-3 py-1 rounded-full">
                                ✓ {{ $selectedSize }}
                            </span>
                        @else
                            <span class="text-xs text-red-400 font-medium">Belum dipilih</span>
                        @endif
                    </div>
                    <div class="flex flex-wrap gap-2">
                        @foreach($availableSizes as $size)
                            @php($sizeStock = $this->stockForSize($size))
                            <button type="button"
                                    wire:click="$set('selectedSize', '{{ $size }}')"
                                    @disabled($usesVariants && $sizeStock <= 0)
                                    class="px-5 py-2.5 rounded-xl text-sm font-semibold transition-all border-2
                                           {{ $usesVariants && $sizeStock <= 0
                                              ? 'bg-gray-50 text-gray-400 border-gray-100 line-through cursor-not-allowed'
                                              : ($selectedSize === $size
                                                 ? 'bg-deep !text-beige border-deep shadow-md scale-105 cursor-pointer'
                                                 : 'bg-panel text-gray-700 border-gray-200 hover:border-beige hover:text-deep hover:scale-105 cursor-pointer') }}">
                                {{ $size }}
                            </button>
                        @endforeach
                    </div>
                    @if($usesVariants)
                        <p class="text-xs text-ink mt-2.5">Ukuran yang dicoret berarti stok warna terpilih sedang kosong.</p>
                    @endif
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
                <div class="bg-panel rounded-2xl border border-gray-100 shadow-sm p-4">
                    <div class="flex items-center justify-between mb-3">
                        <p class="text-sm font-bold text-gray-800 uppercase tracking-wider">Jumlah</p>
                        @php($shownStock = $this->maxQuantity)
                        <span class="text-xs font-semibold {{ $shownStock <= 0 ? 'text-red-600' : ($shownStock <= 5 ? 'text-amber-600' : 'text-ink') }}">
                            @if($usesVariants && $selectedSize !== '' && $selectedColor !== '')
                                Stok {{ $selectedSize }} / {{ $selectedColor }}:
                                {{ $shownStock > 0 ? $shownStock.($shownStock <= 5 ? ' (terbatas!)' : '') : 'habis' }}
                            @else
                                {{ $product->formatted_stock }}
                            @endif
                        </span>
                    </div>
                    <div class="flex items-center gap-3">
                        <button type="button"
                                wire:click="decreaseQuantity"
                                class="w-10 h-10 rounded-full border-2 border-gray-200 text-ink hover:border-beige hover:text-deep transition-all font-bold text-lg flex items-center justify-center">
                            −
                        </button>
                        <span class="w-10 text-center text-lg font-bold text-gray-900">{{ $quantity }}</span>
                        <button type="button"
                                wire:click="increaseQuantity"
                                class="w-10 h-10 rounded-full border-2 border-gray-200 text-ink hover:border-beige hover:text-deep transition-all font-bold text-lg flex items-center justify-center">
                            +
                        </button>
                    </div>
                </div>

                {{-- ===== ACTION BUTTONS ===== --}}
                <div class="space-y-3 pt-1">
                    <button wire:click="addToCart"
                            wire:loading.attr="disabled"
                            @disabled($product->is_out_of_stock)
                            class="w-full font-bold py-3.5 rounded-xl transition-all duration-200 hover:-translate-y-0.5 shadow-md active:scale-[0.98] flex items-center justify-center gap-2
                                   {{ ($product->is_out_of_stock || empty($selectedSize) || empty($selectedColor))
                                      ? 'bg-gray-300 text-ink cursor-not-allowed'
                                      : 'bg-cream hover:bg-coral text-on-cream hover:text-on-coral cursor-pointer' }}">
                        <span wire:loading.remove wire:target="addToCart">
                            @if($product->is_out_of_stock)
                                Stok Habis
                            @elseif(empty($selectedSize))
                                Pilih Ukuran Dulu
                            @elseif(empty($selectedColor))
                                Pilih Warna Dulu
                            @else
                                + Tambah ke Keranjang
                            @endif
                        </span>
                        <span wire:loading wire:target="addToCart" class="inline-flex items-center gap-2">
                            <svg class="animate-spin size-5" viewBox="0 0 24 24" fill="none">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4z"/>
                            </svg>
                            Menambahkan...
                        </span>
                    </button>

                    <button type="button" wire:click="toggleWishlist"
                            class="w-full font-semibold py-3.5 rounded-xl border-2 transition-all duration-200 flex items-center justify-center gap-2
                                   {{ $inWishlist
                                      ? 'border-teal-dark bg-teal !text-on-teal'
                                      : 'border-teal bg-teal !text-on-teal hover:bg-teal-dark hover:border-teal-dark' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-5 !text-on-teal" fill="{{ $inWishlist ? 'currentColor' : 'none' }}" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z"/>
                        </svg>
                        {{ $inWishlist ? 'Tersimpan di Wishlist' : 'Simpan ke Wishlist' }}
                    </button>

                    @php($waReady = $selectedSize !== '' && $selectedColor !== '')
                    <a href="https://wa.me/6281324825060?text={{ urlencode('Halo ThafhanClothes, saya ingin tanya tentang '.$product->name.($waReady ? ' (ukuran '.$selectedSize.', warna '.$selectedColor.')' : '')) }}"
                       target="_blank" rel="noopener noreferrer"
                       class="flex items-center justify-center gap-2 w-full border-2 border-green-500 bg-green-500 hover:bg-green-600 hover:border-green-600 font-semibold py-3.5 rounded-xl transition-all duration-200 !text-white">
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-5 !text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
                            <path d="M12 0C5.373 0 0 5.373 0 12c0 2.122.555 4.112 1.523 5.837L.057 23.882l6.197-1.624A11.945 11.945 0 0 0 12 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 22c-1.89 0-3.663-.5-5.197-1.373l-.373-.22-3.678.964.98-3.584-.243-.392A9.956 9.956 0 0 1 2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10z"/>
                        </svg>
                        Tanya via WhatsApp
                    </a>
                </div>

            </div>{{-- end right column --}}
        </div>{{-- end grid --}}

        {{-- ===== DESKRIPSI PRODUK (TERPISAH, FULL WIDTH) ===== --}}
        <div class="mt-10 bg-panel rounded-2xl border border-gray-100 shadow-sm p-6">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-1 h-6 bg-beige rounded-full"></div>
                <h2 class="text-base font-bold text-gray-900 uppercase tracking-wider">Deskripsi Produk</h2>
            </div>
            <p class="text-sm text-ink leading-relaxed">{{ $product->description }}</p>

            {{-- Panduan Ukuran --}}
            <div class="mt-6 pt-5 border-t border-gray-100">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-1 h-5 bg-beige/50 rounded-full"></div>
                    <h3 class="text-sm font-bold text-gray-800 uppercase tracking-wider">Panduan Ukuran</h3>
                </div>
                <div class="flex flex-wrap gap-2">
                    @foreach($availableSizes as $size)
                        <span class="px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm font-semibold text-gray-700">
                            {{ $size }}
                        </span>
                    @endforeach
                </div>
                <p class="text-xs text-ink mt-3">
                    Jika ragu dengan ukuran, hubungi kami via WhatsApp untuk panduan ukuran yang tepat.
                </p>
            </div>
        </div>

        {{-- ===== RATING & ULASAN ===== --}}
        <div class="mt-10">
            <div class="flex items-center justify-between mb-5">
                <div class="flex items-center gap-3">
                    <div class="w-1 h-7 bg-beige rounded-full"></div>
                    <h2 class="text-xl font-bold text-gray-900">Rating & Ulasan</h2>
                </div>
                @if($product->reviews_count > 0)
                    <div class="flex items-center gap-2 bg-deep text-coral px-4 py-2 rounded-xl">
                        <svg class="size-5 text-yellow-400 fill-yellow-400" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                        </svg>
                        <span class="font-bold">{{ $product->average_rating }}</span>
                        <span class="text-ink text-sm">/ 5 ({{ $product->reviews_count }})</span>
                    </div>
                @endif
            </div>

            <div class="grid lg:grid-cols-[1fr_1.4fr] gap-8">
                {{-- Form Ulasan --}}
                <div class="bg-panel rounded-2xl border border-gray-100 shadow-sm p-6">
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
                                    class="mt-4 text-sm text-deep hover:underline">Tulis ulasan lagi</button>
                        </div>
                    @else
                        <form wire:submit="submitReview" class="space-y-4">
                            <p class="text-xs text-ink">
                                Ulasan hanya untuk pembeli dengan status pesanan <strong>diterima</strong>.
                                Isi nomor pesanan dan 4 digit terakhir nomor telepon checkout.
                            </p>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Nomor Pesanan</label>
                                <input wire:model="reviewOrderNumber" type="text" placeholder="Contoh: ABC1234567"
                                       class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-beige focus:outline-none focus:ring-2 focus:ring-beige/20 text-sm @error('reviewOrderNumber') border-red-300 bg-red-50 @enderror">
                                @error('reviewOrderNumber') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">4 Digit Terakhir No. Telepon</label>
                                <input wire:model="reviewPhoneTail" type="text" inputmode="numeric" maxlength="4" placeholder="Contoh: 5060"
                                       class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-beige focus:outline-none focus:ring-2 focus:ring-beige/20 text-sm @error('reviewPhoneTail') border-red-300 bg-red-50 @enderror">
                                @error('reviewPhoneTail') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Rating</label>
                                <div class="flex items-center gap-1">
                                    @for($i = 1; $i <= 5; $i++)
                                        <button type="button" wire:click="setReviewRating({{ $i }})"
                                                class="transition-transform hover:scale-110">
                                            <svg class="size-7 {{ $i <= $reviewRating ? 'text-yellow-400 fill-yellow-400' : 'text-ink fill-gray-300' }} transition-colors" viewBox="0 0 20 20">
                                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                            </svg>
                                        </button>
                                    @endfor
                                    <span class="ml-2 text-sm text-ink">{{ ['','Sangat Buruk','Buruk','Cukup','Bagus','Sangat Bagus'][$reviewRating] }}</span>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Komentar <span class="text-ink">(opsional)</span></label>
                                <textarea wire:model="reviewComment" rows="3" placeholder="Ceritakan pengalamanmu..."
                                          class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-beige focus:outline-none focus:ring-2 focus:ring-beige/20 text-sm resize-none"></textarea>
                            </div>
                            <button type="submit"
                                    class="w-full bg-cream hover:bg-coral text-on-cream hover:text-on-coral font-bold py-2.5 rounded-xl transition-all text-sm hover:-translate-y-0.5">
                                Kirim Ulasan
                            </button>
                        </form>
                    @endif
                </div>

                {{-- List Ulasan --}}
                <div class="space-y-4">
                    @forelse($product->reviews->sortByDesc('created_at') as $review)
                        <div class="bg-panel rounded-2xl border border-gray-100 shadow-sm p-5">
                            <div class="flex items-start justify-between mb-2">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-full bg-teal flex items-center justify-center text-deep font-bold text-sm">
                                        {{ strtoupper(mb_substr($review->reviewer_name, 0, 1)) }}
                                    </div>
                                    <div>
                                        <div class="flex flex-wrap items-center gap-2">
                                            <p class="text-sm font-semibold text-gray-900">{{ $review->reviewer_name }}</p>
                                            @if($review->is_verified)
                                                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 border border-emerald-200 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-emerald-700">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="size-3" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                                                    </svg>
                                                    Pembeli terverifikasi
                                                </span>
                                            @endif
                                        </div>
                                        <p class="text-xs text-ink">{{ $review->created_at->diffForHumans() }}</p>
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
                                <p class="text-sm text-ink leading-relaxed mt-1">{{ $review->comment }}</p>
                            @else
                                <p class="text-xs text-ink italic mt-1">Tidak ada komentar.</p>
                            @endif
                        </div>
                    @empty
                        <div class="bg-panel rounded-2xl border border-gray-100 shadow-sm p-10 text-center">
                            <p class="text-ink text-sm">Belum ada ulasan. Jadilah yang pertama!</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Related Products --}}
        @if($relatedProducts->isNotEmpty())
            <div class="mt-14">
                <div class="flex items-center gap-3 mb-5">
                    <div class="w-1 h-6 bg-beige rounded-full"></div>
                    <h2 class="text-xl font-bold text-gray-900">Produk Terkait</h2>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    @foreach($relatedProducts as $related)
                        <a wire:navigate href="{{ route('product.detail', $related->slug) }}"
                           class="product-card group bg-panel rounded-xl overflow-hidden shadow-sm">
                            <div class="card-img-wrap aspect-[4/5] relative">
                                <img src="{{ $related->image_url }}" alt="{{ $related->name }}" loading="lazy" class="w-full h-full object-cover">
                                <x-product-condition-badge :condition="$related->condition" class="absolute top-2 left-2" />
                            </div>
                            <div class="p-3">
                                <h3 class="text-sm font-semibold text-gray-900 group-hover:text-deep transition-colors line-clamp-1">{{ $related->name }}</h3>
                                <p class="mt-1 font-bold text-deep text-sm">{{ $related->formatted_price }}</p>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

    </section>
</div>



