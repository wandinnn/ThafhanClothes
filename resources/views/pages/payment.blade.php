<?php

use App\Contracts\PaymentGateway;
use App\Models\Order;
use App\Services\OrderNotifier;
use App\Support\OrderAccess;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component {
    use WithFileUploads;

    /**
     * Disimpan sebagai nomor pesanan, bukan model bernama `order`, agar Livewire
     * tidak mengikat parameter route `{order}` lewat implicit route binding.
     */
    public string $orderNumber = '';

    public string $activeTab = 'transfer';

    public string $phoneTail = '';

    public ?string $unlockError = null;

    /**
     * Status pesanan yang masih boleh menerima unggahan bukti pembayaran.
     */
    private const UPLOADABLE_STATUSES = ['pending_payment', 'payment_uploaded'];

    private const PAYMENT_METHODS = ['transfer', 'qris'];

    #[Validate('required|image|mimes:jpg,jpeg,png,webp|max:5120', message: [
        'required' => 'Bukti pembayaran wajib diunggah.',
        'image' => 'File harus berupa gambar.',
        'mimes' => 'Format harus JPG, PNG, atau WEBP.',
        'max' => 'Ukuran file maksimal 5MB.',
    ])]
    public $proofPhoto = null;

    public function mount(string $order): void
    {
        $this->orderNumber = strtoupper(trim($order));
    }

    public function unlock(): void
    {
        $this->unlockError = null;

        if (strlen(preg_replace('/\D+/', '', $this->phoneTail) ?? '') < 4) {
            $this->unlockError = 'Masukkan 4 digit terakhir nomor telepon.';

            return;
        }

        if (! OrderAccess::findAndVerify($this->orderNumber, $this->phoneTail)) {
            $this->unlockError = 'Nomor telepon tidak cocok dengan pesanan ini.';
        }
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = in_array($tab, self::PAYMENT_METHODS, true) ? $tab : 'transfer';
    }

    public function payWithGateway(): mixed
    {
        if (! OrderAccess::has($this->orderNumber)) {
            session()->flash('error', 'Verifikasi nomor telepon terlebih dahulu.');

            return null;
        }

        $order = $this->findOrder();

        if (! $order || ! in_array($order->status, self::UPLOADABLE_STATUSES, true)) {
            session()->flash('error', 'Pesanan tidak bisa dibayar saat ini.');

            return null;
        }

        if ($order->isPaymentExpired()) {
            session()->flash('error', 'Batas waktu pembayaran sudah habis. Buat pesanan baru.');

            return null;
        }

        /** @var PaymentGateway $gateway */
        $gateway = app(PaymentGateway::class);

        try {
            if ($gateway->supportsInstantPay()) {
                $session = $gateway->createSession($order);

                if ($session->mode === 'simulate') {
                    $previous = $order->status;
                    $settled = $gateway->settle($order, $session->transactionId);
                    app(OrderNotifier::class)->statusUpdated($settled, $previous);
                    OrderAccess::grant($settled->order_number);

                    return redirect()->route('order.success', ['order' => $settled->order_number]);
                }

                if ($session->mode === 'snap' && filled($session->meta['token'] ?? null)) {
                    $this->dispatch('midtrans-pay', [
                        'token' => (string) $session->meta['token'],
                        'clientKey' => (string) ($session->meta['client_key'] ?? ''),
                        'isProduction' => (bool) ($session->meta['is_production'] ?? false),
                        'successUrl' => route('order.success', ['order' => $order->order_number]),
                    ]);

                    return null;
                }

                if ($session->mode === 'redirect' && filled($session->redirectUrl)) {
                    return redirect()->away($session->redirectUrl);
                }
            }
        } catch (\Throwable $e) {
            session()->flash('error', $e->getMessage());

            return null;
        }

        session()->flash('error', 'Gateway pembayaran belum siap. Gunakan transfer/QRIS.');

        return null;
    }

    public function confirmPayment(): mixed
    {
        if (! OrderAccess::has($this->orderNumber)) {
            session()->flash('error', 'Verifikasi nomor telepon terlebih dahulu.');

            return null;
        }

        $order = $this->findOrder();

        if (! $order) {
            session()->flash('error', 'Pesanan tidak ditemukan.');

            return null;
        }

        if (! in_array($order->status, self::UPLOADABLE_STATUSES, true)) {
            session()->flash('error', 'Pesanan ini sudah diproses, bukti pembayaran tidak bisa diubah lagi.');

            return null;
        }

        if ($order->isPaymentExpired()) {
            session()->flash('error', 'Batas waktu pembayaran sudah habis. Buat pesanan baru.');

            return null;
        }

        if (! app(PaymentGateway::class)->supportsProofUpload()) {
            session()->flash('error', 'Driver pembayaran ini tidak memakai unggah bukti. Gunakan tombol bayar gateway.');

            return null;
        }

        $this->validate();

        $path = $this->proofPhoto->store('payment-proofs', 'public');

        $updated = Order::whereKey($order->id)
            ->whereIn('status', self::UPLOADABLE_STATUSES)
            ->update([
                'status' => 'payment_uploaded',
                'payment_method' => $this->activeTab,
                'payment_gateway' => config('shop.payment_driver', 'manual'),
                'payment_proof_path' => $path,
                'paid_at' => now(),
            ]);

        if ($updated === 0) {
            Storage::disk('public')->delete($path);
            session()->flash('error', 'Pesanan ini sudah diproses, bukti pembayaran tidak bisa diubah lagi.');

            return null;
        }

        if ($order->payment_proof_path && $order->payment_proof_path !== $path) {
            Storage::disk('public')->delete($order->payment_proof_path);
        }

        OrderAccess::grant($order->order_number);
        app(OrderNotifier::class)->paymentProofUploaded($order->fresh(['items']));

        return redirect()->route('order.success', ['order' => $order->order_number]);
    }

    private function findOrder(): ?Order
    {
        return Order::with('items')->where('order_number', $this->orderNumber)->first();
    }

    public function render()
    {
        $order = $this->findOrder();
        $verified = $order && OrderAccess::has($order->order_number);
        $gateway = app(PaymentGateway::class);

        return view('pages.payment', [
            'order' => $order,
            'verified' => $verified,
            'canUploadProof' => $verified && $order && in_array($order->status, self::UPLOADABLE_STATUSES, true) && $gateway->supportsProofUpload(),
            'canPayInstant' => $verified && $order && in_array($order->status, self::UPLOADABLE_STATUSES, true) && $gateway->supportsInstantPay(),
            'paymentGateway' => $gateway,
            'bank' => \App\Support\ShopSettings::bank(),
        ]);
    }
};
?>

