<?php

use App\Contracts\ShippingRateProvider;
use App\Data\ShippingQuote;
use App\Exceptions\InsufficientStockException;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\OrderNotifier;
use App\Support\Cart;
use App\Support\OrderAccess;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;

new class extends Component {
    public string $name = '';
    public string $email = '';
    public string $phone = '';
    public string $city = 'Bandung';
    public string $address = '';
    public string $notes = '';
    public string $couponCode = '';
    public string $appliedCouponCode = '';
    public string $shippingServiceCode = '';

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function rules(): array
    {
        $cities = array_keys($this->shipping()->cities());

        return [
            'name' => ['required', 'min:3', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'min:9', 'max:20'],
            'city' => ['required', Rule::in($cities)],
            'shippingServiceCode' => ['required', 'string'],
            'address' => ['required', 'min:10', 'max:500'],
            'notes' => ['nullable', 'max:1000'],
        ];
    }

    protected array $messages = [
        'name.required' => 'Nama lengkap wajib diisi.',
        'name.min' => 'Nama minimal 3 karakter.',
        'email.required' => 'Email wajib diisi agar kami bisa mengirim konfirmasi pesanan.',
        'email.email' => 'Format email tidak valid.',
        'phone.required' => 'Nomor telepon wajib diisi.',
        'phone.min' => 'Nomor telepon tidak valid.',
        'city.in' => 'Kota tujuan tidak tersedia.',
        'shippingServiceCode.required' => 'Pilih layanan pengiriman.',
        'address.required' => 'Alamat lengkap wajib diisi.',
        'address.min' => 'Alamat minimal 10 karakter.',
    ];

    public function mount(): void
    {
        $this->ensureShippingServiceSelected();
    }

    public function updatedCity(): void
    {
        $this->shippingServiceCode = '';
        $this->ensureShippingServiceSelected();
    }

    private function shipping(): ShippingRateProvider
    {
        return app(ShippingRateProvider::class);
    }

    private function ensureShippingServiceSelected(): void
    {
        $quotes = $this->shippingQuotes;

        if ($quotes === []) {
            $this->shippingServiceCode = '';

            return;
        }

        $codes = array_map(fn (ShippingQuote $quote): string => $quote->code, $quotes);

        if (! in_array($this->shippingServiceCode, $codes, true)) {
            $this->shippingServiceCode = $quotes[0]->code;
        }
    }

    /**
     * @return array<string, int>
     */
    public function getCityRatesProperty(): array
    {
        return $this->shipping()->cities();
    }

    /**
     * @return list<ShippingQuote>
     */
    public function getShippingQuotesProperty(): array
    {
        return $this->shipping()->quotesFor(
            $this->city,
            (int) config('shop.shipping.default_weight_grams', 500),
        );
    }

    public function getSelectedShippingQuoteProperty(): ?ShippingQuote
    {
        return $this->shipping()->quoteByCode(
            $this->city,
            $this->shippingServiceCode,
            (int) config('shop.shipping.default_weight_grams', 500),
        );
    }

    /**
     * Isi keranjang selalu dibaca ulang dari session dan database, sehingga
     * kuantitas maupun harga tidak bisa dipalsukan dari sisi browser.
     *
     * @return array<int, array{key: string, product: Product, quantity: int, size: string, color: string}>
     */
    public function getCartProperty(): array
    {
        return Cart::lines()->values()->all();
    }

    public function getSubtotalProperty(): int
    {
        return (int) collect($this->cart)->sum(fn (array $item): int => $item['product']->price * $item['quantity']);
    }

    public function getShippingCostProperty(): int
    {
        return $this->selectedShippingQuote?->cost ?? 0;
    }

    public function getAppliedCouponProperty(): ?Coupon
    {
        if ($this->appliedCouponCode === '') {
            return null;
        }

        $coupon = Coupon::where('code', $this->appliedCouponCode)->first();

        return $coupon && $coupon->isValid($this->subtotal) ? $coupon : null;
    }

    public function getDiscountProperty(): int
    {
        return $this->appliedCoupon?->calculateDiscount($this->subtotal) ?? 0;
    }

    public function getTotalProperty(): int
    {
        return max(0, $this->subtotal + $this->shippingCost - $this->discount);
    }

    public function applyCoupon(): void
    {
        $code = strtoupper(trim($this->couponCode));

        if ($code === '') {
            session()->flash('couponError', 'Masukkan kode kupon terlebih dahulu.');

            return;
        }

        $coupon = Coupon::where('code', $code)->first();

        if (! $coupon || ! $coupon->isValid($this->subtotal)) {
            $this->appliedCouponCode = '';
            session()->flash('couponError', 'Kode kupon tidak valid, kadaluarsa, atau minimum order belum terpenuhi.');

            return;
        }

        $this->appliedCouponCode = $coupon->code;
        $hemat = number_format($coupon->calculateDiscount($this->subtotal), 0, ',', '.');
        session()->flash('couponSuccess', "Kupon {$coupon->code} berhasil! Hemat Rp{$hemat}");
    }

    public function removeCoupon(): void
    {
        $this->couponCode = '';
        $this->appliedCouponCode = '';
    }

    public function createOrder(): mixed
    {
        $this->validate();

        $lines = Cart::lines();

        if ($lines->isEmpty()) {
            session()->flash('error', 'Keranjang Anda kosong.');

            return null;
        }

        try {
            $orderNumber = DB::transaction(fn (): string => $this->storeOrder($lines));
        } catch (InsufficientStockException $e) {
            session()->flash('error', $e->getMessage());

            return null;
        }

        Cart::clear();
        OrderAccess::grant($orderNumber);

        $order = Order::with('items')->where('order_number', $orderNumber)->first();
        if ($order) {
            app(OrderNotifier::class)->orderPlaced($order);
        }

        return redirect()->route('payment', ['order' => $orderNumber]);
    }

    /**
     * Simpan pesanan beserta itemnya. Dipanggil di dalam transaksi database.
     *
     * @param  \Illuminate\Support\Collection<int, array{key: string, product: Product, quantity: int, size: string, color: string}>  $lines
     *
     * @throws InsufficientStockException
     */
    private function storeOrder($lines): string
    {
        $this->claimStock($lines);

        $subtotal = 0;
        $orderLines = [];

        foreach ($lines as $line) {
            $product = $line['product'];
            $subtotal += $product->price * $line['quantity'];

            $orderLines[] = [
                'product_id' => $product->id,
                'product_variant_id' => $line['variant']?->id,
                'product_name' => $product->name,
                'product_image' => $product->image_url,
                'product_price' => $product->price,
                'selected_size' => $line['size'],
                'selected_color' => $line['color'],
                'quantity' => $line['quantity'],
                'subtotal' => $product->price * $line['quantity'],
            ];
        }

        [$discount, $couponCode] = $this->claimCoupon($subtotal);
        $quote = $this->selectedShippingQuote;

        if (! $quote) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'shippingServiceCode' => 'Layanan pengiriman tidak tersedia untuk kota ini.',
            ]);
        }

        $shippingCost = $quote->cost;
        $expiresHours = max(1, (int) config('shop.payment.expires_after_hours', 24));

        $order = Order::create([
            'order_number' => $this->generateOrderNumber(),
            'customer_name' => $this->name,
            'customer_email' => $this->email,
            'customer_phone' => $this->phone,
            'city' => $this->city,
            'address' => $this->address,
            'notes' => $this->notes ?: null,
            'subtotal' => $subtotal,
            'discount' => $discount,
            'shipping_cost' => $shippingCost,
            'shipping_service' => $quote->courier.' '.$quote->service,
            'shipping_etd' => $quote->etd,
            'total' => max(0, $subtotal + $shippingCost - $discount),
            'coupon_code' => $couponCode,
            'status' => 'pending_payment',
            'payment_gateway' => config('shop.payment_driver', 'manual'),
            'payment_expires_at' => now()->addHours($expiresHours),
        ]);

        $order->items()->createMany($orderLines);

        return $order->order_number;
    }

    /**
     * Kurangi stok lewat update bersyarat agar aman dari checkout paralel.
     * Produk bervarian dipotong per varian, produk lama tetap per produk.
     *
     * @param  \Illuminate\Support\Collection<int, array{product: Product, variant: ProductVariant|null, quantity: int, size: string, color: string}>  $lines
     *
     * @throws InsufficientStockException
     */
    private function claimStock($lines): void
    {
        $variantClaims = [];
        $productClaims = [];

        foreach ($lines as $line) {
            $product = $line['product'];

            if ($product->hasVariants()) {
                $variant = $line['variant'];

                if (! $variant) {
                    throw new InsufficientStockException("Varian {$product->name} ({$line['size']} / {$line['color']}) sudah tidak tersedia.");
                }

                $variantClaims[$variant->id]['quantity'] = ($variantClaims[$variant->id]['quantity'] ?? 0) + $line['quantity'];
                $variantClaims[$variant->id]['line'] = $line;

                continue;
            }

            $productClaims[$product->id]['quantity'] = ($productClaims[$product->id]['quantity'] ?? 0) + $line['quantity'];
            $productClaims[$product->id]['product'] = $product;
        }

        foreach ($variantClaims as $variantId => $claim) {
            $claimed = ProductVariant::whereKey($variantId)
                ->where('stock', '>=', $claim['quantity'])
                ->decrement('stock', $claim['quantity']);

            if ($claimed === 0) {
                $line = $claim['line'];

                throw new InsufficientStockException(
                    "Stok {$line['product']->name} ukuran {$line['size']} warna {$line['color']} tidak mencukupi."
                );
            }
        }

        foreach ($productClaims as $productId => $claim) {
            $claimed = Product::whereKey($productId)
                ->where('stock', '>=', $claim['quantity'])
                ->decrement('stock', $claim['quantity']);

            if ($claimed === 0) {
                $product = $claim['product'];

                throw new InsufficientStockException("Stok {$product->name} tidak mencukupi, tersisa {$product->stock}.");
            }
        }

        foreach ($variantClaims as $claim) {
            $claim['line']['product']->syncStockFromVariants();
        }
    }

    /**
     * Klaim kuota kupon secara atomic supaya batas pemakaian tidak terlampaui.
     *
     * @return array{0: int, 1: string|null}
     */
    private function claimCoupon(int $subtotal): array
    {
        $coupon = $this->appliedCoupon;

        if (! $coupon) {
            return [0, null];
        }

        $claimed = Coupon::whereKey($coupon->id)
            ->where(fn ($query) => $query->whereNull('max_uses')->orWhereColumn('used_count', '<', 'max_uses'))
            ->increment('used_count');

        if ($claimed === 0) {
            return [0, null];
        }

        return [$coupon->calculateDiscount($subtotal), $coupon->code];
    }

    private function generateOrderNumber(): string
    {
        do {
            $orderNumber = strtoupper(Str::random(10));
        } while (Order::where('order_number', $orderNumber)->exists());

        return $orderNumber;
    }

    public function render()
    {
        return view('pages.checkout');
    }
};
?>

