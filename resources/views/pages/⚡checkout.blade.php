<?php

use Livewire\Component;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Coupon;
use Illuminate\Support\Str;

new class extends Component {
    public string  $name        = '';
    public string  $email       = '';
    public string  $phone       = '';
    public string  $city        = 'Bandung';
    public string  $address     = '';
    public string  $notes       = '';
    public array   $cart        = [];
    public int     $shippingCost = 0;
    public string  $couponCode   = '';
    public int     $discount     = 0;
    public ?array  $appliedCoupon = null;

    public array $cityRates = [
        'Bandung' => 0, 'Cimahi' => 5000, 'Cianjur' => 10000, 'Garut' => 10000,
        'Tasikmalaya' => 15000, 'Sukabumi' => 15000, 'Ciamis' => 15000,
        'Subang' => 15000, 'Purwakarta' => 15000, 'Indramayu' => 18000,
        'Karawang' => 18000, 'Cirebon' => 20000,
        'Bogor' => 20000, 'Depok' => 22000, 'Bekasi' => 22000,
        'Jakarta' => 25000, 'Tangerang' => 25000, 'Tangerang Selatan' => 25000,
        'Serang' => 28000, 'Cilegon' => 28000, 'Pandeglang' => 28000,
        'Purwokerto' => 28000, 'Cilacap' => 28000, 'Pekalongan' => 30000,
        'Tegal' => 30000, 'Semarang' => 35000, 'Salatiga' => 35000,
        'Magelang' => 35000, 'Kudus' => 37000, 'Solo' => 38000,
        'Yogyakarta' => 38000, 'Sleman' => 38000, 'Bantul' => 38000,
        'Madiun' => 45000, 'Kediri' => 47000, 'Blitar' => 47000,
        'Malang' => 50000, 'Surabaya' => 50000, 'Sidoarjo' => 50000,
        'Jember' => 55000, 'Banyuwangi' => 58000,
        'Bali (Denpasar)' => 58000, 'Mataram (Lombok)' => 65000, 'Kupang' => 85000,
        'Bandar Lampung' => 50000, 'Palembang' => 55000, 'Jambi' => 60000,
        'Padang' => 62000, 'Pekanbaru' => 65000, 'Batam' => 65000,
        'Medan' => 70000, 'Banda Aceh' => 80000,
        'Pontianak' => 68000, 'Banjarmasin' => 72000, 'Balikpapan' => 75000,
        'Samarinda' => 75000, 'Makassar' => 75000, 'Manado' => 82000,
        'Ambon' => 88000, 'Jayapura' => 98000,
    ];

    protected array $rules = [
        'name'    => ['required', 'min:3'],
        'phone'   => ['required', 'min:9'],
        'city'    => ['required'],
        'address' => ['required', 'min:10'],
    ];

    protected array $messages = [
        'name.required'    => 'Nama lengkap wajib diisi.',
        'name.min'         => 'Nama minimal 3 karakter.',
        'phone.required'   => 'Nomor telepon wajib diisi.',
        'phone.min'        => 'Nomor telepon tidak valid.',
        'address.required' => 'Alamat lengkap wajib diisi.',
        'address.min'      => 'Alamat minimal 10 karakter.',
    ];

    public function mount(): void
    {
        $this->loadCart();
        $this->shippingCost = $this->cityRates[$this->city] ?? 0;
    }

    public function loadCart(): void
    {
        $raw        = session('cart', []);
        $this->cart = [];
        foreach ($raw as $productId => $quantity) {
            $product = Product::find($productId);
            if ($product) {
                $this->cart[] = ['product' => $product, 'quantity' => $quantity];
            }
        }
    }

    public function updatedCity(string $value): void
    {
        $this->shippingCost = $this->cityRates[$value] ?? 0;
    }

    public function getSubtotalProperty(): int
    {
        return collect($this->cart)->sum(fn ($i) => $i['product']->price * $i['quantity']);
    }

    public function getTotalProperty(): int
    {
        return max(0, $this->subtotal + $this->shippingCost - $this->discount);
    }

    public function applyCoupon(): void
    {
        if (empty(trim($this->couponCode))) {
            session()->flash('couponError', 'Masukkan kode kupon terlebih dahulu.');
            return;
        }

        $coupon = Coupon::where('code', strtoupper(trim($this->couponCode)))->first();

        if (!$coupon || !$coupon->isValid($this->subtotal)) {
            $this->discount      = 0;
            $this->appliedCoupon = null;
            session()->flash('couponError', 'Kode kupon tidak valid, kadaluarsa, atau minimum order belum terpenuhi.');
            return;
        }

        $this->discount      = $coupon->calculateDiscount($this->subtotal);
        $this->appliedCoupon = $coupon->toArray();
        session()->flash('couponSuccess', "Kupon {$coupon->code} berhasil! Hemat Rp" . number_format($this->discount, 0, ',', '.'));
    }

    public function removeCoupon(): void
    {
        $this->couponCode    = '';
        $this->discount      = 0;
        $this->appliedCoupon = null;
    }

    public function createOrder(): mixed
    {
        if (empty($this->cart)) {
            session()->flash('error', 'Keranjang Anda kosong.');
            return null;
        }

        $this->validate();

        $orderId = strtoupper(Str::random(10));

        // Simpan order ke database
        $order = Order::create([
            'order_number'  => $orderId,
            'customer_name' => $this->name,
            'customer_email'=> $this->email ?: null,
            'customer_phone'=> $this->phone,
            'city'          => $this->city,
            'address'       => $this->address,
            'notes'         => $this->notes ?: null,
            'subtotal'      => $this->subtotal,
            'discount'      => $this->discount,
            'shipping_cost' => $this->shippingCost,
            'total'         => $this->total,
            'coupon_code'   => $this->appliedCoupon['code'] ?? null,
            'status'        => 'pending_payment',
        ]);

        // Simpan setiap item ke database
        $meta = session('cart_meta', []);
        foreach ($this->cart as $item) {
            $itemMeta = $meta[$item['product']->id] ?? [];
            OrderItem::create([
                'order_id'       => $order->id,
                'product_id'     => $item['product']->id,
                'product_name'   => $item['product']->name,
                'product_image'  => $item['product']->image_url,
                'product_price'  => $item['product']->price,
                'selected_size'  => $itemMeta['size'] ?? null,
                'selected_color' => $itemMeta['color'] ?? null,
                'quantity'       => $item['quantity'],
                'subtotal'       => $item['product']->price * $item['quantity'],
            ]);

            // Kurangi stok
            $item['product']->decrement('stock', $item['quantity']);
        }

        // Tandai kupon sudah digunakan
        if ($this->appliedCoupon) {
            Coupon::where('code', $this->appliedCoupon['code'])->increment('used_count');
        }

        // Bersihkan keranjang dari session
        session()->forget(['cart', 'cart_meta']);

        return redirect()->route('payment', ['order' => $orderId]);
    }

    public function render()
    {
        return view('pages.⚡checkout');
    }
};
?>