<div class="animate-fade-in min-h-screen bg-beige">

    {{-- Header --}}
    <section class="bg-teal py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h1 class="text-3xl font-bold text-ink">Pembayaran</h1>
            <div class="gold-line mt-2 mb-0"></div>
            <p class="mt-3 max-w-2xl text-sm !text-white leading-relaxed">
                @if($paymentGateway->supportsInstantPay() && ! $paymentGateway->supportsProofUpload())
                    Bayar aman lewat Midtrans (VA, e-wallet, QRIS, kartu). Status pesanan diperbarui otomatis setelah pembayaran berhasil.
                @else
                    Transfer atau scan QRIS sesuai nominal, lalu unggah bukti pembayaran untuk diproses.
                @endif
            </p>
        </div>
    </section>

    <section class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        @if(!$order)
            <div class="bg-panel rounded-3xl shadow-sm border border-gray-100 p-10 text-center">
                <p class="text-ink">Pesanan tidak ditemukan.</p>
                <a wire:navigate href="{{ route('home') }}" class="mt-4 inline-block text-sm text-deep hover:underline">Kembali ke Beranda</a>
            </div>
        @elseif(! $verified)
            <div class="mx-auto max-w-lg bg-panel rounded-3xl shadow-sm border border-gray-100 p-8">
                <h2 class="text-xl font-semibold text-gray-900">Verifikasi Akses Pembayaran</h2>
                <p class="mt-2 text-sm text-ink">
                    Masukkan 4 digit terakhir nomor telepon checkout untuk membuka halaman pembayaran pesanan
                    <strong>{{ $order->order_number }}</strong>.
                </p>

                @if($unlockError)
                    <div class="mt-4 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $unlockError }}</div>
                @endif

                <form wire:submit="unlock" class="mt-6 space-y-4">
                    <input wire:model="phoneTail" type="text" inputmode="numeric" maxlength="4" placeholder="4 digit terakhir"
                           class="w-full rounded-3xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm outline-none focus:border-beige">
                    <button type="submit" class="w-full rounded-full bg-beige px-6 py-3 text-sm font-semibold text-deep hover:bg-coral transition-colors">
                        Lanjut ke Pembayaran
                    </button>
                </form>
            </div>
        @else

            @if(session('error'))
                <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>
            @endif

            {{-- Order Info Banner --}}
            <div class="bg-deep rounded-2xl p-5 mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <p class="!text-cream font-bold text-xs uppercase tracking-wider">Nomor Pesanan</p>
                    <p class="!text-cream font-bold text-lg mt-0.5">{{ strtoupper($order->order_number) }}</p>
                </div>
                <div class="text-right">
                    <p class="!text-cream font-bold text-xs uppercase tracking-wider">Total yang Harus Dibayar</p>
                    <p class="!text-cream font-bold text-2xl mt-0.5">
                        {{ $order->formatted_total }}
                    </p>
                </div>
            </div>

            <div class="grid lg:grid-cols-[1fr_1fr] gap-6">

                {{-- =================== LEFT: Metode Pembayaran =================== --}}
                <div class="space-y-5">

                    @if($canPayInstant && ! $canUploadProof)
                        <div class="bg-panel rounded-2xl border border-teal/30 shadow-sm p-5">
                            <h3 class="font-semibold text-gray-900 mb-1">
                                {{ $paymentGateway->driver() === 'fake' ? 'Bayar (Simulasi)' : 'Bayar dengan Midtrans' }}
                            </h3>
                            <p class="text-xs text-ink mb-4 leading-relaxed">
                                @if($paymentGateway->driver() === 'fake')
                                    Mode simulasi lokal — tanpa Midtrans sungguhan. Cocok untuk uji alur di development.
                                @else
                                    Pilih metode di jendela Midtrans (transfer VA, GoPay, QRIS, kartu, dll). Setelah berhasil, pesanan dikonfirmasi otomatis.
                                @endif
                            </p>
                            <button type="button" wire:click="payWithGateway"
                                    wire:loading.attr="disabled"
                                    class="admin-btn w-full py-3 text-sm !rounded-xl">
                                <span wire:loading.remove wire:target="payWithGateway">
                                    {{ $paymentGateway->driver() === 'fake' ? 'Bayar Sekarang (Simulasi)' : 'Buka Pembayaran Midtrans' }}
                                </span>
                                <span wire:loading wire:target="payWithGateway">Memproses...</span>
                            </button>
                            <p class="mt-3 text-[11px] text-ink">
                                Jendela pembayaran tidak muncul? Pastikan popup tidak diblokir browser, atau coba refresh halaman.
                            </p>
                        </div>
                    @else
                    {{-- Tab Header --}}
                    <div class="bg-panel rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                        <div class="flex border-b border-gray-100">
                            <button wire:click="setTab('transfer')"
                                    class="flex-1 py-3.5 text-sm font-semibold transition-colors
                                           {{ $activeTab === 'transfer' ? 'bg-deep text-coral' : 'text-ink hover:text-coral hover:bg-beige-dark' }}">
                                <span class="flex items-center justify-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z"/>
                                    </svg>
                                    Transfer Bank
                                </span>
                            </button>
                            <button wire:click="setTab('qris')"
                                    class="flex-1 py-3.5 text-sm font-semibold transition-colors
                                           {{ $activeTab === 'qris' ? 'bg-deep text-coral' : 'text-ink hover:text-coral hover:bg-beige-dark' }}">
                                <span class="flex items-center justify-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0 1 3.75 9.375v-4.5ZM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 0 1-1.125-1.125v-4.5ZM13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0 1 13.5 9.375v-4.5Z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 6.75h.75v.75h-.75v-.75ZM6.75 16.5h.75v.75h-.75v-.75ZM16.5 6.75h.75v.75h-.75v-.75ZM13.5 13.5h.75v.75h-.75v-.75ZM13.5 19.5h.75v.75h-.75v-.75ZM19.5 13.5h.75v.75h-.75v-.75ZM19.5 19.5h.75v.75h-.75v-.75ZM16.5 16.5h.75v.75h-.75v-.75Z"/>
                                    </svg>
                                    QRIS
                                </span>
                            </button>
                        </div>

                        {{-- Tab Content --}}
                        <div class="p-5">
                            @if($activeTab === 'transfer')
                                {{-- Transfer Bank --}}
                                <div class="flex items-center gap-4 mb-5">
                                    <div class="w-14 h-14 rounded-2xl bg-panel border border-gray-100 flex items-center justify-center flex-shrink-0 shadow-md overflow-hidden p-1.5">
                                        <img src="{{ asset('images/seabank.png') }}" alt="SeaBank" class="w-full h-full object-contain">
                                    </div>
                                    <div>
                                        <p class="font-bold text-gray-900 text-lg">{{ $bank['name'] }}</p>
                                        <p class="text-xs text-ink">Kirim ke rekening berikut</p>
                                    </div>
                                </div>

                                <div class="space-y-3">
                                    <div class="rounded-xl bg-gray-50 border border-gray-100 px-4 py-3">
                                        <p class="text-xs text-ink mb-1">Nomor Rekening</p>
                                        <div class="flex items-center justify-between">
                                            <p class="text-lg font-bold text-gray-900 tracking-wider">{{ $bank['account'] }}</p>
                                            <button onclick="navigator.clipboard.writeText(@js($bank['account'])); this.textContent='✓ Disalin!'; setTimeout(()=>this.textContent='Salin',2000)"
                                                    class="text-xs font-semibold text-deep hover:text-coral border border-deep/20 px-3 py-1 rounded-full transition-colors">
                                                Salin
                                            </button>
                                        </div>
                                    </div>

                                    <div class="rounded-xl bg-gray-50 border border-gray-100 px-4 py-3">
                                        <p class="text-xs text-ink mb-1">Atas Nama</p>
                                        <p class="font-bold text-gray-900">{{ $bank['holder'] }}</p>
                                    </div>

                                    <div class="rounded-xl bg-beige/10 border border-deep/15 px-4 py-3">
                                        <p class="text-xs text-coral mb-1 font-medium">Jumlah Transfer (Pastikan Tepat)</p>
                                        <div class="flex items-center justify-between">
                                            <p class="text-lg font-black text-deep">
                                                {{ $order->formatted_total }}
                                            </p>
                                            <button onclick="navigator.clipboard.writeText('{{ $order->total }}'); this.textContent='✓ Disalin!'; setTimeout(()=>this.textContent='Salin',2000)"
                                                    class="text-xs font-semibold text-deep hover:text-coral border border-deep/20 px-3 py-1 rounded-full transition-colors">
                                                Salin
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                    <div class="mt-4 rounded-xl bg-yellow-50 border border-yellow-200 px-4 py-3">
                                        <p class="text-xs text-yellow-800 font-medium">⚠️ Penting:</p>
                                        <ul class="text-xs text-yellow-700 mt-1 space-y-0.5 list-disc list-inside">
                                            <li>Transfer sesuai nominal yang tertera</li>
                                            <li>Simpan bukti transfer</li>
                                            <li>Upload bukti setelah transfer</li>
                                            <li>WAJIB konfirmasi ke Admin by WA dengan tombol di bawah</li>
                                        </ul>
                                    </div>

                            @else
                                {{-- QRIS: upload dari Admin → Pengaturan, atau public/images/qris.png --}}
                                <div class="w-full">
                                    <p class="text-sm text-ink mb-4 text-center">Scan QRIS berikut menggunakan aplikasi mobile banking atau e-wallet</p>

                                    <div class="w-full max-w-md mx-auto rounded-2xl border-2 border-gray-200 bg-panel overflow-hidden shadow-sm">
                                        @php($qrisUrl = \App\Support\ShopSettings::qrisUrl())
                                        @if ($qrisUrl)
                                            <img src="{{ $qrisUrl }}"
                                                 alt="QRIS Pembayaran ThafhanClothes"
                                                 class="w-full h-auto object-contain">
                                        @else
                                            <div class="aspect-square w-full flex flex-col items-center justify-center gap-3 p-8 bg-beige/60 border-dashed">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="size-14 text-ink/40" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.5h4.5v4.5h-4.5V4.5Zm0 10.5h4.5v4.5h-4.5V15Zm10.5-10.5h4.5v4.5h-4.5V4.5Zm0 5.25h4.5v.75h-4.5v-.75Zm-5.25 0h.75v4.5h-.75v-4.5Zm5.25 4.5h1.5v1.5h-1.5V15Zm3 0h1.5v4.5H18V15Zm-7.5 3h4.5v1.5h-4.5V18Z" />
                                                </svg>
                                                <p class="text-sm font-semibold text-deep text-center">Slot QRIS</p>
                                                <p class="text-xs text-ink text-center leading-relaxed">
                                                    Upload gambar di Admin → Pengaturan,<br>
                                                    atau simpan sebagai <code class="text-cream bg-deep px-1.5 py-0.5 rounded text-[11px]">public/images/qris.png</code>
                                                </p>
                                            </div>
                                        @endif
                                    </div>

                                    <div class="mt-4 rounded-xl bg-yellow-50 border border-yellow-200 px-4 py-3 text-left">
                                        <p class="text-xs text-yellow-800 font-medium">⚠️ Penting:</p>
                                        <ul class="text-xs text-yellow-700 mt-1 space-y-0.5 list-disc list-inside">
                                            <li>Bayar sesuai nominal yang tertera</li>
                                            <li>Simpan bukti pembayaran</li>
                                            <li>Upload bukti setelah bayar</li>
                                            <li>WAJIB konfirmasi ke Admin by WA dengan tombol di bawah</li>
                                        </ul>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                    @endif

                    {{-- Contact WA --}}
                    <a href="https://wa.me/{{ \App\Support\ShopSettings::whatsapp() }}?text={{ urlencode('Halo ThafhanClothes, saya sudah transfer untuk pesanan '.strtoupper($order->order_number).'.') }}"
                       target="_blank" rel="noopener noreferrer"
                       class="flex items-center justify-center gap-3 w-full bg-green-500 hover:bg-green-600 !text-white font-semibold py-3 rounded-2xl transition-colors shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-5 !text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
                            <path d="M12 0C5.373 0 0 5.373 0 12c0 2.122.555 4.112 1.523 5.837L.057 23.882l6.197-1.624A11.945 11.945 0 0 0 12 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 22c-1.89 0-3.663-.5-5.197-1.373l-.373-.22-3.678.964.98-3.584-.243-.392A9.956 9.956 0 0 1 2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10z"/>
                        </svg>
                        Konfirmasi via WhatsApp
                    </a>
                </div>

                {{-- =================== RIGHT: Upload Bukti =================== --}}
                <div class="space-y-5">

                    {{-- Ringkasan Pesanan --}}
                    <div class="bg-panel rounded-2xl border border-gray-100 shadow-sm p-5">
                        <h3 class="font-semibold text-gray-900 mb-4">Ringkasan Pesanan</h3>
                        <div class="space-y-3 max-h-40 overflow-y-auto pr-1">
                            @foreach($order->items as $item)
                                <div class="flex items-center gap-3">
                                    @if($item->product_image)
                                        <img src="{{ $item->product_image }}" class="w-10 h-10 rounded-lg object-cover flex-shrink-0 border border-gray-100">
                                    @endif
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-900 truncate">{{ $item->product_name }}</p>
                                        <p class="text-xs text-ink">{{ $item->quantity }} × {{ $item->formatted_price }}</p>
                                    </div>
                                    <p class="text-sm font-semibold text-gray-900 flex-shrink-0">
                                        Rp{{ number_format($item->subtotal, 0, ',', '.') }}
                                    </p>
                                </div>
                            @endforeach
                        </div>
                        <div class="mt-4 pt-4 border-t border-gray-100 space-y-2 text-sm">
                            <div class="flex justify-between text-ink">
                                <span>Subtotal</span>
                                <span>Rp{{ number_format($order->subtotal, 0, ',', '.') }}</span>
                            </div>
                            @if($order->discount > 0)
                                <div class="flex justify-between text-green-700">
                                    <span>Diskon {{ $order->coupon_code ? '('.$order->coupon_code.')' : '' }}</span>
                                    <span>−Rp{{ number_format($order->discount, 0, ',', '.') }}</span>
                                </div>
                            @endif
                            <div class="flex justify-between text-ink">
                                <span>Ongkir</span>
                                <span>Rp{{ number_format($order->shipping_cost, 0, ',', '.') }}</span>
                            </div>
                            <div class="flex justify-between font-bold text-gray-900 pt-2 border-t border-gray-100">
                                <span>Total</span>
                                <span class="text-deep">{{ $order->formatted_total }}</span>
                            </div>
                        </div>
                    </div>

                    {{-- Instant pay (fake when still showing manual UI, or midtrans duplicate avoided on left) --}}
                    @if($canPayInstant && $canUploadProof)
                        <div class="bg-panel rounded-2xl border border-teal/30 shadow-sm p-5 mb-4">
                            <h3 class="font-semibold text-gray-900 mb-1">Bayar via Gateway</h3>
                            <p class="text-xs text-ink mb-4">
                                Driver aktif: <strong>{{ $paymentGateway->driver() }}</strong>.
                                Mode simulasi lokal — tanpa Midtrans sungguhan.
                            </p>
                            <button type="button" wire:click="payWithGateway"
                                    wire:loading.attr="disabled"
                                    class="w-full rounded-xl bg-teal !text-beige font-bold py-3 text-sm hover:bg-teal-dark transition-colors">
                                <span wire:loading.remove wire:target="payWithGateway">Bayar Sekarang (Simulasi)</span>
                                <span wire:loading wire:target="payWithGateway">Memproses...</span>
                            </button>
                        </div>
                    @endif

                    {{-- Upload Bukti Pembayaran --}}
                    <div class="bg-panel rounded-2xl border border-gray-100 shadow-sm p-5">
                        <h3 class="font-semibold text-gray-900 mb-1">Upload Bukti Pembayaran</h3>
                        <p class="text-xs text-ink mb-4">Format JPG/PNG/WEBP, maks 5MB</p>

                        @if(! $canUploadProof)
                            <div class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-4 text-sm text-ink">
                                @if($canPayInstant)
                                    Pembayaran diproses lewat gateway. Setelah bayar berhasil, status pesanan diperbarui otomatis.
                                @else
                                    Pesanan ini sudah berstatus
                                    <strong class="text-gray-900">{{ $order->status_label }}</strong>,
                                    sehingga bukti pembayaran tidak bisa diubah lagi.
                                @endif
                                <a wire:navigate href="{{ route('order.detail', $order->order_number) }}" class="mt-3 inline-block font-semibold text-deep hover:underline">
                                    Lihat detail pesanan
                                </a>
                            </div>
                        @else
                        <form wire:submit="confirmPayment">

                            {{-- Preview --}}
                            @if($proofPhoto)
                                <div class="mb-4 rounded-xl overflow-hidden border border-gray-200 bg-gray-50">
                                    <img src="{{ $proofPhoto->temporaryUrl() }}"
                                         alt="Preview bukti pembayaran"
                                         class="w-full max-h-48 object-contain">
                                </div>
                            @endif

                            {{-- File Input --}}
                            <label for="proofInput"
                                   class="relative flex flex-col items-center justify-center w-full {{ $proofPhoto ? 'h-16' : 'h-32' }} border-2 border-dashed border-gray-300 rounded-xl cursor-pointer hover:border-beige hover:bg-beige/5 transition-all group">
                                <div class="flex flex-col items-center justify-center text-center">
                                    @if(!$proofPhoto)
                                        <svg xmlns="http://www.w3.org/2000/svg" class="size-8 text-ink group-hover:text-deep mb-2 transition-colors" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5"/>
                                        </svg>
                                        <p class="text-sm text-ink group-hover:text-deep transition-colors">Klik untuk pilih foto</p>
                                        <p class="text-xs text-ink mt-0.5">atau seret & lepas di sini</p>
                                    @else
                                        <p class="text-sm text-deep font-medium">Ganti Foto</p>
                                    @endif
                                </div>
                                <input id="proofInput"
                                       type="file"
                                       wire:model="proofPhoto"
                                       accept="image/jpeg,image/png,image/webp"
                                       class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                            </label>

                            {{-- Loading --}}
                            <div wire:loading wire:target="proofPhoto" class="flex items-center gap-2 mt-2 text-sm text-ink">
                                <svg class="animate-spin size-4 text-deep" viewBox="0 0 24 24" fill="none">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4z"/>
                                </svg>
                                Mengunggah...
                            </div>

                            @error('proofPhoto')
                                <p class="mt-2 text-xs text-red-600 flex items-center gap-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z"/>
                                    </svg>
                                    {{ $message }}
                                </p>
                            @enderror

                            {{-- Submit --}}
                            <button type="submit"
                                    wire:loading.attr="disabled"
                                    class="w-full mt-4 bg-deep hover:bg-deep-dark text-coral font-bold py-3.5 rounded-xl transition-all duration-200 flex items-center justify-center gap-2 disabled:opacity-60 disabled:cursor-not-allowed text-sm">
                                <span wire:loading.remove wire:target="confirmPayment">
                                    <span class="flex items-center gap-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                                        </svg>
                                        Konfirmasi Pembayaran
                                    </span>
                                </span>
                                <span wire:loading wire:target="confirmPayment" class="flex items-center gap-2">
                                    <svg class="animate-spin size-5" viewBox="0 0 24 24" fill="none">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4z"/>
                                    </svg>
                                    Memproses...
                                </span>
                            </button>
                        </form>
                        @endif
                    </div>

                </div>
            </div>
        @endif
    </section>
