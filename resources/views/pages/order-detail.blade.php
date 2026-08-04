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

        $order = OrderAccess::findAndVerify($this->orderNumber, $this->phoneTail);

        if (! $order) {
            $this->unlockError = 'Nomor telepon tidak cocok dengan pesanan ini.';
        }
    }

    /**
     * Langkah pelacakan pesanan beserta posisi status saat ini.
     *
     * @return array<int, array{label: string, done: bool, current: bool}>
     */
    private function timelineFor(Order $order): array
    {
        if ($order->status === 'cancelled') {
            return [];
        }

        $steps = collect(Order::STATUSES)->except('cancelled');
        $currentIndex = $steps->keys()->search($order->status);

        return $steps->values()
            ->map(fn (string $label, int $index): array => [
                'label' => $label,
                'done' => $currentIndex !== false && $index < $currentIndex,
                'current' => $index === $currentIndex,
            ])
            ->all();
    }

    public function render()
    {
        $order = Order::with('items')
            ->where('order_number', $this->orderNumber)
            ->first();

        $verified = $order && OrderAccess::has($order->order_number);

        return view('pages.order-detail', [
            'order' => $order,
            'verified' => $verified,
            'timeline' => ($order && $verified) ? $this->timelineFor($order) : [],
        ]);
    }
};
?>

