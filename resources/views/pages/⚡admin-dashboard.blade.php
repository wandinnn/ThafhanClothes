<?php

use Livewire\Component;
use App\Models\Product;
use App\Models\Category;

new class extends Component {
    public int $totalProducts = 0;

    public int $totalCategories = 0;

    public int $totalBestSellers = 0;

    public int $totalNewArrivals = 0;

    public array $productsPerCategory = [];

    public function mount()
    {
        $this->totalProducts = Product::count();
        $this->totalCategories = Category::count();
        $this->totalBestSellers = Product::where('is_best_seller', true)->count();
        $this->totalNewArrivals = Product::where('is_new_arrival', true)->count();

        $this->productsPerCategory = Category::withCount('products')
            ->get()
            ->map(fn ($c) => ['name' => $c->name, 'count' => $c->products_count])
            ->toArray();
    }

    public function render()
    {
        return view('pages.⚡admin-dashboard')
            ->layout('layouts.admin')
            ->title('Dashboard');
    }
};
?>

<div class="space-y-6 animate-fade-in">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>
        <p class="mt-1 text-sm text-[#4F252E]">Ringkasan data toko</p>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 animate-fade-up stagger-1">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-[#4F252E]">Total Produk</p>
                    <p class="mt-1 text-3xl font-bold text-gray-900">{{ $totalProducts }}</p>
                </div>
                <div class="size-12 rounded-xl bg-primary/20 flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6 text-primary">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" />
                    </svg>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 animate-fade-up stagger-2">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-[#4F252E]">Total Kategori</p>
                    <p class="mt-1 text-3xl font-bold text-gray-900">{{ $totalCategories }}</p>
                </div>
                <div class="size-12 rounded-xl bg-secondary/20 flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6 text-secondary">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6Z" />
                    </svg>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 animate-fade-up stagger-3">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-[#4F252E]">Best Seller</p>
                    <p class="mt-1 text-3xl font-bold text-gray-900">{{ $totalBestSellers }}</p>
                </div>
                <div class="size-12 rounded-xl bg-yellow-50 flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6 text-yellow-500">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.362 5.214A8.252 8.252 0 0 1 12 21 8.25 8.25 0 0 1 6.038 7.048 8.287 8.287 0 0 0 9 9.6a8.983 8.983 0 0 0 3.361.653 8.983 8.983 0 0 0 3.361-.653 8.287 8.287 0 0 0 1.64-1.386Z" />
                    </svg>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 animate-fade-up stagger-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-[#4F252E]">New Arrival</p>
                    <p class="mt-1 text-3xl font-bold text-gray-900">{{ $totalNewArrivals }}</p>
                </div>
                <div class="size-12 rounded-xl bg-blue-50 flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6 text-blue-500">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                </div>
            </div>
        </div>
    </div>

    {{-- Products Per Category --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 animate-fade-up stagger-5">
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

    {{-- Quick Actions --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 animate-fade-up stagger-6">
        <a href="{{ route('admin.products') }}"
           class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-md transition-all duration-200 group">
            <div class="flex items-center gap-4">
                <div class="size-12 rounded-xl bg-primary/20 flex items-center justify-center group-hover:scale-110 transition-transform duration-200">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6 text-primary">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                </div>
                <div>
                    <h3 class="font-semibold text-gray-900">Tambah Produk</h3>
                    <p class="text-sm text-[#4F252E] mt-0.5">Kelola produk toko</p>
                </div>
            </div>
        </a>
        <a href="{{ route('admin.categories') }}"
           class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-md transition-all duration-200 group">
            <div class="flex items-center gap-4">
                <div class="size-12 rounded-xl bg-secondary/20 flex items-center justify-center group-hover:scale-110 transition-transform duration-200">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6 text-secondary">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                </div>
                <div>
                    <h3 class="font-semibold text-gray-900">Tambah Kategori</h3>
                    <p class="text-sm text-[#4F252E] mt-0.5">Kelola kategori produk</p>
                </div>
            </div>
        </a>
    </div>
</div>

