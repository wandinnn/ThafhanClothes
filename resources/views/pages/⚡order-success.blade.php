<?php

use Livewire\Component;
use App\Models\Order;

new class extends Component {
    public ?Order $order = null;

    public function mount(string $order): void
    {
        $this->order = Order::with('items')
            ->where('order_number', $order)
            ->first();
    }

    public function render()
    {
        return view('pages.⚡order-success');
    }
};
?>

<div class="animate-fade-in min-h-screen bg-[#f4f4f4] flex items-center justify-center px-4 py-12">
    <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-8 max-w-lg w-full text-center">
        @if(!$order)
            <p class="text-[#4F252E]">Pesanan tidak ditemukan.</p>
            <a href="{{ route('home') }}" class="mt-4 inline-block text-[#1F2A2A] hover:underline text-sm">Kembali ke Beranda</a>
        @else
            <div class="w-16 h-16 bg-[#f5ead3] rounded-full flex items-center justify-center mx-auto mb-5">
                <svg xmlns="http://www.w3.org/2000/svg" class="size-8 text-[#1F2A2A]" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                </svg>
            </div>

            <h1 class="text-2xl font-bold text-gray-900 mb-1">Terima kasih! Pesanan sudah terdaftar.</h1>
            <p class="text-sm text-[#4F252E] mb-6">
                Nomor pesanan Anda adalah <strong class="text-gray-900">{{ strtoupper($order->order_number) }}</strong>.
            </p>

            <div class="grid grid-cols-2 gap-4 mb-6">
                <div class="bg-gray-50 rounded-2xl p-4 text-left">
                    <p class="text-xs text-[#4F252E] mb-1">Nama</p>
                    <p class="font-bold text-gray-900">{{ $order->customer_name }}</p>
                </div>
                <div class="bg-gray-50 rounded-2xl p-4 text-left">
                    <p class="text-xs text-[#4F252E] mb-1">Status Pesanan</p>
                    <span class="text-sm font-bold inline-block px-2 py-0.5 rounded-full border {{ $order->status_color }}">
                        {{ $order->status_label }}
                    </span>
                </div>
                <div class="bg-gray-50 rounded-2xl p-4 text-left">
                    <p class="text-xs text-[#4F252E] mb-1">Kota</p>
                    <p class="font-semibold text-gray-900">{{ $order->city }}</p>
                </div>
                <div class="bg-gray-50 rounded-2xl p-4 text-left">
                    <p class="text-xs text-[#4F252E] mb-1">Tanggal</p>
                    <p class="font-semibold text-gray-900">{{ $order->created_at->format('d M Y') }}</p>
                </div>
            </div>

            <div class="bg-gray-50 rounded-2xl p-5 text-left mb-6">
                <h3 class="font-semibold text-gray-900 mb-3">Ringkasan</h3>
                <div class="space-y-1.5 text-sm">
                    <div class="flex justify-between text-[#4F252E]">
                        <span>Subtotal</span>
                        <span>Rp{{ number_format($order->subtotal,0,',','.') }}</span>
                    </div>
                    @if($order->discount > 0)
                        <div class="flex justify-between text-green-700">
                            <span>Diskon</span>
                            <span>−Rp{{ number_format($order->discount,0,',','.') }}</span>
                        </div>
                    @endif
                    <div class="flex justify-between text-[#4F252E]">
                        <span>Ongkos Kirim</span>
                        <span>Rp{{ number_format($order->shipping_cost,0,',','.') }}</span>
                    </div>
                    <div class="flex justify-between font-bold text-gray-900 border-t border-gray-200 pt-2 mt-2">
                        <span>Total Pembayaran</span>
                        <span class="text-[#1F2A2A]">Rp{{ number_format($order->total,0,',','.') }}</span>
                    </div>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row gap-3">
                <a href="{{ route('order.detail', $order->order_number) }}"
                   class="flex-1 bg-[#8BDFDD] hover:bg-[#2F2A2A] text-[#4F252E] font-bold py-3 rounded-2xl transition-colors text-sm flex items-center justify-center">
                    Lihat Detail Pesanan
                </a>
                <a href="{{ route('home') }}"
                   class="flex-1 border-2 border-[#FFF6DE] text-[#1F2A2A] hover:bg-[#FFF6DE]/10 font-bold py-3 rounded-2xl transition-colors text-sm flex items-center justify-center">
                    Kembali ke Beranda
                </a>
            </div>
        @endif
    </div>
</div>



