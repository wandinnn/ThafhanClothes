<?php

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Product;
use App\Models\Category;

new class extends Component {
    use WithPagination;

    public string $search = '';

    public $editingProduct = null;

    public string $name = '';

    public string $image_url = '';

    public int $price = 0;

    public string $description = '';

    public string $category_id = '';

    public bool $is_best_seller = false;

    public bool $is_new_arrival = false;

    public bool $showForm = false;

    public bool $confirmingDelete = false;

    public $deletingProduct = null;

    protected function rules(): array
    {
        return [
            'name' => ['required', 'min:3', 'max:255'],
            'image_url' => ['required', 'url'],
            'price' => ['required', 'integer', 'min:0'],
            'description' => ['required', 'min:10'],
            'category_id' => ['required', 'exists:categories,id'],
            'is_best_seller' => ['boolean'],
            'is_new_arrival' => ['boolean'],
        ];
    }

    protected $messages = [
        'name.required' => 'Nama produk wajib diisi.',
        'name.min' => 'Nama produk minimal 3 karakter.',
        'image_url.required' => 'URL gambar wajib diisi.',
        'image_url.url' => 'URL gambar tidak valid.',
        'price.required' => 'Harga wajib diisi.',
        'price.integer' => 'Harga harus berupa angka.',
        'price.min' => 'Harga tidak boleh negatif.',
        'description.required' => 'Deskripsi wajib diisi.',
        'description.min' => 'Deskripsi minimal 10 karakter.',
        'category_id.required' => 'Kategori wajib dipilih.',
        'category_id.exists' => 'Kategori tidak ditemukan.',
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function create(): void
    {
        $this->resetForm();
        $this->showForm = true;
        $this->editingProduct = null;
    }

    public function edit(string $slug): void
    {
        $product = Product::where('slug', $slug)->firstOrFail();
        $this->resetForm();
        $this->editingProduct = $product->id;
        $this->name = $product->name;
        $this->image_url = $product->image_url;
        $this->price = $product->price;
        $this->description = $product->description;
        $this->category_id = (string) $product->category_id;
        $this->is_best_seller = $product->is_best_seller;
        $this->is_new_arrival = $product->is_new_arrival;
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'name' => $this->name,
            'slug' => \Illuminate\Support\Str::slug($this->name),
            'image_url' => $this->image_url,
            'price' => $this->price,
            'description' => $this->description,
            'category_id' => $this->category_id,
            'is_best_seller' => $this->is_best_seller,
            'is_new_arrival' => $this->is_new_arrival,
        ];

        if ($this->editingProduct) {
            Product::findOrFail($this->editingProduct)->update($data);
            $this->dispatch('notify', message: 'Produk berhasil diperbarui.', type: 'success');
        } else {
            Product::create($data);
            $this->dispatch('notify', message: 'Produk berhasil ditambahkan.', type: 'success');
        }

        $this->showForm = false;
        $this->resetForm();
    }

    public function confirmDelete(string $slug): void
    {
        $this->deletingProduct = $slug;
        $this->confirmingDelete = true;
    }

    public function delete(): void
    {
        if ($this->deletingProduct) {
            $product = Product::where('slug', $this->deletingProduct)->firstOrFail();
            $product->delete();
            $this->dispatch('notify', message: 'Produk berhasil dihapus.', type: 'success');
            $this->confirmingDelete = false;
            $this->deletingProduct = null;
        }
    }

    public function cancelDelete(): void
    {
        $this->confirmingDelete = false;
        $this->deletingProduct = null;
    }

    public function cancel(): void
    {
        $this->showForm = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->name = '';
        $this->image_url = '';
        $this->price = 0;
        $this->description = '';
        $this->category_id = '';
        $this->is_best_seller = false;
        $this->is_new_arrival = false;
        $this->resetErrorBag();
    }

    public function render()
    {
        $products = Product::with('category')
            ->where(function ($q) {
                $q->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('description', 'like', '%'.$this->search.'%');
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        $categories = Category::orderBy('name')->get();

        return view('pages.⚡admin-products', compact('products', 'categories'))
            ->layout('layouts.admin')
            ->title('Produk');
    }
};
?>

<div class="space-y-6 animate-fade-in">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Produk</h1>
            <p class="mt-1 text-sm text-[#4F252E]">Kelola produk toko</p>
        </div>
        @if(!$showForm)
            <button wire:click="create"
                    class="inline-flex items-center gap-2 bg-accent hover:bg-secondary text-[#4F252E] font-semibold px-5 py-2.5 rounded-xl transition-all duration-200 hover:-translate-y-0.5 active:translate-y-0 text-sm">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Tambah Produk
            </button>
        @endif
    </div>

    {{-- Form --}}
    @if($showForm)
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 animate-fade-up">
            <h2 class="text-lg font-semibold text-gray-900 mb-6">{{ $editingProduct ? 'Edit Produk' : 'Tambah Produk' }}</h2>
            <form wire:submit="save" class="space-y-5">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-1.5">Nama Produk</label>
                        <input wire:model="name" id="name" type="text"
                               class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition-all duration-200 text-sm @error('name') border-red-300 bg-red-50 @enderror"
                               placeholder="Nama produk">
                        @error('name') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="category_id" class="block text-sm font-medium text-gray-700 mb-1.5">Kategori</label>
                        <select wire:model="category_id" id="category_id"
                                class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition-all duration-200 text-sm @error('category_id') border-red-300 bg-red-50 @enderror">
                            <option value="">Pilih kategori</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                        @error('category_id') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="price" class="block text-sm font-medium text-gray-700 mb-1.5">Harga (Rp)</label>
                        <input wire:model="price" id="price" type="number" min="0"
                               class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition-all duration-200 text-sm @error('price') border-red-300 bg-red-50 @enderror"
                               placeholder="100000">
                        @error('price') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="image_url" class="block text-sm font-medium text-gray-700 mb-1.5">URL Gambar</label>
                        <input wire:model="image_url" id="image_url" type="url"
                               class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition-all duration-200 text-sm @error('image_url') border-red-300 bg-red-50 @enderror"
                               placeholder="https://example.com/image.jpg">
                        @error('image_url') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        @if($image_url)
                            <img src="{{ $image_url }}" class="mt-2 size-16 rounded-lg object-cover border border-gray-200">
                        @endif
                    </div>
                </div>
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-1.5">Deskripsi</label>
                    <textarea wire:model="description" id="description" rows="4"
                              class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition-all duration-200 text-sm @error('description') border-red-300 bg-red-50 @enderror"
                              placeholder="Deskripsi produk"></textarea>
                    @error('description') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <div class="flex flex-wrap gap-6">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" wire:model="is_best_seller"
                               class="size-4 rounded border-gray-300 text-accent focus:ring-accent/20">
                        <span class="text-sm text-gray-700">Best Seller</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" wire:model="is_new_arrival"
                               class="size-4 rounded border-gray-300 text-accent focus:ring-accent/20">
                        <span class="text-sm text-gray-700">New Arrival</span>
                    </label>
                </div>
                <div class="flex items-center gap-3 pt-2">
                    <button type="submit"
                            class="bg-accent hover:bg-secondary text-[#4F252E] font-semibold px-6 py-2.5 rounded-xl transition-all duration-200 hover:-translate-y-0.5 active:translate-y-0 text-sm">
                        {{ $editingProduct ? 'Simpan Perubahan' : 'Tambah Produk' }}
                    </button>
                    <button type="button" wire:click="cancel"
                            class="text-[#4F252E] hover:text-gray-700 font-medium px-6 py-2.5 rounded-xl border border-gray-200 hover:bg-gray-50 transition-all duration-200 text-sm">
                        Batal
                    </button>
                </div>
            </form>
        </div>
    @endif

    {{-- Search --}}
    <div class="relative">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-[#4F252E]">
            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
        </svg>
        <input wire:model.live.debounce.300ms="search" type="text" placeholder="Cari produk..."
               class="w-full max-w-xs pl-10 pr-4 py-2.5 rounded-xl border border-gray-200 focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition-all duration-200 text-sm bg-white">
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100">
                        <th class="text-left px-4 py-3 font-semibold text-[#4F252E]">Gambar</th>
                        <th class="text-left px-4 py-3 font-semibold text-[#4F252E]">Nama</th>
                        <th class="text-left px-4 py-3 font-semibold text-[#4F252E] hidden sm:table-cell">Kategori</th>
                        <th class="text-left px-4 py-3 font-semibold text-[#4F252E] hidden md:table-cell">Harga</th>
                        <th class="text-left px-4 py-3 font-semibold text-[#4F252E] hidden lg:table-cell">Label</th>
                        <th class="text-right px-4 py-3 font-semibold text-[#4F252E]">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($products as $product)
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-4 py-3">
                                <img src="{{ $product->image_url }}" alt="{{ $product->name }}"
                                     class="size-12 rounded-lg object-cover border border-gray-100"
                                     onerror="this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 24 24%22 fill=%22%23ddd%22><rect width=%2224%22 height=%2224%22/></svg>'">
                            </td>
                            <td class="px-4 py-3">
                                <p class="font-medium text-gray-900 line-clamp-1">{{ $product->name }}</p>
                            </td>
                            <td class="px-4 py-3 hidden sm:table-cell">
                                <span class="text-[#4F252E]">{{ $product->category->name }}</span>
                            </td>
                            <td class="px-4 py-3 hidden md:table-cell">
                                <span class="font-semibold text-gray-900">{{ $product->formatted_price }}</span>
                            </td>
                            <td class="px-4 py-3 hidden lg:table-cell">
                                <div class="flex gap-1.5">
                                    @if($product->is_best_seller)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-50 text-yellow-700">Best</span>
                                    @endif
                                    @if($product->is_new_arrival)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700">Baru</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <button wire:click="edit('{{ $product->slug }}')"
                                            class="p-2 text-[#4F252E] hover:text-accent hover:bg-accent/5 rounded-lg transition-all">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                        </svg>
                                    </button>
                                    <button wire:click="confirmDelete('{{ $product->slug }}')"
                                            class="p-2 text-[#4F252E] hover:text-red-500 hover:bg-red-50 rounded-lg transition-all">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center text-[#4F252E]">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-8 mx-auto mb-2 text-[#4F252E]">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" />
                                </svg>
                                <p class="text-sm">Belum ada produk.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($products->hasPages())
            <div class="px-4 py-3 border-t border-gray-100">
                {{ $products->links() }}
            </div>
        @endif
    </div>

    {{-- Delete Confirmation Modal --}}
    @if($confirmingDelete)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/30" wire:click="cancelDelete"></div>
            <div class="relative bg-white rounded-2xl shadow-xl p-6 max-w-sm w-full animate-fade-up">
                <div class="text-center">
                    <div class="mx-auto size-12 rounded-full bg-red-100 flex items-center justify-center mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6 text-red-500">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900">Hapus Produk</h3>
                    <p class="mt-2 text-sm text-[#4F252E]">Apakah Anda yakin ingin menghapus produk ini? Tindakan ini tidak dapat dibatalkan.</p>
                </div>
                <div class="flex items-center gap-3 mt-6">
                    <button wire:click="delete"
                            class="flex-1 bg-red-500 hover:bg-red-600 text-[#4F252E] font-semibold py-2.5 rounded-xl transition-all duration-200 text-sm">
                        Hapus
                    </button>
                    <button wire:click="cancelDelete"
                            class="flex-1 text-[#4F252E] hover:text-gray-700 font-medium py-2.5 rounded-xl border border-gray-200 hover:bg-gray-50 transition-all duration-200 text-sm">
                        Batal
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>

