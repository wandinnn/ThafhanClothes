<?php
use Livewire\Component;

new class extends Component {
    public string $order;
    public ?array $orderData = null;

    public function mount(string $order): void
    {
        // Baca dari DB, bukan session
        $this->order = \App\Models\Order::with('items')
            ->where('order_number', $order)
            ->firstOrFail();
    }

    public function render()
    {
        return view('pages.⚡order-detail');
    }
};
?>

<div class="animate-fade-in">
    <section class="bg-[#8BDFDD] py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h1 class="text-3xl font-bold text-[#4F252E]">Detail Pesanan</h1>
            <div class="gold-line mt-2 mb-0"></div>
        </div>
    </section>

    <section class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        @if (! $orderData)
            <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-10 text-center">
                <p class="text-lg font-semibold text-gray-900">Pesanan tidak ditemukan.</p>
                <p class="mt-3 text-sm text-[#4F252E]">Silakan periksa kembali kode pesanan atau gunakan halaman pelacakan.</p>
                <a href="{{ route('track.order') }}" class="mt-6 inline-flex rounded-full bg-[#FFF6DE] px-6 py-3 text-sm font-semibold text-[#1F2A2A] hover:bg-[#F48F68] transition-colors">
                    Lacak Pesanan
                </a>
            </div>
        @else
            <div class="grid gap-8 lg:grid-cols-[1.2fr_0.8fr]">
                <div class="space-y-6">
                    <div class="bg-white rounded-3xl border border-gray-100 p-6 shadow-sm">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm text-[#4F252E]">Nomor Pesanan</p>
                                <p class="mt-1 text-xl font-semibold text-gray-900">{{ strtoupper($orderData['id']) }}</p>
                            </div>
                            <div class="rounded-full bg-[#FFF6DE] px-4 py-2 text-sm font-semibold text-[#1F2A2A]">
                                {{ $orderData['status'] }}</div>
                        </div>
                        <p class="mt-3 text-sm text-[#4F252E]">Dibuat pada {{ $orderData['created_at'] }}</p>
                    </div>

                    <div class="bg-white rounded-3xl border border-gray-100 p-6 shadow-sm">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">Pelacakan Pesanan</h2>
                        <div class="space-y-4">
                            @php
                                $steps = ['Menunggu Pembayaran', 'Diproses', 'Dikirim', 'Sampai Tujuan'];
                            @endphp
                            @foreach($steps as $step)
                                <div class="flex items-start gap-4">
                                    <div class="mt-1 h-3 w-3 rounded-full {{ $orderData['status'] === $step || array_search($step, $steps) < array_search($orderData['status'], $steps) ? 'bg-[#FFF6DE]' : 'bg-gray-300' }}"></div>
                                    <div>
                                        <p class="font-semibold {{ $orderData['status'] === $step ? 'text-gray-900' : 'text-[#4F252E]' }}">{{ $step }}</p>
                                        <p class="text-sm text-[#4F252E]">{{ $orderData['status'] === $step ? 'Status saat ini' : 'Status berikutnya' }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="bg-white rounded-3xl border border-gray-100 p-6 shadow-sm">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">Alamat Pengiriman</h2>
                        <p class="text-sm text-[#4F252E]">{{ $orderData['name'] }}</p>
                        <p class="mt-1 text-sm text-[#4F252E]">{{ $orderData['phone'] }}</p>
                        <p class="mt-1 text-sm text-[#4F252E]">{{ $orderData['address'] }}</p>
                        <p class="mt-1 text-sm text-[#4F252E]">{{ $orderData['city'] }}</p>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="bg-white rounded-3xl border border-gray-100 p-6 shadow-sm">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">Ringkasan Pembayaran</h2>
                        <div class="space-y-3 text-sm text-gray-700">
                            @foreach($orderData['items'] as $item)
                                <div class="flex justify-between gap-4">
                                    <div>
                                        <p class="font-semibold text-gray-900">{{ $item['name'] }}</p>
                                        <p class="text-[#4F252E]">{{ $item['quantity'] }} x Rp{{ number_format($item['price'], 0, ',', '.') }}</p>
                                    </div>
                                    <p class="font-semibold text-gray-900">Rp{{ number_format($item['total'], 0, ',', '.') }}</p>
                                </div>
                            @endforeach
                        </div>
                        <div class="mt-6 border-t border-gray-200 pt-4 text-sm text-gray-700">
                            <div class="flex justify-between">
                                <span>Subtotal</span>
                                <span>Rp{{ number_format($orderData['subtotal'], 0, ',', '.') }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span>Ongkir</span>
                                <span>Rp{{ number_format($orderData['shipping'], 0, ',', '.') }}</span>
                            </div>
                            <div class="flex justify-between font-semibold text-gray-900 pt-3">
                                <span>Total</span>
                                <span>Rp{{ number_format($orderData['total'], 0, ',', '.') }}</span>
                            </div>
                        </div>
                    </div>
                    <a href="{{ route('home') }}" class="inline-flex w-full items-center justify-center rounded-full bg-[#FFF6DE] px-6 py-3 text-sm font-semibold text-[#1F2A2A] hover:bg-[#F48F68] transition-colors">
                        Kembali ke Beranda
                    </a>
                </div>
            </div>
        @endif
    </section>
</div>



