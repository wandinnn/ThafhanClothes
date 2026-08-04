<?php

use App\Models\Order;
use App\Support\OrderAccess;
use Livewire\Component;

new class extends Component {
    /**
     * Disimpan sebagai nomor pesanan, bukan model bernama `order`, agar Livewire
     * tidak mengikat parameter route `{order}` lewat implicit route binding.
     */
    public string $orderNumber = '';

    public string $phoneTail = '';

    public ?string $unlockError = null;

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

    public function render()
    {
        $order = Order::with('items')
            ->where('order_number', $this->orderNumber)
            ->first();

        return view('pages.order-success', [
            'order' => $order,
            'verified' => $order && OrderAccess::has($order->order_number),
        ]);
    }
};
?>

<div class="animate-fade-in min-h-screen bg-beige flex items-center justify-center px-4 py-12">
    <div class="bg-panel rounded-3xl shadow-sm border border-gray-100 p-8 max-w-lg w-full text-center">
        @if(!$order)
            <p class="text-ink">Pesanan tidak ditemukan.</p>
            <a wire:navigate href="{{ route('home') }}" class="mt-4 inline-block text-deep hover:underline text-sm">Kembali ke Beranda</a>
        @elseif(! $verified)
            <h1 class="text-xl font-bold text-gray-900 mb-2">Verifikasi Akses</h1>
            <p class="text-sm text-ink mb-6">Masukkan 4 digit terakhir nomor telepon untuk melihat ringkasan pesanan.</p>
            @if($unlockError)
                <div class="mb-4 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 text-left">{{ $unlockError }}</div>
            @endif
            <form wire:submit="unlock" class="space-y-4 text-left">
                <input wire:model="phoneTail" type="text" inputmode="numeric" maxlength="4" placeholder="4 digit terakhir"
                       class="w-full rounded-3xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm outline-none focus:border-beige">
                <button type="submit" class="w-full rounded-full bg-beige px-6 py-3 text-sm font-semibold text-deep hover:bg-coral transition-colors">
                    Buka Ringkasan
                </button>
            </form>
        @else
            <div class="w-16 h-16 bg-beige-dark rounded-full flex items-center justify-center mx-auto mb-5">
                <svg xmlns="http://www.w3.org/2000/svg" class="size-8 text-deep" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                </svg>
            </div>

            <h1 class="text-2xl font-bold text-gray-900 mb-1">Terima kasih! Pesanan sudah terdaftar.</h1>
            <p class="text-sm text-ink mb-6">
                Nomor pesanan Anda adalah <strong class="text-gray-900">{{ strtoupper($order->order_number) }}</strong>.
            </p>

            <div class="grid grid-cols-2 gap-4 mb-6">
                <div class="bg-gray-50 rounded-2xl p-4 text-left">
                    <p class="text-xs text-ink mb-1">Nama</p>
                    <p class="font-bold text-gray-900">{{ $order->customer_name }}</p>
                </div>
                <div class="bg-gray-50 rounded-2xl p-4 text-left">
                    <p class="text-xs text-ink mb-1">Status Pesanan</p>
                    <span class="text-sm font-bold inline-block px-2 py-0.5 rounded-full border {{ $order->status_color }}">
                        {{ $order->status_label }}
                    </span>
                </div>
                <div class="bg-gray-50 rounded-2xl p-4 text-left">
                    <p class="text-xs text-ink mb-1">Kota</p>
                    <p class="font-semibold text-gray-900">{{ $order->city }}</p>
                </div>
                <div class="bg-gray-50 rounded-2xl p-4 text-left">
                    <p class="text-xs text-ink mb-1">Tanggal</p>
                    <p class="font-semibold text-gray-900">{{ $order->created_at->format('d M Y') }}</p>
                </div>
            </div>

            <div class="bg-gray-50 rounded-2xl p-5 text-left mb-6">
                <h3 class="font-semibold text-gray-900 mb-3">Ringkasan</h3>
                <div class="space-y-1.5 text-sm">
                    <div class="flex justify-between text-ink">
                        <span>Subtotal</span>
                        <span>Rp{{ number_format($order->subtotal,0,',','.') }}</span>
                    </div>
                    @if($order->discount > 0)
                        <div class="flex justify-between text-green-700">
                            <span>Diskon</span>
                            <span>−Rp{{ number_format($order->discount,0,',','.') }}</span>
                        </div>
                    @endif
                    <div class="flex justify-between text-ink">
                        <span>Ongkos Kirim</span>
                        <span>Rp{{ number_format($order->shipping_cost,0,',','.') }}</span>
                    </div>
                    <div class="flex justify-between font-bold text-gray-900 border-t border-gray-200 pt-2 mt-2">
                        <span>Total Pembayaran</span>
                        <span class="text-deep">Rp{{ number_format($order->total,0,',','.') }}</span>
                    </div>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row gap-3">
                <a wire:navigate href="{{ route('order.detail', $order->order_number) }}"
                   class="flex-1 bg-deep hover:bg-deep-dark text-coral font-bold py-3 rounded-2xl transition-colors text-sm flex items-center justify-center">
                    Lihat Detail Pesanan
                </a>
                <a wire:navigate href="{{ route('home') }}"
                   class="flex-1 border-2 border-beige text-deep hover:bg-beige/10 font-bold py-3 rounded-2xl transition-colors text-sm flex items-center justify-center">
                    Kembali ke Beranda
                </a>
            </div>
        @endif
    </div>
</div>



