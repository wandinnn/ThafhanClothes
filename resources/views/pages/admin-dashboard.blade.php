<?php

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use Livewire\Component;

new class extends Component {
    public int $totalProducts = 0;

    public int $totalCategories = 0;

    public int $totalBestSellers = 0;

    public int $totalNewArrivals = 0;

    public int $pendingPayment = 0;

    public int $paymentUploaded = 0;

    public int $processingOrders = 0;

    public int $lowStock = 0;

    public int $revenueMonth = 0;

    public array $productsPerCategory = [];

    public array $recentOrders = [];

    /** @var array<int, array{name: string, label: string, stock: int}> */
    public array $lowStockItems = [];

    private const LOW_STOCK_THRESHOLD = 5;

    public function mount(): void
    {
        $this->totalProducts = Product::count();
        $this->totalCategories = Category::count();
        $this->totalBestSellers = Product::where('is_best_seller', true)->count();
        $this->totalNewArrivals = Product::where('is_new_arrival', true)->count();
        $this->pendingPayment = Order::where('status', 'pending_payment')->count();
        $this->paymentUploaded = Order::where('status', 'payment_uploaded')->count();
        $this->processingOrders = Order::whereIn('status', ['confirmed', 'processing', 'shipped'])->count();
        $this->lowStockItems = $this->collectLowStock();
        $this->lowStock = count($this->lowStockItems);
        $this->revenueMonth = (int) Order::where('status', '!=', 'cancelled')
            ->where('created_at', '>=', now()->startOfMonth())
            ->sum('total');

        $this->productsPerCategory = Category::withCount('products')
            ->get()
            ->map(fn ($c) => ['name' => $c->name, 'count' => $c->products_count])
            ->toArray();

        $this->recentOrders = Order::orderByDesc('created_at')
            ->take(5)
            ->get(['id', 'order_number', 'customer_name', 'total', 'status', 'created_at'])
            ->map(fn (Order $order) => [
                'order_number' => $order->order_number,
                'customer_name' => $order->customer_name,
                'total' => $order->formatted_total,
                'status' => $order->status_label,
                'status_color' => $order->status_color,
                'created_at' => $order->created_at->format('d M Y H:i'),
            ])
            ->toArray();
    }

    /**
     * Gabungkan stok tipis dari varian dan dari produk yang belum punya varian.
     *
     * @return array<int, array{name: string, label: string, stock: int}>
     */
    private function collectLowStock(): array
    {
        $variants = ProductVariant::with('product:id,name')
            ->where('stock', '<=', self::LOW_STOCK_THRESHOLD)
            ->get()
            ->map(fn (ProductVariant $variant): array => [
                'name' => $variant->product?->name ?? 'Produk terhapus',
                'label' => $variant->label,
                'stock' => (int) $variant->stock,
            ]);

        $singles = Product::doesntHave('variants')
            ->where('stock', '<=', self::LOW_STOCK_THRESHOLD)
            ->get(['id', 'name', 'stock'])
            ->map(fn (Product $product): array => [
                'name' => $product->name,
                'label' => 'Stok tunggal',
                'stock' => (int) $product->stock,
            ]);

        return $variants->concat($singles)
            ->sortBy('stock')
            ->values()
            ->all();
    }

    public function render()
    {
        return view('pages.admin-dashboard')
            ->layout('layouts.admin')
            ->title('Dashboard');
    }
};
?>

