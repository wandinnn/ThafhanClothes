<?php
use Livewire\Component;

new class extends Component {
    public string $orderCode = '';
    public ?string $message = null;

    public function goToOrder()
    {
        if (! $this->orderCode) {
            $this->message = 'Masukkan kode pesanan terlebih dahulu.';
            return;
        }

        if (session()->has("orders.$this->orderCode")) {
            return redirect()->route('order.detail', ['order' => $this->orderCode]);
        }

        $this->message = 'Kode pesanan tidak ditemukan. Pastikan Anda memasukkan dengan benar.';
    }

    public function render()
    {
        return view('pages.⚡track-order');
    }
};
?>

<div class="animate-fade-in">
    <section class="bg-[#8BDFDD] py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h1 class="text-3xl font-bold text-[#4F252E]">Lacak Pesanan</h1>
            <div class="gold-line mt-2 mb-0"></div>
        </div>
    </section>

    <section class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-8">
            <h2 class="text-xl font-semibold text-gray-900">Masukkan Kode Pesanan Anda</h2>
            <p class="mt-2 text-sm text-[#4F252E]">Temukan status pesanan Anda dengan cepat menggunakan kode pesanan yang Anda terima setelah checkout.</p>

            @if($message)
                <div class="mt-5 rounded-3xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    {{ $message }}
                </div>
            @endif

            <form wire:submit.prevent="goToOrder" class="mt-6 flex flex-col gap-4 sm:flex-row sm:items-center">
                <input wire:model.defer="orderCode" type="text" placeholder="Kode pesanan"
                       class="w-full rounded-3xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-900 outline-none focus:border-[#FFF6DE] focus:ring-[#FFF6DE]/20">
                <button type="submit"
                        class="inline-flex items-center justify-center rounded-full bg-[#FFF6DE] px-6 py-3 text-sm font-semibold text-[#1F2A2A] hover:bg-[#F48F68] transition-all duration-200">
                    Cari Pesanan
                </button>
            </form>

            <div class="mt-8 rounded-3xl border border-gray-100 bg-[#f8faff] p-6">
                <h3 class="font-semibold text-gray-900">Tips</h3>
                <ul class="mt-3 space-y-2 text-sm text-[#4F252E]">
                    <li>1. Pastikan kode pesanan sesuai dengan yang dikirim melalui notifikasi.</li>
                    <li>2. Kode pesanan bersifat sensitif huruf besar/kecil.</li>
                    <li>3. Bila tidak ditemukan, coba kembali setelah beberapa menit atau hubungi customer service.</li>
                </ul>
            </div>
        </div>
    </section>
</div>