<div class="animate-fade-in">
    <section class="bg-teal py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h1 class="text-3xl font-bold text-ink">Detail Pesanan</h1>
            <div class="gold-line mt-2 mb-0"></div>
            <p class="mt-3 max-w-2xl text-sm !text-white leading-relaxed">
                Lihat status, ringkasan, dan nomor resi pesananmu di satu tempat.
            </p>
        </div>
    </section>

    <section class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        @if (! $order)
            <div class="bg-panel rounded-3xl shadow-sm border border-gray-100 p-10 text-center">
                <p class="text-lg font-semibold text-gray-900">Pesanan tidak ditemukan.</p>
                <p class="mt-3 text-sm text-ink">Silakan periksa kembali kode pesanan atau gunakan halaman pelacakan.</p>
                <a wire:navigate href="{{ route('track.order') }}" class="mt-6 inline-flex rounded-full bg-beige px-6 py-3 text-sm font-semibold text-deep hover:bg-coral transition-colors">
                    Lacak Pesanan
                </a>
            </div>
        @elseif (! $verified)
            <div class="mx-auto max-w-lg bg-panel rounded-3xl shadow-sm border border-gray-100 p-8">
                <h2 class="text-xl font-semibold text-gray-900">Verifikasi Akses</h2>
                <p class="mt-2 text-sm text-ink">
                    Pesanan <strong>{{ $order->order_number }}</strong> ditemukan. Masukkan 4 digit terakhir nomor telepon checkout untuk melihat detail.
                </p>

                @if($unlockError)
                    <div class="mt-4 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $unlockError }}</div>
                @endif

                <form wire:submit="unlock" class="mt-6 space-y-4">
                    <input wire:model="phoneTail" type="text" inputmode="numeric" maxlength="4" placeholder="4 digit terakhir"
                           class="w-full rounded-3xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm outline-none focus:border-beige">
                    <button type="submit" class="w-full rounded-full bg-beige px-6 py-3 text-sm font-semibold text-deep hover:bg-coral transition-colors">
                        Buka Detail
                    </button>
                </form>
            </div>
        @else
            <div class="grid gap-8 lg:grid-cols-[1.2fr_0.8fr]">
                <div class="space-y-6">
                    <div class="bg-panel rounded-3xl border border-gray-100 p-6 shadow-sm">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm text-ink">Nomor Pesanan</p>
                                <p class="mt-1 text-xl font-semibold text-gray-900">{{ strtoupper($order->order_number) }}</p>
                            </div>
                            <div class="rounded-full border px-4 py-2 text-sm font-semibold {{ $order->status_color }}">
                                {{ $order->status_label }}
                            </div>
                        </div>
                        <p class="mt-3 text-sm text-ink">Dibuat pada {{ $order->created_at->format('d M Y H:i') }}</p>
                    </div>

                    @if ($order->hasShipmentInfo())
                        <div class="bg-panel rounded-3xl border border-beige/40 p-6 shadow-sm">
                            <h2 class="text-lg font-semibold text-gray-900">Nomor Resi</h2>
                            <p class="mt-1 text-sm text-ink">Pesanan dikirim via {{ $order->shipping_courier }}@if($order->shipped_at) pada {{ $order->shipped_at->format('d M Y H:i') }}@endif.</p>
                            <p class="mt-3 inline-block rounded-2xl border border-deep/15 bg-white px-4 py-2 font-mono text-base font-bold tracking-wide text-gray-900">
                                {{ $order->tracking_number }}
                            </p>
                            <p class="mt-3 text-xs text-ink">Masukkan nomor resi ini di situs {{ $order->shipping_courier }} untuk melihat posisi paket.</p>
                        </div>
                    @endif

                    <div class="bg-panel rounded-3xl border border-gray-100 p-6 shadow-sm">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">Pelacakan Pesanan</h2>

                        @if ($order->status === 'cancelled')
                            <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                                Pesanan ini telah dibatalkan.
                            </div>
                        @else
                            <div class="space-y-4">
                                @foreach ($timeline as $step)
                                    <div class="flex items-start gap-4">
                                        <div class="mt-1 h-3 w-3 rounded-full {{ $step['done'] || $step['current'] ? 'bg-beige' : 'bg-gray-300' }}"></div>
                                        <div>
                                            <p class="font-semibold {{ $step['current'] ? 'text-gray-900' : 'text-ink' }}">{{ $step['label'] }}</p>
                                            <p class="text-sm text-ink">
                                                @if ($step['current'])
                                                    Status saat ini
                                                @elseif ($step['done'])
                                                    Selesai
                                                @else
                                                    Belum diproses
                                                @endif
                                            </p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <div class="bg-panel rounded-3xl border border-gray-100 p-6 shadow-sm">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">Alamat Pengiriman</h2>
                        <p class="text-sm text-ink">{{ $order->customer_name }}</p>
                        <p class="mt-1 text-sm text-ink">{{ $order->customer_phone }}</p>
                        <p class="mt-1 text-sm text-ink">{{ $order->address }}</p>
                        <p class="mt-1 text-sm text-ink">{{ $order->city }}</p>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="bg-panel rounded-3xl border border-gray-100 p-6 shadow-sm">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">Ringkasan Pembayaran</h2>
                        <div class="space-y-3 text-sm text-gray-700">
                            @foreach ($order->items as $item)
                                <div class="flex justify-between gap-4">
                                    <div>
                                        <p class="font-semibold text-gray-900">{{ $item->product_name }}</p>
                                        @if ($item->selected_size || $item->selected_color)
                                            <p class="text-xs text-ink">
                                                {{ $item->selected_size }}{{ $item->selected_size && $item->selected_color ? ' · ' : '' }}{{ $item->selected_color }}
                                            </p>
                                        @endif
                                        <p class="text-ink">{{ $item->quantity }} x Rp{{ number_format($item->product_price, 0, ',', '.') }}</p>
                                    </div>
                                    <p class="font-semibold text-gray-900">Rp{{ number_format($item->subtotal, 0, ',', '.') }}</p>
                                </div>
                            @endforeach
                        </div>
                        <div class="mt-6 border-t border-gray-200 pt-4 text-sm text-gray-700 space-y-2">
                            <div class="flex justify-between">
                                <span>Subtotal</span>
                                <span>Rp{{ number_format($order->subtotal, 0, ',', '.') }}</span>
                            </div>
                            @if ($order->discount > 0)
                                <div class="flex justify-between text-green-700">
                                    <span>Diskon {{ $order->coupon_code ? '('.$order->coupon_code.')' : '' }}</span>
                                    <span>−Rp{{ number_format($order->discount, 0, ',', '.') }}</span>
                                </div>
                            @endif
                            <div class="flex justify-between">
                                <span>Ongkir</span>
                                <span>Rp{{ number_format($order->shipping_cost, 0, ',', '.') }}</span>
                            </div>
                            <div class="flex justify-between font-semibold text-gray-900 border-t border-gray-200 pt-3">
                                <span>Total</span>
                                <span>{{ $order->formatted_total }}</span>
                            </div>
                        </div>
                    </div>
                    <a wire:navigate href="{{ route('home') }}"
                       class="inline-flex w-full items-center justify-center rounded-full !bg-deep px-6 py-3 text-sm font-semibold !text-cream hover:!bg-deep-dark transition-colors"
                       style="background-color: var(--theme-deep); color: var(--theme-cream);">
                        Kembali ke Beranda
                    </a>
                </div>
            </div>
        @endif
    </section>
</div>