<div class="space-y-6 animate-fade-in">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>
        <p class="mt-1 text-sm text-ink">Ringkasan data toko dan pesanan</p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
            <p class="text-sm font-medium text-ink">Menunggu Pembayaran</p>
            <p class="mt-1 text-3xl font-bold text-gray-900">{{ $pendingPayment }}</p>
        </div>
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
            <p class="text-sm font-medium text-ink">Bukti Dikirim</p>
            <p class="mt-1 text-3xl font-bold text-gray-900">{{ $paymentUploaded }}</p>
        </div>
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
            <p class="text-sm font-medium text-ink">Sedang Diproses</p>
            <p class="mt-1 text-3xl font-bold text-gray-900">{{ $processingOrders }}</p>
        </div>
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
            <p class="text-sm font-medium text-ink">Omzet Bulan Ini</p>
            <p class="mt-1 text-2xl font-bold text-gray-900">Rp{{ number_format($revenueMonth, 0, ',', '.') }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
            <p class="text-sm font-medium text-ink">Total Produk</p>
            <p class="mt-1 text-3xl font-bold text-gray-900">{{ $totalProducts }}</p>
        </div>
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
            <p class="text-sm font-medium text-ink">Total Kategori</p>
            <p class="mt-1 text-3xl font-bold text-gray-900">{{ $totalCategories }}</p>
        </div>
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
            <p class="text-sm font-medium text-ink">Best Seller</p>
            <p class="mt-1 text-3xl font-bold text-gray-900">{{ $totalBestSellers }}</p>
        </div>
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
            <p class="text-sm font-medium text-ink">Stok Menipis (≤5)</p>
            <p class="mt-1 text-3xl font-bold {{ $lowStock > 0 ? 'text-amber-600' : 'text-gray-900' }}">{{ $lowStock }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-gray-900">Pesanan Terbaru</h2>
                <a wire:navigate href="{{ route('admin.orders') }}" class="text-sm font-semibold text-deep hover:underline">Lihat semua</a>
            </div>
            <div class="space-y-3">
                @forelse($recentOrders as $order)
                    <div class="flex items-center justify-between gap-3 border-b border-gray-50 pb-3 last:border-0 last:pb-0">
                        <div>
                            <p class="font-semibold text-gray-900">{{ $order['order_number'] }}</p>
                            <p class="text-xs text-ink">{{ $order['customer_name'] }} · {{ $order['created_at'] }}</p>
                        </div>
                        <div class="text-right">
                            <p class="font-semibold text-gray-900">{{ $order['total'] }}</p>
                            <span class="inline-flex mt-1 rounded-full border px-2 py-0.5 text-[11px] font-semibold {{ $order['status_color'] }}">
                                {{ $order['status'] }}
                            </span>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-ink">Belum ada pesanan.</p>
                @endforelse
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-gray-900">Stok Menipis</h2>
                <a wire:navigate href="{{ route('admin.products') }}" class="text-sm font-semibold text-deep hover:underline">Kelola produk</a>
            </div>
            @if($lowStockItems === [])
                <p class="text-sm text-ink">Semua stok masih aman.</p>
            @else
                <div class="max-h-72 space-y-2 overflow-y-auto pr-1">
                    @foreach($lowStockItems as $item)
                        <div class="flex items-center justify-between gap-3 rounded-xl border border-gray-50 px-3 py-2">
                            <div class="min-w-0">
                                <p class="truncate font-medium text-gray-900">{{ $item['name'] }}</p>
                                <p class="text-xs text-ink">{{ $item['label'] }}</p>
                            </div>
                            <span class="shrink-0 rounded-full px-2.5 py-1 text-xs font-bold {{ $item['stock'] <= 0 ? 'bg-red-50 text-red-700' : 'bg-amber-50 text-amber-700' }}">
                                {{ $item['stock'] <= 0 ? 'Habis' : $item['stock'].' tersisa' }}
                            </span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Produk per Kategori</h2>
            <div class="space-y-3">
                @foreach($productsPerCategory as $item)
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-700">{{ $item['name'] }}</span>
                        <div class="flex items-center gap-3">
                            <div class="w-32 sm:w-48 h-2 bg-gray-100 rounded-full overflow-hidden">
                                <div class="h-full bg-primary rounded-full transition-all duration-500"
                                     style="width: {{ $totalProducts > 0 ? ($item['count'] / $totalProducts) * 100 : 0 }}%"></div>
                            </div>
                            <span class="text-sm font-semibold text-gray-900 min-w-[2ch] text-right">{{ $item['count'] }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
