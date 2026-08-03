<?php

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Order;

new class extends Component {
    use WithPagination;

    public string $filterStatus = '';
    public string $search       = '';
    public ?int   $viewingId    = null;
    public ?Order $viewingOrder = null;

    public function updatedSearch(): void    { $this->resetPage(); }
    public function updatedFilterStatus(): void { $this->resetPage(); }

    public function viewOrder(int $id): void
    {
        $this->viewingOrder = Order::with('items')->find($id);
        $this->viewingId    = $id;
    }

    public function closeDetail(): void
    {
        $this->viewingId    = null;
        $this->viewingOrder = null;
    }

    public function updateStatus(int $orderId, string $status): void
    {
        Order::where('id', $orderId)->update(['status' => $status]);
        if ($this->viewingOrder && $this->viewingOrder->id === $orderId) {
            $this->viewingOrder->refresh();
        }
        session()->flash('statusUpdated', 'Status pesanan diperbarui.');
    }

    public function render()
    {
        $orders = Order::with('items')
            ->when($this->search, fn ($q) => $q->where('order_number', 'like', '%'.$this->search.'%')
                ->orWhere('customer_name', 'like', '%'.$this->search.'%')
                ->orWhere('customer_phone', 'like', '%'.$this->search.'%'))
            ->when($this->filterStatus, fn ($q) => $q->where('status', $this->filterStatus))
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('pages.⚡admin-orders', ['orders' => $orders])
            ->layout('layouts.app');
    }
};
?>