<div class="animate-fade-in">
    <section class="bg-[#8BDFDD] py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h1 class="text-3xl font-bold text-[#4F252E]">Checkout</h1>
            <div class="gold-line mt-2"></div>
        </div>
    </section>

    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        @if(session('error'))
            <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>
        @endif

        <div class="grid lg:grid-cols-[1.4fr_0.9fr] gap-8">

            {{-- Form --}}
            <div class="space-y-5">
                <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-5">Detail Pengiriman</h2>
                    <form wire:submit.prevent="createOrder" class="space-y-4">

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Nama Lengkap</label>
                            <input wire:model.defer="name" type="text" placeholder="Nama penerima"
                                   class="w-full rounded-2xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm outline-none focus:border-[#FFF6DE] focus:ring-2 focus:ring-[#FFF6DE]/20 @error('name') border-red-300 bg-red-50 @enderror">
                            @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Email <span class="text-[#4F252E]">(opsional)</span></label>
                                <input wire:model.defer="email" type="email" placeholder="email@contoh.com"
                                       class="w-full rounded-2xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm outline-none focus:border-[#FFF6DE] focus:ring-2 focus:ring-[#FFF6DE]/20">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Nomor Telepon</label>
                                <input wire:model.defer="phone" type="tel" placeholder="08xxxxxxxxxx"
                                       class="w-full rounded-2xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm outline-none focus:border-[#FFF6DE] focus:ring-2 focus:ring-[#FFF6DE]/20 @error('phone') border-red-300 bg-red-50 @enderror">
                                @error('phone') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Kota Tujuan</label>
                                <select wire:model.live="city"
                                        class="w-full rounded-2xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm outline-none focus:border-[#FFF6DE] focus:ring-2 focus:ring-[#FFF6DE]/20">
                                    @foreach($cityRates as $cityName => $rate)
                                        <option value="{{ $cityName }}">
                                            {{ $cityName }} — {{ $rate === 0 ? 'Gratis' : 'Rp'.number_format($rate,0,',','.') }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Alamat Lengkap</label>
                                <input wire:model.defer="address" type="text" placeholder="Jalan, nomor, RT/RW"
                                       class="w-full rounded-2xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm outline-none focus:border-[#FFF6DE] focus:ring-2 focus:ring-[#FFF6DE]/20 @error('address') border-red-300 bg-red-50 @enderror">
                                @error('address') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Catatan Pesanan <span class="text-[#4F252E]">(opsional)</span></label>
                            <textarea wire:model.defer="notes" rows="3" placeholder="Instruksi khusus untuk pengiriman..."
                                      class="w-full rounded-2xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm outline-none focus:border-[#FFF6DE] focus:ring-2 focus:ring-[#FFF6DE]/20 resize-none"></textarea>
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

                            @if($appliedCoupon)
                                <div class="flex items-center justify-between bg-green-50 border border-green-200 rounded-xl px-4 py-2.5">
                                    <div>
                                        <span class="text-sm font-bold text-green-700">{{ $appliedCoupon['code'] }}</span>
                                        <span class="ml-2 text-xs text-green-600">Hemat Rp{{ number_format($discount,0,',','.') }}</span>
                                    </div>
                                    <button type="button" wire:click="removeCoupon" class="text-xs text-red-500 hover:text-red-700">Hapus</button>
                                </div>
                            @else
                                <div class="flex gap-2">
                                    <input wire:model.defer="couponCode" type="text" placeholder="Masukkan kode kupon" style="text-transform:uppercase"
                                           class="flex-1 rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-sm outline-none focus:border-[#FFF6DE] focus:ring-2 focus:ring-[#FFF6DE]/20">
                                    <button type="button" wire:click="applyCoupon"
                                            class="bg-[#8BDFDD] text-[#4F252E] px-4 py-2.5 rounded-xl text-sm font-semibold hover:bg-[#2F2A2A] transition-colors">
                                        Pakai
                                    </button>
                                </div>
                            @endif
                        </div>

                        @if($shippingCost === 0)
                            <div class="flex items-center gap-2 bg-green-50 border border-green-200 rounded-2xl px-4 py-3">
                                <p class="text-sm text-green-700 font-medium">🎉 Gratis ongkir untuk wilayah Bandung!</p>
                            </div>
                        @else
                            <div class="flex items-center gap-2 bg-blue-50 border border-blue-200 rounded-2xl px-4 py-3">
                                <p class="text-sm text-blue-700">
                                    Ongkir ke <strong>{{ $city }}</strong>: <strong>Rp{{ number_format($shippingCost,0,',','.') }}</strong>
                                </p>
                            </div>
                        @endif

                        <button type="submit"
                                class="w-full inline-flex items-center justify-center gap-2 rounded-full bg-[#FFF6DE] px-6 py-3.5 text-sm font-bold text-[#1F2A2A] hover:bg-[#F48F68] transition-all shadow-md hover:-translate-y-0.5">
                            Lanjut ke Pembayaran →
                        </button>
                    </form>
                </div>
            </div>

            {{-- Order Summary --}}
            <div>
                <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-6 sticky top-24">
                    <h2 class="text-xl font-semibold text-gray-900 mb-5">Ringkasan Pesanan</h2>
                    <div class="space-y-3 max-h-60 overflow-y-auto pr-1">
                        @forelse($cart as $item)
                            <div class="flex items-center gap-3">
                                <img src="{{ $item['product']->image_url }}" class="h-14 w-14 rounded-xl object-cover flex-shrink-0 border border-gray-100">
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-semibold text-gray-900 truncate">{{ $item['product']->name }}</p>
                                    <p class="text-xs text-[#4F252E]">{{ $item['quantity'] }} × {{ $item['product']->formatted_price }}</p>
                                </div>
                                <p class="text-sm font-bold text-gray-900 flex-shrink-0">
                                    Rp{{ number_format($item['product']->price * $item['quantity'],0,',','.') }}
                                </p>
                            </div>
                        @empty
                            <p class="text-sm text-[#4F252E] text-center py-4">Keranjang kosong.</p>
                        @endforelse
                    </div>

                    <div class="mt-5 border-t border-gray-100 pt-4 space-y-2.5 text-sm">
                        <div class="flex justify-between text-[#4F252E]">
                            <span>Subtotal</span>
                            <span>Rp{{ number_format($this->subtotal,0,',','.') }}</span>
                        </div>
                        <div class="flex justify-between text-[#4F252E]">
                            <span>Ongkir ({{ $city }})</span>
                            <span class="{{ $shippingCost === 0 ? 'text-green-600' : '' }}">
                                {{ $shippingCost === 0 ? 'Gratis' : 'Rp'.number_format($shippingCost,0,',','.') }}
                            </span>
                        </div>
                        @if($discount > 0)
                            <div class="flex justify-between text-green-700">
                                <span>Diskon ({{ $appliedCoupon['code'] ?? '' }})</span>
                                <span>−Rp{{ number_format($discount,0,',','.') }}</span>
                            </div>
                        @endif
                        <div class="flex justify-between text-base font-bold text-gray-900 border-t pt-2.5">
                            <span>Total</span>
                            <span class="text-[#1F2A2A]">Rp{{ number_format($this->total,0,',','.') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>



