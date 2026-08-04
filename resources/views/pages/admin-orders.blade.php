<?php

use App\Exceptions\InsufficientStockException;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\OrderNotifier;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public string $filterStatus = '';

    public string $search = '';

    public ?int $viewingId = null;

    public string $shippingCourier = '';

    public string $trackingNumber = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilterStatus(): void
    {
        $this->resetPage();
    }

    public function viewOrder(int $id): void
    {
        $order = Order::find($id);

        if (! $order) {
            return;
        }

        $this->viewingId = $id;
        $this->shippingCourier = $order->shipping_courier ?? '';
        $this->trackingNumber = $order->tracking_number ?? '';
        $this->resetErrorBag();
    }

    public function closeDetail(): void
    {
        $this->viewingId = null;
        $this->shippingCourier = '';
        $this->trackingNumber = '';
        $this->resetErrorBag();
    }

    /**
     * Simpan kurir dan nomor resi, lalu kabari pembeli lewat email.
     */
    public function saveShipment(): void
    {
        $order = Order::with('items')->find($this->viewingId);

        if (! $order) {
            return;
        }

        $this->validate([
            'shippingCourier' => ['nullable', Rule::in(Order::COURIERS)],
            'trackingNumber' => ['nullable', 'string', 'max:100', 'regex:/^[A-Za-z0-9\-\/\. ]+$/'],
        ], [
            'shippingCourier.in' => 'Kurir tidak dikenal.',
            'trackingNumber.regex' => 'Nomor resi hanya boleh berisi huruf, angka, titik, garis, dan spasi.',
            'trackingNumber.max' => 'Nomor resi maksimal 100 karakter.',
        ]);

        $courier = $this->shippingCourier !== '' ? $this->shippingCourier : null;
        $tracking = trim($this->trackingNumber) !== '' ? trim($this->trackingNumber) : null;

        if ($tracking && ! $courier) {
            $this->addError('shippingCourier', 'Pilih kurir sebelum menyimpan nomor resi.');

            return;
        }

        $isNewShipment = $tracking !== null && $tracking !== $order->tracking_number;

        $order->update([
            'shipping_courier' => $courier,
            'tracking_number' => $tracking,
            'shipped_at' => $tracking ? ($order->shipped_at ?? now()) : null,
        ]);

        if ($isNewShipment) {
            $fresh = $order->fresh(['items']);
            $autoShipStatuses = ['payment_uploaded', 'confirmed', 'processing'];

            if (in_array($fresh->status, $autoShipStatuses, true)) {
                $fresh->update(['status' => 'shipped']);
                $fresh = $fresh->fresh(['items']);
            }

            app(OrderNotifier::class)->shipmentUpdated($fresh);
        }

        session()->flash('statusUpdated', $tracking
            ? 'Nomor resi tersimpan dan pembeli sudah dikabari lewat email.'
            : 'Info pengiriman dikosongkan.');
    }

    public function updateStatus(int $orderId, string $status): void
    {
        if (! array_key_exists($status, Order::STATUSES)) {
            session()->flash('statusError', 'Status pesanan tidak dikenal.');

            return;
        }

        $order = Order::with('items')->find($orderId);

        if (! $order || $order->status === $status) {
            return;
        }

        $previousStatus = $order->status;

        try {
            DB::transaction(function () use ($order, $status): void {
                if ($status === 'cancelled') {
                    $this->restockItems($order);
                } elseif ($order->status === 'cancelled') {
                    $this->reserveItems($order);
                }

                $order->update(['status' => $status]);
            });
        } catch (InsufficientStockException $e) {
            session()->flash('statusError', $e->getMessage());

            return;
        }

        app(OrderNotifier::class)->statusUpdated($order->fresh(['items']), $previousStatus);

        session()->flash('statusUpdated', 'Status pesanan diperbarui. Pembeli dikabari lewat email (jika ada alamat email).');
    }

    /**
     * Kembalikan stok yang sempat dipotong saat checkout ketika pesanan dibatalkan.
     */
    private function restockItems(Order $order): void
    {
        foreach ($order->items as $item) {
            if ($item->product_variant_id) {
                ProductVariant::whereKey($item->product_variant_id)->increment('stock', $item->quantity);

                continue;
            }

            if ($item->product_id) {
                Product::whereKey($item->product_id)->increment('stock', $item->quantity);
            }
        }

        $this->syncVariantProductStock($order);
    }

    /**
     * Potong ulang stok saat pesanan yang sudah dibatalkan diaktifkan kembali.
     *
     * @throws InsufficientStockException
     */
    private function reserveItems(Order $order): void
    {
        foreach ($order->items as $item) {
            if ($item->product_variant_id) {
                $claimed = ProductVariant::whereKey($item->product_variant_id)
                    ->where('stock', '>=', $item->quantity)
                    ->decrement('stock', $item->quantity);
            } elseif ($item->product_id) {
                $claimed = Product::whereKey($item->product_id)
                    ->where('stock', '>=', $item->quantity)
                    ->decrement('stock', $item->quantity);
            } else {
                continue;
            }

            if ($claimed === 0) {
                throw new InsufficientStockException("Stok {$item->product_name} tidak cukup untuk mengaktifkan kembali pesanan ini.");
            }
        }

        $this->syncVariantProductStock($order);
    }

    /**
     * Selaraskan cache `products.stock` setelah stok varian berubah.
     */
    private function syncVariantProductStock(Order $order): void
    {
        $productIds = $order->items
            ->filter(fn ($item): bool => (bool) $item->product_variant_id)
            ->pluck('product_id')
            ->filter()
            ->unique();

        Product::whereIn('id', $productIds)->get()->each->syncStockFromVariants();
    }

    public function render()
    {
        $orders = Order::with('items')
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('order_number', 'like', '%'.$this->search.'%')
                        ->orWhere('customer_name', 'like', '%'.$this->search.'%')
                        ->orWhere('customer_phone', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->filterStatus, fn ($q) => $q->where('status', $this->filterStatus))
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('pages.admin-orders', [
            'orders' => $orders,
            'totalOrders' => Order::count(),
            'viewingOrder' => $this->viewingId ? Order::with('items')->find($this->viewingId) : null,
        ])->layout('layouts.admin')->title('Pesanan');
    }
};
?>

