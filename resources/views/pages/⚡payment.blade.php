<?php

use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\Validate;

new class extends Component {
    use WithFileUploads;

    public string $order     = '';
    public ?array $orderData = null;
    public string $activeTab = 'transfer';

    #[Validate('required|image|mimes:jpg,jpeg,png,webp|max:5120', message: [
        'required' => 'Bukti pembayaran wajib diunggah.',
        'image'    => 'File harus berupa gambar.',
        'mimes'    => 'Format harus JPG, PNG, atau WEBP.',
        'max'      => 'Ukuran file maksimal 5MB.',
    ])]
    public $proofPhoto = null;

    public bool $isSubmitting = false;

    public function mount(string $order): void
    {
        $this->order     = $order;
        // Baca dari DB, bukan session
        $orderModel      = \App\Models\Order::with('items')
            ->where('order_number', $order)->first();
        $this->orderData = $orderModel ? $orderModel->toArray() : null;
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function confirmPayment(): mixed
    {
        $this->validate();

        $path = $this->proofPhoto->store('payment-proofs', 'public');

        // Update di database
        \App\Models\Order::where('order_number', $this->order)->update([
            'status'             => 'payment_uploaded',
            'payment_method'     => $this->activeTab,
            'payment_proof_path' => $path,
            'paid_at'            => now(),
        ]);

        return redirect()->route('order.success', ['order' => $this->order]);
    }

    public function render()
    {
        return view('pages.⚡payment');
    }
};
?>