</div>

@script
<script>
    const ensureMidtransSnap = (clientKey, isProduction) => new Promise((resolve, reject) => {
        if (window.snap) {
            resolve();
            return;
        }

        const existing = document.getElementById('midtrans-snap-script');
        if (existing) {
            existing.addEventListener('load', () => resolve());
            existing.addEventListener('error', () => reject(new Error('Gagal memuat Midtrans Snap.')));
            return;
        }

        const script = document.createElement('script');
        script.id = 'midtrans-snap-script';
        script.src = isProduction
            ? 'https://app.midtrans.com/snap/snap.js'
            : 'https://app.sandbox.midtrans.com/snap/snap.js';
        script.setAttribute('data-client-key', clientKey);
        script.onload = () => resolve();
        script.onerror = () => reject(new Error('Gagal memuat Midtrans Snap.'));
        document.head.appendChild(script);
    });

    $wire.on('midtrans-pay', async (payload) => {
        const data = Array.isArray(payload) ? payload[0] : payload;

        try {
            await ensureMidtransSnap(data.clientKey, data.isProduction);
            window.snap.pay(data.token, {
                onSuccess: () => { window.location.href = data.successUrl; },
                onPending: () => { window.location.href = data.successUrl; },
                onError: () => { alert('Pembayaran gagal atau dibatalkan. Silakan coba lagi.'); },
                onClose: () => {},
            });
        } catch (error) {
            alert(error?.message || 'Tidak bisa membuka jendela Midtrans.');
        }
    });
</script>
@endscript