<div class="animate-fade-in">
    <section class="bg-teal py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h1 class="text-3xl font-bold text-ink">Checkout</h1>
            <div class="gold-line mt-2"></div>
            <p class="mt-3 max-w-2xl text-sm !text-white leading-relaxed">
                Isi data pengiriman, pilih ongkir, dan selesaikan pesanan dengan aman.
            </p>
        </div>
    </section>

    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        @if(session('error'))
            <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>
        @endif

        <div class="grid lg:grid-cols-[1.4fr_0.9fr] gap-8">

            {{-- Form --}}
            <div class="space-y-5">
                <div class="bg-panel rounded-3xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-5">Detail Pengiriman</h2>
                    <form wire:submit.prevent="createOrder" class="space-y-4">

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Nama Lengkap</label>
                            <input wire:model.defer="name" type="text" placeholder="Nama penerima"
                                   class="w-full rounded-2xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm outline-none focus:border-beige focus:ring-2 focus:ring-beige/20 @error('name') border-red-300 bg-red-50 @enderror">
                            @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Email</label>
                                <input wire:model.defer="email" type="email" placeholder="email@contoh.com"
                                       class="w-full rounded-2xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm outline-none focus:border-beige focus:ring-2 focus:ring-beige/20 @error('email') border-red-300 bg-red-50 @enderror">
                                @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Nomor Telepon</label>
                                <input wire:model.defer="phone" type="tel" placeholder="08xxxxxxxxxx"
                                       class="w-full rounded-2xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm outline-none focus:border-beige focus:ring-2 focus:ring-beige/20 @error('phone') border-red-300 bg-red-50 @enderror">
                                @error('phone') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Kota Tujuan</label>
                                <select wire:model.live="city"
                                        class="w-full rounded-2xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm outline-none focus:border-beige focus:ring-2 focus:ring-beige/20 @error('city') border-red-300 bg-red-50 @enderror">
                                    @foreach($this->cityRates as $cityName => $rate)
                                        <option value="{{ $cityName }}">
                                            {{ $cityName }} — {{ $rate === 0 ? 'Gratis' : 'dari Rp'.number_format($rate,0,',','.') }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('city') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Alamat Lengkap</label>
                                <input wire:model.defer="address" type="text" placeholder="Jalan, nomor, RT/RW"
                                       class="w-full rounded-2xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm outline-none focus:border-beige focus:ring-2 focus:ring-beige/20 @error('address') border-red-300 bg-red-50 @enderror">
                                @error('address') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        @if(count($this->shippingQuotes) > 0)
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Layanan Pengiriman</label>
                                <div class="space-y-2">
                                    @foreach($this->shippingQuotes as $quote)
                                        <label class="flex items-start gap-3 rounded-2xl border px-4 py-3 cursor-pointer transition-colors
                                                      {{ $shippingServiceCode === $quote->code ? 'border-deep bg-beige/40' : 'border-gray-200 bg-gray-50 hover:border-beige' }}">
                                            <input type="radio" wire:model.live="shippingServiceCode" value="{{ $quote->code }}"
                                                   class="mt-1 size-4 text-deep focus:ring-beige/20">
                                            <span class="text-sm text-gray-800">
                                                <span class="font-semibold">{{ $quote->courier }} {{ $quote->service }}</span>
                                                <span class="block text-xs text-ink mt-0.5">
                                                    {{ $quote->cost === 0 ? 'Gratis ongkir' : 'Rp'.number_format($quote->cost,0,',','.') }}
                                                    · estimasi {{ $quote->etd }}
                                                </span>
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                                @error('shippingServiceCode') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                        @endif

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Catatan Pesanan <span class="text-ink">(opsional)</span></label>
                            <textarea wire:model.defer="notes" rows="3" placeholder="Instruksi khusus untuk pengiriman..."
                                      class="w-full rounded-2xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm outline-none focus:border-beige focus:ring-2 focus:ring-beige/20 resize-none"></textarea>
                        </div>

                        {{-- Kupon --}}
                        <div class="bg-gray-50 rounded-2xl p-4 border border-gray-100">
                            <p class="text-sm font-semibold text-gray-700 mb-2">Kode Kupon / Promo</p>
                            @if(session('couponError'))
                                <p class="text-xs text-red-600 mb-2">{{ session('couponError') }}</p>
                            @endif
                            @if(session('couponSuccess'))
                                <p class="text-xs text-green-700 mb-2">{{ session('couponSuccess') }}</p>
                            @endif

                            @if($this->appliedCoupon)
                                <div class="flex items-center justify-between bg-green-50 border border-green-200 rounded-xl px-4 py-2.5">
                                    <div>
                                        <span class="text-sm font-bold text-green-700">{{ $this->appliedCoupon->code }}</span>
                                        <span class="ml-2 text-xs text-green-600">Hemat Rp{{ number_format($this->discount,0,',','.') }}</span>
                                    </div>
                                    <button type="button" wire:click="removeCoupon" class="text-xs text-red-500 hover:text-red-700">Hapus</button>
                                </div>
                            @else
                                <div class="flex gap-2">
                                    <input wire:model.defer="couponCode" type="text" placeholder="Masukkan kode kupon" style="text-transform:uppercase"
                                           class="flex-1 rounded-xl border border-gray-200 bg-panel px-3 py-2.5 text-sm outline-none focus:border-beige focus:ring-2 focus:ring-beige/20">
                                    <button type="button" wire:click="applyCoupon"
                                            class="bg-deep text-coral px-4 py-2.5 rounded-xl text-sm font-semibold hover:bg-deep-dark transition-colors">
                                        Pakai
                                    </button>
                                </div>
                            @endif
                        </div>

                        @if($this->shippingCost === 0)
                            <div class="flex items-center gap-2 bg-green-50 border border-green-200 rounded-2xl px-4 py-3">
                                <p class="text-sm text-green-700 font-medium">🎉 Gratis ongkir untuk wilayah Bandung!</p>
                            </div>
                        @else
                            <div class="flex items-center gap-2 bg-blue-50 border border-blue-200 rounded-2xl px-4 py-3">
                                <p class="text-sm text-blue-700">
                                    Ongkir ke <strong>{{ $city }}</strong>
                                    @if($this->selectedShippingQuote)
                                        via <strong>{{ $this->selectedShippingQuote->courier }} {{ $this->selectedShippingQuote->service }}</strong>
                                    @endif:
                                    <strong>Rp{{ number_format($this->shippingCost,0,',','.') }}</strong>
                                </p>
                            </div>
                        @endif

                        <button type="submit"
                                wire:loading.attr="disabled"
                                wire:target="createOrder"
                                class="w-full inline-flex items-center justify-center gap-2 rounded-full bg-cream hover:bg-coral-dark px-6 py-3.5 text-sm font-bold !text-deep hover:!text-deep transition-all shadow-md hover:-translate-y-0.5 disabled:opacity-60 disabled:cursor-not-allowed">
                            <span wire:loading.remove wire:target="createOrder">Lanjut ke Pembayaran →</span>
                            <span wire:loading wire:target="createOrder">Memproses pesanan...</span>
                        </button>
                    </form>
                </div>
            </div>

            {{-- Order Summary --}}
            <div>
                <div class="bg-panel rounded-3xl shadow-sm border border-gray-100 p-6 sticky top-24">
                    <h2 class="text-xl font-semibold text-gray-900 mb-5">Ringkasan Pesanan</h2>
                    <div class="space-y-3 max-h-60 overflow-y-auto pr-1">
                        @forelse($this->cart as $item)
                            <div class="flex items-center gap-3">
                                <img src="{{ $item['product']->image_url }}" class="h-14 w-14 rounded-xl object-cover flex-shrink-0 border border-gray-100">
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-semibold text-gray-900 truncate">{{ $item['product']->name }}</p>
                                    <p class="text-xs text-ink">{{ $item['size'] }} · {{ $item['color'] }} · {{ $item['quantity'] }} × {{ $item['product']->formatted_price }}</p>
                                </div>
                                <p class="text-sm font-bold text-gray-900 flex-shrink-0">
                                    Rp{{ number_format($item['product']->price * $item['quantity'],0,',','.') }}
                                </p>
                            </div>
                        @empty
                            <p class="text-sm text-ink text-center py-4">Keranjang kosong.</p>
                        @endforelse
                    </div>

                    <div class="mt-5 border-t border-gray-100 pt-4 space-y-2.5 text-sm">
                        <div class="flex justify-between text-ink">
                            <span>Subtotal</span>
                            <span>Rp{{ number_format($this->subtotal,0,',','.') }}</span>
                        </div>
                        <div class="flex justify-between text-ink">
                            <span>Ongkir ({{ $city }})</span>
                            <span class="{{ $this->shippingCost === 0 ? 'text-green-600' : '' }}">
                                {{ $this->shippingCost === 0 ? 'Gratis' : 'Rp'.number_format($this->shippingCost,0,',','.') }}
                            </span>
                        </div>
                        @if($this->discount > 0)
                            <div class="flex justify-between text-green-700">
                                <span>Diskon ({{ $this->appliedCoupon?->code }})</span>
                                <span>−Rp{{ number_format($this->discount,0,',','.') }}</span>
                            </div>
                        @endif
                        <div class="flex justify-between text-base font-bold text-gray-900 border-t pt-2.5">
                            <span>Total</span>
                            <span class="text-deep">Rp{{ number_format($this->total,0,',','.') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>