<div class="animate-fade-in min-h-screen bg-[#f4f4f4]">

    {{-- Header --}}
    <section class="bg-[#8BDFDD] py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h1 class="text-3xl font-bold text-[#4F252E]">Pembayaran</h1>
            <div class="gold-line mt-2 mb-0"></div>
        </div>
    </section>

    <section class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        @if(!$orderData)
            <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-10 text-center">
                <p class="text-[#4F252E]">Pesanan tidak ditemukan.</p>
                <a href="{{ route('home') }}" class="mt-4 inline-block text-sm text-[#1F2A2A] hover:underline">Kembali ke Beranda</a>
            </div>
        @else

            {{-- Order Info Banner --}}
            <div class="bg-[#8BDFDD] rounded-2xl p-5 mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <p class="text-[#4F252E] text-xs uppercase tracking-wider">Nomor Pesanan</p>
                    <p class="text-[#4F252E] font-bold text-lg mt-0.5">{{ strtoupper($orderData['id']) }}</p>
                </div>
                <div class="text-right">
                    <p class="text-[#4F252E] text-xs uppercase tracking-wider">Total yang Harus Dibayar</p>
                    <p class="text-[#1F2A2A] font-bold text-2xl mt-0.5">
                        Rp{{ number_format($orderData['total'], 0, ',', '.') }}
                    </p>
                </div>
            </div>

            <div class="grid lg:grid-cols-[1fr_1fr] gap-6">

                {{-- =================== LEFT: Metode Pembayaran =================== --}}
                <div class="space-y-5">

                    {{-- Tab Header --}}
                    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                        <div class="flex border-b border-gray-100">
                            <button wire:click="setTab('transfer')"
                                    class="flex-1 py-3.5 text-sm font-semibold transition-colors
                                           {{ $activeTab === 'transfer' ? 'bg-[#8BDFDD] text-[#4F252E]' : 'text-[#4F252E] hover:text-gray-700 hover:bg-gray-50' }}">
                                <span class="flex items-center justify-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z"/>
                                    </svg>
                                    Transfer Bank
                                </span>
                            </button>
                            <button wire:click="setTab('qris')"
                                    class="flex-1 py-3.5 text-sm font-semibold transition-colors
                                           {{ $activeTab === 'qris' ? 'bg-[#8BDFDD] text-[#4F252E]' : 'text-[#4F252E] hover:text-gray-700 hover:bg-gray-50' }}">
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
                                    <div class="w-14 h-14 rounded-2xl bg-[#1a4f8f] flex items-center justify-center flex-shrink-0 shadow-md">
                                        <span class="text-[#4F252E] font-black text-sm tracking-tight">SEA</span>
                                    </div>
                                    <div>
                                        <p class="font-bold text-gray-900 text-lg">Seabank</p>
                                        <p class="text-xs text-[#4F252E]">Kirim ke rekening berikut</p>
                                    </div>
                                </div>

                                <div class="space-y-3">
                                    <div class="rounded-xl bg-gray-50 border border-gray-100 px-4 py-3">
                                        <p class="text-xs text-[#4F252E] mb-1">Nomor Rekening</p>
                                        <div class="flex items-center justify-between">
                                            <p class="text-lg font-bold text-gray-900 tracking-wider">901550812105</p>
                                            <button onclick="navigator.clipboard.writeText('901550812105'); this.textContent='✓ Disalin!'; setTimeout(()=>this.textContent='Salin',2000)"
                                                    class="text-xs font-semibold text-[#1F2A2A] hover:text-[#F48F68] border border-[#FFF6DE] px-3 py-1 rounded-full transition-colors">
                                                Salin
                                            </button>
                                        </div>
                                    </div>

                                    <div class="rounded-xl bg-gray-50 border border-gray-100 px-4 py-3">
                                        <p class="text-xs text-[#4F252E] mb-1">Atas Nama</p>
                                        <p class="font-bold text-gray-900">NAUFAL THAFHAN</p>
                                    </div>

                                    <div class="rounded-xl bg-[#FFF6DE]/10 border border-[#FFF6DE]/30 px-4 py-3">
                                        <p class="text-xs text-[#F48F68] mb-1 font-medium">Jumlah Transfer (Pastikan Tepat)</p>
                                        <div class="flex items-center justify-between">
                                            <p class="text-lg font-black text-[#1F2A2A]">
                                                Rp{{ number_format($orderData['total'], 0, ',', '.') }}
                                            </p>
                                            <button onclick="navigator.clipboard.writeText('{{ $orderData['total'] }}'); this.textContent='✓ Disalin!'; setTimeout(()=>this.textContent='Salin',2000)"
                                                    class="text-xs font-semibold text-[#1F2A2A] hover:text-[#F48F68] border border-[#FFF6DE] px-3 py-1 rounded-full transition-colors">
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
                                    </ul>
                                </div>

                            @else
                                {{-- QRIS --}}
                                <div class="text-center">
                                    <p class="text-sm text-[#4F252E] mb-4">Scan QR berikut menggunakan aplikasi mobile banking atau e-wallet</p>

                                    <div class="inline-block p-3 bg-white border-2 border-gray-200 rounded-2xl shadow-sm mb-4">
                                        {{--
                                            CATATAN DEVELOPER:
                                            QR di bawah adalah placeholder yang berisi info rekening.
                                            Untuk QRIS resmi, ganti src dengan QR QRIS dari aplikasi Seabank Anda.
                                            Cara mendapatkan QRIS Seabank:
                                            1. Buka app Seabank
                                            2. Tap "Terima Uang" atau "QR Saya"
                                            3. Screenshot QR tersebut
                                            4. Upload ke storage/app/public/qris.png
                                            5. Ganti src di bawah: src="{{ Storage::url('qris.png') }}"
                                        --}}
                                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=SEABANK%0ANomor+Rekening%3A+901550812105%0AAtas+Nama%3A+NAUFAL+THAFHAN%0ATotal%3A+Rp{{ $orderData['total'] }}"
                                             alt="QR Code Pembayaran"
                                             class="w-48 h-48 rounded-xl">
                                    </div>

                                    <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 text-left">
                                        <p class="text-sm font-semibold text-blue-800 mb-2">Cara mendapatkan QRIS resmi:</p>
                                        <ol class="text-xs text-blue-700 space-y-1 list-decimal list-inside">
                                            <li>Buka aplikasi Seabank</li>
                                            <li>Pilih <strong>"Terima Uang"</strong> atau <strong>"QR Saya"</strong></li>
                                            <li>Screenshot QR yang muncul</li>
                                            <li>Hubungi kami via WA untuk konfirmasi</li>
                                        </ol>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Contact WA --}}
                    <a href="https://wa.me/6281324825060?text=Halo%20ThafhanClothes%2C%20saya%20sudah%20transfer%20untuk%20pesanan%20{{ strtoupper($orderData['id']) }}"
                       target="_blank" rel="noopener noreferrer"
                       class="flex items-center justify-center gap-3 w-full bg-[#25d366] hover:bg-[#1da851] text-[#4F252E] font-semibold py-3 rounded-2xl transition-colors shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
                            <path d="M12 0C5.373 0 0 5.373 0 12c0 2.122.555 4.112 1.523 5.837L.057 23.882l6.197-1.624A11.945 11.945 0 0 0 12 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 22c-1.89 0-3.663-.5-5.197-1.373l-.373-.22-3.678.964.98-3.584-.243-.392A9.956 9.956 0 0 1 2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10z"/>
                        </svg>
                        Konfirmasi via WhatsApp
                    </a>
                </div>

                {{-- =================== RIGHT: Upload Bukti =================== --}}
                <div class="space-y-5">

                    {{-- Ringkasan Pesanan --}}
                    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                        <h3 class="font-semibold text-gray-900 mb-4">Ringkasan Pesanan</h3>
                        <div class="space-y-3 max-h-40 overflow-y-auto pr-1">
                            @foreach($orderData['items'] as $item)
                                <div class="flex items-center gap-3">
                                    @if(isset($item['product_image']))
                                        <img src="{{ $item['product_image'] }}" class="w-10 h-10 rounded-lg object-cover flex-shrink-0 border border-gray-100">
                                    @endif
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-900 truncate">{{ $item['product_name'] }}</p>
                                        <p class="text-xs text-[#4F252E]">{{ $item['quantity'] }} × Rp{{ number_format($item['product_price'], 0, ',', '.') }}</p>
                                    </div>
                                    <p class="text-sm font-semibold text-gray-900 flex-shrink-0">
                                        Rp{{ number_format($item['subtotal'], 0, ',', '.') }}
                                    </p>
                                </div>
                            @endforeach
                        </div>
                        <div class="mt-4 pt-4 border-t border-gray-100 space-y-2 text-sm">
                            <div class="flex justify-between text-[#4F252E]">
                                <span>Subtotal</span>
                                <span>Rp{{ number_format($orderData['subtotal'], 0, ',', '.') }}</span>
                            </div>
                            <div class="flex justify-between text-[#4F252E]">
                                <span>Ongkir</span>
                                <span>Rp{{ number_format($orderData['shipping_cost'], 0, ',', '.') }}</span>
                            </div>
                            <div class="flex justify-between font-bold text-gray-900 pt-2 border-t border-gray-100">
                                <span>Total</span>
                                <span class="text-[#1F2A2A]">Rp{{ number_format($orderData['total'], 0, ',', '.') }}</span>
                            </div>
                        </div>
                    </div>

                    {{-- Upload Bukti Pembayaran --}}
                    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                        <h3 class="font-semibold text-gray-900 mb-1">Upload Bukti Pembayaran</h3>
                        <p class="text-xs text-[#4F252E] mb-4">Format JPG/PNG/WEBP, maks 5MB</p>

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
                                   class="relative flex flex-col items-center justify-center w-full {{ $proofPhoto ? 'h-16' : 'h-32' }} border-2 border-dashed border-gray-300 rounded-xl cursor-pointer hover:border-[#FFF6DE] hover:bg-[#FFF6DE]/5 transition-all group">
                                <div class="flex flex-col items-center justify-center text-center">
                                    @if(!$proofPhoto)
                                        <svg xmlns="http://www.w3.org/2000/svg" class="size-8 text-[#4F252E] group-hover:text-[#1F2A2A] mb-2 transition-colors" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5"/>
                                        </svg>
                                        <p class="text-sm text-[#4F252E] group-hover:text-[#1F2A2A] transition-colors">Klik untuk pilih foto</p>
                                        <p class="text-xs text-[#4F252E] mt-0.5">atau seret & lepas di sini</p>
                                    @else
                                        <p class="text-sm text-[#1F2A2A] font-medium">Ganti Foto</p>
                                    @endif
                                </div>
                                <input id="proofInput"
                                       type="file"
                                       wire:model="proofPhoto"
                                       accept="image/jpeg,image/png,image/webp"
                                       class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                            </label>

                            {{-- Loading --}}
                            <div wire:loading wire:target="proofPhoto" class="flex items-center gap-2 mt-2 text-sm text-[#4F252E]">
                                <svg class="animate-spin size-4 text-[#1F2A2A]" viewBox="0 0 24 24" fill="none">
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
                                    class="w-full mt-4 bg-[#8BDFDD] hover:bg-[#2F2A2A] text-[#4F252E] font-bold py-3.5 rounded-xl transition-all duration-200 flex items-center justify-center gap-2 disabled:opacity-60 disabled:cursor-not-allowed text-sm">
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
                    </div>

                </div>
            </div>
        @endif
    </section>
</div>



