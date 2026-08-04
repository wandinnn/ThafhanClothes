<?php

use App\Models\Order;
use App\Support\OrderAccess;
use Livewire\Component;

new class extends Component {
    public string $orderCode = '';

    public string $phoneTail = '';

    public ?string $message = null;

    public function goToOrder(): mixed
    {
        $code = strtoupper(trim($this->orderCode));
        $phoneTail = trim($this->phoneTail);

        if ($code === '') {
            $this->message = 'Masukkan kode pesanan terlebih dahulu.';

            return null;
        }

        if ($phoneTail === '' || strlen(preg_replace('/\D+/', '', $phoneTail) ?? '') < 4) {
            $this->message = 'Masukkan 4 digit terakhir nomor telepon yang dipakai saat checkout.';

            return null;
        }

        $order = Order::where('order_number', $code)->first();

        if (! $order) {
            $this->message = 'Kode pesanan tidak ditemukan. Pastikan Anda memasukkan dengan benar.';

            return null;
        }

        if (! OrderAccess::matchesPhone($order, $phoneTail)) {
            $this->message = 'Nomor telepon tidak cocok dengan pesanan ini.';

            return null;
        }

        OrderAccess::grant($order->order_number);

        return redirect()->route('order.detail', ['order' => $order->order_number]);
    }

    public function render()
    {
        return view('pages.track-order');
    }
};
?>

<div class="animate-fade-in">
    <section class="bg-teal py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h1 class="text-3xl font-bold text-ink">Lacak Pesanan</h1>
            <div class="gold-line mt-2 mb-0"></div>
        </div>
    </section>

    <section class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div class="bg-panel rounded-3xl shadow-sm border border-gray-100 p-8">
            <h2 class="text-xl font-semibold text-gray-900">Masukkan Kode Pesanan Anda</h2>
            <p class="mt-2 text-sm text-ink">Untuk melindungi data Anda, kami juga meminta 4 digit terakhir nomor telepon yang dipakai saat checkout.</p>

            @if($message)
                <div class="mt-5 rounded-3xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    {{ $message }}
                </div>
            @endif

            <form wire:submit.prevent="goToOrder" class="mt-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Kode pesanan</label>
                    <input wire:model.defer="orderCode" type="text" placeholder="Contoh: ABC1234567"
                           class="w-full rounded-3xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-900 outline-none focus:border-beige focus:ring-beige/20">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">4 digit terakhir nomor telepon</label>
                    <input wire:model.defer="phoneTail" type="text" inputmode="numeric" maxlength="4" placeholder="Contoh: 5060"
                           class="w-full rounded-3xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-900 outline-none focus:border-beige focus:ring-beige/20">
                </div>
                <button type="submit"
                        class="inline-flex items-center justify-center rounded-full bg-beige px-6 py-3 text-sm font-semibold text-deep hover:bg-coral transition-all duration-200">
                    Cari Pesanan
                </button>
            </form>

            <div class="mt-8 rounded-3xl border border-deep/10 bg-panel p-6">
                <h3 class="font-semibold text-gray-900">Tips</h3>
                <ul class="mt-3 space-y-2 text-sm text-ink">
                    <li>1. Kode pesanan tidak membedakan huruf besar/kecil.</li>
                    <li>2. Gunakan nomor telepon yang sama dengan data checkout.</li>
                    <li>3. Bila tidak ditemukan, hubungi customer service ThafhanClothes.</li>
                </ul>
            </div>
        </div>
    </section>
</div>