<div class="animate-fade-in">
    <section class="bg-[#8BDFDD] py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-[#4F252E]">Kelola Pesanan</h1>
                <div class="gold-line mt-2"></div>
            </div>
            <span class="text-[#1F2A2A] font-semibold text-sm">{{ Order::count() }} total pesanan</span>
        </div>
    </section>

    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        @if(session('statusUpdated'))
            <div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl text-sm">{{ session('statusUpdated') }}</div>
        @endif

        {{-- Filter bar --}}
        <div class="flex flex-col sm:flex-row gap-3 mb-5">
            <input wire:model.live.debounce.300ms="search" type="search" placeholder="Cari nama, nomor pesanan, telepon..."
                   class="flex-1 rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm outline-none focus:border-[#FFF6DE] focus:ring-2 focus:ring-[#FFF6DE]/20">
            <select wire:model.live="filterStatus"
                    class="rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm outline-none focus:border-[#FFF6DE]">
                <option value="">Semua Status</option>
                @foreach(\App\Models\Order::STATUSES as $val => $label)
                    <option value="{{ $val }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>

        {{-- Detail panel --}}
        @if($viewingOrder)
            <div class="mb-6 bg-white rounded-2xl border border-[#FFF6DE]/30 shadow-sm p-6">
                <div class="flex items-start justify-between mb-4">
                    <div>
                        <h2 class="text-lg font-bold text-gray-900">Pesanan #{{ strtoupper($viewingOrder->order_number) }}</h2>
                        <p class="text-xs text-[#4F252E] mt-0.5">{{ $viewingOrder->created_at->format('d M Y H:i') }}</p>
                    </div>
                    <button wire:click="closeDetail" class="text-[#4F252E] hover:text-[#4F252E] text-xl">✕</button>
                </div>

                <div class="grid sm:grid-cols-2 gap-4 mb-4 text-sm">
                    <div><p class="text-[#4F252E] text-xs">Nama</p><p class="font-semibold">{{ $viewingOrder->customer_name }}</p></div>
                    <div><p class="text-[#4F252E] text-xs">Telepon</p><p class="font-semibold">{{ $viewingOrder->customer_phone }}</p></div>
                    <div><p class="text-[#4F252E] text-xs">Kota</p><p class="font-semibold">{{ $viewingOrder->city }}</p></div>
                    <div><p class="text-[#4F252E] text-xs">Alamat</p><p class="font-semibold">{{ $viewingOrder->address }}</p></div>
                </div>

                {{-- Items --}}
                <div class="border border-gray-100 rounded-xl overflow-hidden mb-4">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="text-left px-4 py-2.5 text-xs font-semibold text-[#4F252E] uppercase">Produk</th>
                                <th class="text-right px-4 py-2.5 text-xs font-semibold text-[#4F252E] uppercase">Harga</th>
                                <th class="text-right px-4 py-2.5 text-xs font-semibold text-[#4F252E] uppercase">Qty</th>
                                <th class="text-right px-4 py-2.5 text-xs font-semibold text-[#4F252E] uppercase">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @foreach($viewingOrder->items as $item)
                                <tr>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-3">
                                            @if($item->product_image)
                                                <img src="{{ $item->product_image }}" class="w-9 h-9 rounded-lg object-cover border border-gray-100">
                                            @endif
                                            <div>
                                                <p class="font-medium text-gray-900">{{ $item->product_name }}</p>
                                                @if($item->selected_size || $item->selected_color)
                                                    <p class="text-xs text-[#4F252E]">
                                                        {{ $item->selected_size }} {{ $item->selected_color ? '· '.$item->selected_color : '' }}
                                                    </p>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-right text-gray-700">Rp{{ number_format($item->product_price,0,',','.') }}</td>
                                    <td class="px-4 py-3 text-right text-gray-700">{{ $item->quantity }}</td>
                                    <td class="px-4 py-3 text-right font-semibold text-gray-900">Rp{{ number_format($item->subtotal,0,',','.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-gray-50 border-t border-gray-200">
                            <tr>
                                <td colspan="3" class="px-4 py-3 text-right font-bold text-gray-900">Total</td>
                                <td class="px-4 py-3 text-right font-bold text-[#1F2A2A]">{{ $viewingOrder->formatted_total }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                {{-- Bukti bayar --}}
                @if($viewingOrder->payment_proof_path)
                    <div class="mb-4">
                        <p class="text-xs text-[#4F252E] mb-2">Bukti Pembayaran</p>
                        <img src="{{ asset('storage/' . $viewingOrder->payment_proof_path) }}"
                             class="w-48 rounded-xl border border-gray-200 shadow-sm">
                    </div>
                @endif

                {{-- Update status --}}
                <div>
                    <p class="text-xs text-[#4F252E] mb-2 font-semibold uppercase tracking-wider">Update Status</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach(\App\Models\Order::STATUSES as $val => $label)
                            <button wire:click="updateStatus({{ $viewingOrder->id }}, '{{ $val }}')"
                                    class="text-xs font-semibold px-3 py-1.5 rounded-full border transition-colors
                                           {{ $viewingOrder->status === $val
                                              ? 'bg-[#FFF6DE] text-[#1F2A2A] border-[#FFF6DE]'
                                              : 'bg-white text-[#4F252E] border-gray-200 hover:border-[#FFF6DE] hover:text-[#1F2A2A]' }}">
                                {{ $label }}
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        {{-- Tabel pesanan --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-100">
                    <tr>
                        <th class="text-left px-5 py-3.5 text-xs font-semibold text-[#4F252E] uppercase">Nomor</th>
                        <th class="text-left px-5 py-3.5 text-xs font-semibold text-[#4F252E] uppercase">Pelanggan</th>
                        <th class="text-left px-5 py-3.5 text-xs font-semibold text-[#4F252E] uppercase hidden sm:table-cell">Tanggal</th>
                        <th class="text-right px-5 py-3.5 text-xs font-semibold text-[#4F252E] uppercase hidden md:table-cell">Total</th>
                        <th class="text-center px-5 py-3.5 text-xs font-semibold text-[#4F252E] uppercase">Status</th>
                        <th class="px-5 py-3.5"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($orders as $order)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-5 py-3.5 font-mono text-xs font-bold text-gray-700">{{ strtoupper($order->order_number) }}</td>
                            <td class="px-5 py-3.5">
                                <p class="font-semibold text-gray-900">{{ $order->customer_name }}</p>
                                <p class="text-xs text-[#4F252E]">{{ $order->customer_phone }}</p>
                            </td>
                            <td class="px-5 py-3.5 text-[#4F252E] hidden sm:table-cell text-xs">{{ $order->created_at->format('d M Y') }}</td>
                            <td class="px-5 py-3.5 text-right font-semibold text-gray-900 hidden md:table-cell">{{ $order->formatted_total }}</td>
                            <td class="px-5 py-3.5 text-center">
                                <span class="text-xs font-semibold px-2.5 py-1 rounded-full border {{ $order->status_color }}">
                                    {{ $order->status_label }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5">
                                <button wire:click="viewOrder({{ $order->id }})"
                                        class="text-xs font-semibold text-[#1F2A2A] hover:text-[#F48F68] border border-[#FFF6DE]/30 hover:border-[#FFF6DE] px-3 py-1.5 rounded-lg transition-colors">
                                    Detail
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-10 text-center text-[#4F252E]">Belum ada pesanan.</td></tr>
                    @endforelse
                </tbody>
            </table>
            <div class="px-5 py-4 border-t border-gray-100">{{ $orders->links() }}</div>
        </div>
    </section>
</div>