<div class="animate-fade-in">
    <section class="bg-teal py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-ink">Kelola Pesanan</h1>
                <div class="gold-line mt-2"></div>
            </div>
            <span class="text-deep font-semibold text-sm">{{ $totalOrders }} total pesanan</span>
        </div>
    </section>

    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        @if(session('statusUpdated'))
            <div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl text-sm">{{ session('statusUpdated') }}</div>
        @endif

        @if(session('statusError'))
            <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm">{{ session('statusError') }}</div>
        @endif

        {{-- Filter bar --}}
        <div class="flex flex-col sm:flex-row gap-3 mb-5">
            <input wire:model.live.debounce.300ms="search" type="search" placeholder="Cari nama, nomor pesanan, telepon..."
                   class="flex-1 rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm outline-none focus:border-beige focus:ring-2 focus:ring-beige/20">
            <select wire:model.live="filterStatus"
                    class="rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm outline-none focus:border-beige">
                <option value="">Semua Status</option>
                @foreach(\App\Models\Order::STATUSES as $val => $label)
                    <option value="{{ $val }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>

        {{-- Detail panel --}}
        @if($viewingOrder)
            <div class="mb-6 bg-white rounded-2xl border border-deep/15 shadow-sm p-6">
                <div class="flex items-start justify-between mb-4">
                    <div>
                        <h2 class="text-lg font-bold text-gray-900">Pesanan #{{ strtoupper($viewingOrder->order_number) }}</h2>
                        <p class="text-xs text-ink mt-0.5">{{ $viewingOrder->created_at->format('d M Y H:i') }}</p>
                    </div>
                    <button wire:click="closeDetail" class="text-ink hover:text-ink text-xl">✕</button>
                </div>

                <div class="grid sm:grid-cols-2 gap-4 mb-4 text-sm">
                    <div><p class="text-ink text-xs">Nama</p><p class="font-semibold">{{ $viewingOrder->customer_name }}</p></div>
                    <div>
                        <p class="text-ink text-xs">Telepon</p>
                        <p class="font-semibold">{{ $viewingOrder->customer_phone }}</p>
                        <a href="https://wa.me/{{ \App\Support\ShopSettings::whatsappDigitsFromPhone($viewingOrder->customer_phone) }}?text={{ urlencode('Halo '.$viewingOrder->customer_name.', terkait pesanan '.$viewingOrder->order_number.' di ThafhanClothes.') }}"
                           target="_blank" rel="noopener noreferrer"
                           class="mt-2 inline-flex items-center gap-1.5 rounded-full bg-green-500 hover:bg-green-600 !text-white px-3 py-1.5 text-xs font-semibold transition-colors">
                            Chat WA Pembeli
                        </a>
                    </div>
                    <div><p class="text-ink text-xs">Kota</p><p class="font-semibold">{{ $viewingOrder->city }}</p></div>
                    <div><p class="text-ink text-xs">Alamat</p><p class="font-semibold">{{ $viewingOrder->address }}</p></div>
                </div>

                {{-- Items --}}
                <div class="border border-gray-100 rounded-xl overflow-hidden mb-4">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="text-left px-4 py-2.5 text-xs font-semibold text-ink uppercase">Produk</th>
                                <th class="text-right px-4 py-2.5 text-xs font-semibold text-ink uppercase">Harga</th>
                                <th class="text-right px-4 py-2.5 text-xs font-semibold text-ink uppercase">Qty</th>
                                <th class="text-right px-4 py-2.5 text-xs font-semibold text-ink uppercase">Total</th>
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
                                                    <p class="text-xs text-ink">
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
                                <td class="px-4 py-3 text-right font-bold text-deep">{{ $viewingOrder->formatted_total }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                {{-- Bukti bayar --}}
                @if($viewingOrder->payment_proof_path)
                    <div class="mb-4">
                        <p class="text-xs text-ink mb-2">Bukti Pembayaran</p>
                        <img src="{{ route('admin.payment-proof', $viewingOrder) }}"
                             alt="Bukti pembayaran"
                             class="w-48 max-h-64 rounded-xl border border-gray-200 shadow-sm object-cover">
                        <a wire:navigate href="{{ route('admin.payment-proof', $viewingOrder) }}" target="_blank" rel="noopener"
                           class="mt-2 inline-block text-sm font-semibold text-deep hover:underline">Buka gambar penuh</a>
                    </div>
                @endif

                {{-- Info pengiriman --}}
                <div class="mb-5 rounded-xl border border-gray-100 bg-gray-50/60 p-4">
                    <p class="text-xs text-ink mb-3 font-semibold uppercase tracking-wider">Info Pengiriman</p>
                    <form wire:submit="saveShipment" class="grid gap-3 sm:grid-cols-[minmax(0,1fr)_minmax(0,1.4fr)_auto] sm:items-start">
                        <div>
                            <select wire:model="shippingCourier"
                                    class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-sm outline-none focus:border-beige focus:ring-2 focus:ring-beige/20">
                                <option value="">Pilih kurir</option>
                                @foreach(\App\Models\Order::COURIERS as $courier)
                                    <option value="{{ $courier }}">{{ $courier }}</option>
                                @endforeach
                            </select>
                            @error('shippingCourier') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <input wire:model="trackingNumber" type="text" placeholder="Nomor resi, contoh JP1234567890"
                                   class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-sm outline-none focus:border-beige focus:ring-2 focus:ring-beige/20">
                            @error('trackingNumber') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <button type="submit"
                                class="rounded-xl bg-beige px-5 py-2.5 text-sm font-semibold text-deep transition-colors hover:brightness-95">
                            Simpan Resi
                        </button>
                    </form>

                    @if($viewingOrder->hasShipmentInfo())
                        <p class="mt-3 text-xs text-ink">
                            Dikirim via <span class="font-semibold text-gray-900">{{ $viewingOrder->shipping_courier }}</span>
                            · resi <span class="font-mono font-semibold text-gray-900">{{ $viewingOrder->tracking_number }}</span>
                            @if($viewingOrder->shipped_at) · {{ $viewingOrder->shipped_at->format('d M Y H:i') }} @endif
                        </p>
                    @elseif($viewingOrder->status === 'shipped')
                        <p class="mt-3 text-xs text-amber-700">Status sudah "Dalam Pengiriman" tetapi nomor resi belum diisi.</p>
                    @endif
                </div>

                {{-- Update status --}}
                <div>
                    <p class="text-xs text-ink mb-2 font-semibold uppercase tracking-wider">Update Status</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach(\App\Models\Order::STATUSES as $val => $label)
                            <button wire:click="updateStatus({{ $viewingOrder->id }}, '{{ $val }}')"
                                    class="text-xs font-semibold px-3 py-1.5 rounded-full border transition-colors
                                           {{ $viewingOrder->status === $val
                                              ? 'bg-beige text-deep border-beige'
                                              : 'bg-white text-ink border-gray-200 hover:border-beige hover:text-deep' }}">
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
                        <th class="text-left px-5 py-3.5 text-xs font-semibold text-ink uppercase">Nomor</th>
                        <th class="text-left px-5 py-3.5 text-xs font-semibold text-ink uppercase">Pelanggan</th>
                        <th class="text-left px-5 py-3.5 text-xs font-semibold text-ink uppercase hidden sm:table-cell">Tanggal</th>
                        <th class="text-right px-5 py-3.5 text-xs font-semibold text-ink uppercase hidden md:table-cell">Total</th>
                        <th class="text-center px-5 py-3.5 text-xs font-semibold text-ink uppercase">Status</th>
                        <th class="px-5 py-3.5"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($orders as $order)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-5 py-3.5 font-mono text-xs font-bold text-gray-700">{{ strtoupper($order->order_number) }}</td>
                            <td class="px-5 py-3.5">
                                <p class="font-semibold text-gray-900">{{ $order->customer_name }}</p>
                                <p class="text-xs text-ink">{{ $order->customer_phone }}</p>
                            </td>
                            <td class="px-5 py-3.5 text-ink hidden sm:table-cell text-xs">{{ $order->created_at->format('d M Y') }}</td>
                            <td class="px-5 py-3.5 text-right font-semibold text-gray-900 hidden md:table-cell">{{ $order->formatted_total }}</td>
                            <td class="px-5 py-3.5 text-center">
                                <span class="text-xs font-semibold px-2.5 py-1 rounded-full border {{ $order->status_color }}">
                                    {{ $order->status_label }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5">
                                <button wire:click="viewOrder({{ $order->id }})"
                                        class="text-xs font-semibold text-deep hover:text-coral border border-deep/15 hover:border-beige px-3 py-1.5 rounded-lg transition-colors">
                                    Detail
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-10 text-center text-ink">Belum ada pesanan.</td></tr>
                    @endforelse
                </tbody>
            </table>
            <div class="px-5 py-4 border-t border-gray-100">{{ $orders->links() }}</div>
        </div>
    </section>
</div>



