<?php

use App\Enums\ProductCondition;
use App\Models\Category;
use App\Models\Product;
use App\Support\ProductOptions;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

new class extends Component {
    use WithFileUploads, WithPagination;

    public string $search = '';

    public $editingProduct = null;

    public string $name = '';

    public string $image_url = '';

    public $imageFile = null;

    /** @var array<int, mixed> */
    public array $galleryFiles = [];

    /** @var list<array{id: int, url: string}> */
    public array $existingGallery = [];

    /** @var list<int> */
    public array $removedGalleryIds = [];

    /** @var array<int, array{size: string, color: string, stock: int, sku: string}> */
    public array $variantRows = [];

    public string $variantColorToAdd = '';

    public int $price = 0;

    public ?int $original_price = null;

    public int $stock = 0;

    public string $description = '';

    public string $category_id = '';

    public string $condition = 'second_like_new';

    public bool $is_best_seller = false;

    public bool $is_new_arrival = false;

    public bool $is_flash_sale = false;

    public bool $showForm = false;

    public bool $confirmingDelete = false;

    public $deletingProduct = null;

    protected function rules(): array
    {
        return [
            'name' => ['required', 'min:3', 'max:255'],
            'image_url' => [$this->imageFile ? 'nullable' : 'required', 'url'],
            'imageFile' => ['nullable', 'image', 'max:2048'],
            'galleryFiles' => ['array', 'max:'.(Product::MAX_GALLERY_IMAGES - 1)],
            'galleryFiles.*' => ['image', 'max:2048'],
            'price' => ['required', 'integer', 'min:0'],
            'original_price' => ['nullable', 'integer', 'gt:price'],
            'stock' => ['required', 'integer', 'min:0'],
            'description' => ['required', 'min:10'],
            'category_id' => ['required', 'exists:categories,id'],
            'condition' => ['required', 'in:'.implode(',', array_column(ProductCondition::cases(), 'value'))],
            'is_best_seller' => ['boolean'],
            'is_new_arrival' => ['boolean'],
            'is_flash_sale' => ['boolean'],
            'variantRows' => ['array'],
            'variantRows.*.size' => ['required', 'string', 'max:50'],
            'variantRows.*.color' => ['required', 'string', 'max:50'],
            'variantRows.*.stock' => ['required', 'integer', 'min:0'],
            'variantRows.*.sku' => ['nullable', 'string', 'max:64'],
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
        'original_price.gt' => 'Harga coret harus lebih besar dari harga jual.',
        'stock.required' => 'Stok wajib diisi.',
        'stock.min' => 'Stok tidak boleh negatif.',
        'description.required' => 'Deskripsi wajib diisi.',
        'description.min' => 'Deskripsi minimal 10 karakter.',
        'category_id.required' => 'Kategori wajib dipilih.',
        'category_id.exists' => 'Kategori tidak ditemukan.',
        'condition.required' => 'Kondisi barang wajib dipilih.',
        'condition.in' => 'Kondisi barang tidak valid.',
        'imageFile.image' => 'Berkas harus berupa gambar.',
        'imageFile.max' => 'Ukuran gambar maksimal 2 MB.',
        'galleryFiles.max' => 'Maksimal 5 gambar per produk (1 utama + 4 tambahan).',
        'galleryFiles.*.image' => 'Setiap foto gallery harus berupa gambar.',
        'galleryFiles.*.max' => 'Setiap foto gallery maksimal 2 MB.',
        'variantRows.*.size.required' => 'Ukuran varian wajib diisi.',
        'variantRows.*.color.required' => 'Warna varian wajib dipilih.',
        'variantRows.*.stock.required' => 'Stok varian wajib diisi.',
        'variantRows.*.stock.min' => 'Stok varian tidak boleh negatif.',
    ];

    /**
     * Jumlah foto tambahan yang masih boleh diunggah (total produk maks 5 termasuk utama).
     */
    public function getRemainingGallerySlotsProperty(): int
    {
        $kept = count($this->existingGallery);
        $incoming = count(array_filter($this->galleryFiles));

        return max(0, Product::MAX_GALLERY_IMAGES - 1 - $kept - $incoming);
    }

    /**
     * Total slot gambar terpakai (utama + gallery), untuk indikator 0–5 di form.
     */
    public function getUsedImageSlotsProperty(): int
    {
        $hasCover = filled($this->image_url) || $this->imageFile !== null;
        $gallery = count($this->existingGallery) + count(array_filter($this->galleryFiles));

        return ($hasCover ? 1 : 0) + $gallery;
    }

    public function updatedGalleryFiles(): void
    {
        $maxExtra = Product::MAX_GALLERY_IMAGES - 1;
        $allowed = max(0, $maxExtra - count($this->existingGallery));

        if (count($this->galleryFiles) <= $allowed) {
            return;
        }

        $this->galleryFiles = array_slice(array_values($this->galleryFiles), 0, $allowed);
        $this->addError('galleryFiles', 'Maksimal 5 gambar per produk (1 utama + 4 tambahan).');
    }

    /**
     * @return list<string>
     */
    public function getVariantColorOptionsProperty(): array
    {
        return ProductOptions::colors();
    }

    /**
     * Ukuran bawaan mengikuti kategori yang sedang dipilih di form.
     *
     * @return list<string>
     */
    public function getSuggestedSizesProperty(): array
    {
        $product = new Product(['name' => $this->name]);
        $product->setRelation('category', $this->category_id ? Category::find($this->category_id) : null);

        return ProductOptions::sizesFor($product);
    }

    public function getVariantStockTotalProperty(): int
    {
        return (int) collect($this->variantRows)->sum(fn (array $row): int => max(0, (int) ($row['stock'] ?? 0)));
    }

    public function addVariantRow(): void
    {
        $this->variantRows[] = ['size' => '', 'color' => '', 'stock' => 0, 'sku' => ''];
    }

    public function removeVariantRow(int $index): void
    {
        unset($this->variantRows[$index]);
        $this->variantRows = array_values($this->variantRows);
    }

    /**
     * Buat sekaligus satu baris untuk tiap ukuran kategori pada warna terpilih.
     */
    public function addSizesForColor(): void
    {
        $color = trim($this->variantColorToAdd);

        if ($color === '') {
            $this->addError('variantColorToAdd', 'Pilih warna terlebih dahulu.');

            return;
        }

        $existing = collect($this->variantRows)
            ->map(fn (array $row): string => trim((string) $row['size']).'|'.trim((string) $row['color']))
            ->all();

        foreach ($this->suggestedSizes as $size) {
            if (in_array($size.'|'.$color, $existing, true)) {
                continue;
            }

            $this->variantRows[] = ['size' => $size, 'color' => $color, 'stock' => 0, 'sku' => ''];
        }

        $this->variantColorToAdd = '';
        $this->resetErrorBag('variantColorToAdd');
    }

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
        $product = Product::with(['variants', 'images'])->where('slug', $slug)->firstOrFail();
        $this->resetForm();
        $this->editingProduct = $product->id;
        $this->variantRows = $product->variants
            ->sortBy(['color', 'size'])
            ->map(fn ($variant): array => [
                'size' => $variant->size,
                'color' => $variant->color,
                'stock' => (int) $variant->stock,
                'sku' => (string) ($variant->sku ?? ''),
            ])
            ->values()
            ->all();
        $this->existingGallery = $product->images
            ->map(fn ($image): array => [
                'id' => (int) $image->id,
                'url' => (string) $image->url,
            ])
            ->values()
            ->all();
        $this->name = $product->name;
        $this->image_url = $product->image_url;
        $this->price = $product->price;
        $this->original_price = $product->original_price;
        $this->stock = $product->stock;
        $this->description = $product->description;
        $this->category_id = (string) $product->category_id;
        $this->condition = $product->condition?->value ?? ProductCondition::SecondLikeNew->value;
        $this->is_best_seller = $product->is_best_seller;
        $this->is_new_arrival = $product->is_new_arrival;
        $this->is_flash_sale = $product->is_flash_sale;
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->validate();

        if (! $this->variantCombinationsAreUnique()) {
            $this->addError('variantRows', 'Ada kombinasi ukuran dan warna yang sama lebih dari sekali.');

            return;
        }

        if ($this->imageFile) {
            $this->image_url = Storage::url($this->imageFile->store('products', 'public'));
        }

        $keptGalleryCount = count(array_filter(
            $this->existingGallery,
            fn (array $image): bool => ! in_array($image['id'], $this->removedGalleryIds, true)
        ));
        $incomingGalleryCount = is_array($this->galleryFiles) ? count($this->galleryFiles) : 0;

        if ($keptGalleryCount + $incomingGalleryCount > Product::MAX_GALLERY_IMAGES - 1) {
            $this->addError('galleryFiles', 'Maksimal 5 gambar per produk (1 utama + 4 tambahan).');

            return;
        }

        $data = [
            'name' => $this->name,
            'slug' => $this->uniqueSlug($this->name, $this->editingProduct),
            'image_url' => $this->image_url,
            'price' => $this->price,
            'original_price' => $this->original_price,
            'stock' => $this->variantRows === [] ? $this->stock : $this->variantStockTotal,
            'description' => $this->description,
            'category_id' => $this->category_id,
            'condition' => $this->condition,
            'is_best_seller' => $this->is_best_seller,
            'is_new_arrival' => $this->is_new_arrival,
            'is_flash_sale' => $this->is_flash_sale,
        ];

        if ($this->editingProduct) {
            $product = Product::findOrFail($this->editingProduct);
            $product->update($data);
            $this->dispatch('notify', message: 'Produk berhasil diperbarui.', type: 'success');
        } else {
            $product = Product::create($data);
            $this->dispatch('notify', message: 'Produk berhasil ditambahkan.', type: 'success');
        }

        $this->syncVariants($product);
        $this->syncGallery($product);

        $this->showForm = false;
        $this->resetForm();
    }

    public function removeExistingGalleryImage(int $id): void
    {
        if (! in_array($id, $this->removedGalleryIds, true)) {
            $this->removedGalleryIds[] = $id;
        }

        $this->existingGallery = array_values(array_filter(
            $this->existingGallery,
            fn (array $image): bool => $image['id'] !== $id
        ));
    }

    private function variantCombinationsAreUnique(): bool
    {
        $combinations = collect($this->variantRows)
            ->map(fn (array $row): string => trim((string) $row['size']).'|'.trim((string) $row['color']));

        return $combinations->count() === $combinations->unique()->count();
    }

    /**
     * Simpan varian sesuai baris di form, hapus yang sudah tidak ada, lalu
     * selaraskan total stok produk.
     */
    private function syncVariants(Product $product): void
    {
        $keptIds = [];

        foreach ($this->variantRows as $row) {
            $size = trim((string) $row['size']);
            $color = trim((string) $row['color']);

            if ($size === '' || $color === '') {
                continue;
            }

            $variant = $product->variants()->updateOrCreate(
                ['size' => $size, 'color' => $color],
                [
                    'stock' => max(0, (int) $row['stock']),
                    'sku' => trim((string) ($row['sku'] ?? '')) ?: null,
                ],
            );

            $keptIds[] = $variant->id;
        }

        $product->variants()->whereNotIn('id', $keptIds)->delete();
        $product->refresh()->syncStockFromVariants();
    }

    /**
     * Simpan foto gallery tambahan (di luar gambar utama), sisa slot sampai total 5.
     */
    private function syncGallery(Product $product): void
    {
        if ($this->removedGalleryIds !== []) {
            $product->images()->whereIn('id', $this->removedGalleryIds)->delete();
        }

        $nextSort = (int) ($product->images()->max('sort_order') ?? -1) + 1;

        foreach ($this->galleryFiles as $file) {
            if ($file === null) {
                continue;
            }

            $product->images()->create([
                'url' => Storage::url($file->store('products', 'public')),
                'sort_order' => $nextSort++,
            ]);
        }
    }

    /**
     * Slug unik dengan sufiks angka, karena kolom `products.slug` punya unique index.
     */
    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = \Illuminate\Support\Str::slug($name) ?: 'produk';
        $slug = $base;
        $suffix = 2;

        while (Product::where('slug', $slug)
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }

    public function duplicate(string $slug): void
    {
        $product = Product::with(['variants', 'images'])->where('slug', $slug)->firstOrFail();

        $clone = $product->replicate();
        $clone->name = $product->name.' (Salinan)';
        $clone->slug = $this->uniqueSlug($clone->name);
        $clone->is_flash_sale = false;
        $clone->save();

        foreach ($product->variants as $variant) {
            $variantClone = $variant->replicate();
            $variantClone->product_id = $clone->id;
            $variantClone->save();
        }

        foreach ($product->images as $image) {
            $imageClone = $image->replicate();
            $imageClone->product_id = $clone->id;
            $imageClone->save();
        }

        $this->dispatch('notify', message: 'Produk berhasil digandakan.', type: 'success');
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
        $this->imageFile = null;
        $this->galleryFiles = [];
        $this->existingGallery = [];
        $this->removedGalleryIds = [];
        $this->variantRows = [];
        $this->variantColorToAdd = '';
        $this->price = 0;
        $this->original_price = null;
        $this->stock = 0;
        $this->description = '';
        $this->category_id = '';
        $this->condition = ProductCondition::SecondLikeNew->value;
        $this->is_best_seller = false;
        $this->is_new_arrival = false;
        $this->is_flash_sale = false;
        $this->resetErrorBag();
    }

    public function render()
    {
        $products = Product::with('category')
            ->withCount('variants')
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('description', 'like', '%'.$this->search.'%');
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        $categories = Category::orderBy('name')->get();

        return view('pages.admin-products', compact('products', 'categories'))
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
            <p class="mt-1 text-sm text-ink">Kelola produk toko</p>
        </div>
        @if(!$showForm)
            <button wire:click="create"
                    class="admin-btn px-5 py-2.5 text-sm">
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
                        <label for="original_price" class="block text-sm font-medium text-gray-700 mb-1.5">
                            Harga Coret (Rp) <span class="text-ink font-normal">— opsional</span>
                        </label>
                        <input wire:model="original_price" id="original_price" type="number" min="0"
                               class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition-all duration-200 text-sm @error('original_price') border-red-300 bg-red-50 @enderror"
                               placeholder="150000">
                        @error('original_price') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="stock" class="block text-sm font-medium text-gray-700 mb-1.5">Stok</label>
                        @if($variantRows === [])
                            <input wire:model="stock" id="stock" type="number" min="0"
                                   class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition-all duration-200 text-sm @error('stock') border-red-300 bg-red-50 @enderror"
                                   placeholder="100">
                            @error('stock') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        @else
                            <div class="w-full px-4 py-2.5 rounded-xl border border-dashed border-gray-200 bg-gray-50 text-sm text-gray-700">
                                {{ $this->variantStockTotal }} <span class="text-ink">— total dari {{ count($variantRows) }} varian</span>
                            </div>
                        @endif
                    </div>
                    <div class="lg:col-span-2 rounded-2xl border border-gray-100 bg-gray-50/60 p-4 space-y-4">
                        <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm font-semibold text-gray-900">Gambar Produk</p>
                                <p class="mt-0.5 text-xs text-ink">Maksimal {{ \App\Models\Product::MAX_GALLERY_IMAGES }} gambar (1 utama + {{ \App\Models\Product::MAX_GALLERY_IMAGES - 1 }} tambahan).</p>
                            </div>
                            <p class="text-xs font-semibold text-deep">
                                {{ $this->usedImageSlots }} / {{ \App\Models\Product::MAX_GALLERY_IMAGES }} terpakai
                            </p>
                        </div>

                        <div>
                            <label for="image_url" class="block text-sm font-medium text-gray-700 mb-1.5">Gambar utama</label>
                            <input wire:model="image_url" id="image_url" type="url"
                                   class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition-all duration-200 text-sm @error('image_url') border-red-300 bg-red-50 @enderror"
                                   placeholder="https://example.com/image.jpg">
                            @error('image_url') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror

                            <div class="mt-2 flex items-center gap-3">
                                <input wire:model="imageFile" id="imageFile" type="file" accept="image/*"
                                       class="block w-full text-xs text-ink file:mr-3 file:rounded-lg file:border-0 file:bg-accent/15 file:px-3 file:py-2 file:text-xs file:font-semibold file:text-gray-800 hover:file:bg-accent/25">
                                <div wire:loading wire:target="imageFile" class="text-xs text-ink whitespace-nowrap">Mengunggah…</div>
                            </div>
                            <p class="mt-1 text-xs text-ink">Unggah berkas (maks 2 MB) untuk menimpa URL di atas.</p>
                            @error('imageFile') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror

                            @if($imageFile)
                                <img src="{{ $imageFile->temporaryUrl() }}" alt="Preview gambar utama" class="mt-2 size-16 rounded-lg object-cover border border-gray-200">
                            @elseif($image_url)
                                <img src="{{ $image_url }}" alt="Gambar utama" class="mt-2 size-16 rounded-lg object-cover border border-gray-200">
                            @endif
                        </div>

                        <div>
                            <label for="galleryFiles" class="block text-sm font-medium text-gray-700 mb-1.5">
                                Foto tambahan
                                <span class="text-ink font-normal">— sisa {{ $this->remainingGallerySlots }} slot</span>
                            </label>
                            <input wire:model="galleryFiles" id="galleryFiles" type="file" accept="image/*" multiple
                                   @disabled(count($existingGallery) >= \App\Models\Product::MAX_GALLERY_IMAGES - 1)
                                   class="block w-full text-xs text-ink file:mr-3 file:rounded-lg file:border-0 file:bg-accent/15 file:px-3 file:py-2 file:text-xs file:font-semibold file:text-gray-800 hover:file:bg-accent/25 disabled:opacity-50 disabled:cursor-not-allowed">
                            <div wire:loading wire:target="galleryFiles" class="mt-1 text-xs text-ink">Mengunggah gallery…</div>
                            <p class="mt-1 text-xs text-ink">Pilih beberapa foto sekaligus. Total di toko tetap maksimal {{ \App\Models\Product::MAX_GALLERY_IMAGES }}.</p>
                            @error('galleryFiles') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                            @error('galleryFiles.*') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror

                            @if($existingGallery !== [] || $galleryFiles !== [])
                                <div class="mt-3 flex flex-wrap gap-3">
                                    @foreach($existingGallery as $image)
                                        <div class="relative">
                                            <img src="{{ $image['url'] }}" alt="" class="size-16 rounded-lg object-cover border border-gray-200">
                                            <button type="button" wire:click="removeExistingGalleryImage({{ $image['id'] }})"
                                                    class="absolute -right-2 -top-2 flex size-5 items-center justify-center rounded-full bg-red-500 text-xs font-bold text-white"
                                                    title="Hapus foto">×</button>
                                        </div>
                                    @endforeach
                                    @foreach($galleryFiles as $file)
                                        @if($file)
                                            <img src="{{ $file->temporaryUrl() }}" alt="" class="size-16 rounded-lg object-cover border border-dashed border-accent">
                                        @endif
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Varian ukuran & warna --}}
                <div class="rounded-2xl border border-gray-100 bg-gray-50/60 p-4">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm font-semibold text-gray-900">Varian (Ukuran &amp; Warna)</p>
                            <p class="mt-0.5 text-xs text-ink">Kosongkan bila produk ini memakai satu stok saja.</p>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <select wire:model="variantColorToAdd"
                                    class="rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-primary">
                                <option value="">Pilih warna</option>
                                @foreach($this->variantColorOptions as $color)
                                    <option value="{{ $color }}">{{ $color }}</option>
                                @endforeach
                            </select>
                            <button type="button" wire:click="addSizesForColor"
                                    class="rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm font-semibold text-gray-700 transition-colors hover:border-accent hover:text-gray-900">
                                Isi semua ukuran
                            </button>
                            <button type="button" wire:click="addVariantRow"
                                    class="admin-btn px-3 py-2 text-sm">
                                Tambah baris
                            </button>
                        </div>
                    </div>
                    @error('variantColorToAdd') <p class="mt-2 text-xs text-red-500">{{ $message }}</p> @enderror
                    @error('variantRows') <p class="mt-2 text-xs text-red-500">{{ $message }}</p> @enderror

                    @if($variantRows !== [])
                        <div class="mt-4 space-y-2">
                            @foreach($variantRows as $index => $row)
                                <div wire:key="variant-{{ $index }}" class="grid grid-cols-2 gap-2 sm:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_110px_minmax(0,1fr)_auto]">
                                    <div>
                                        <input wire:model="variantRows.{{ $index }}.size" type="text" list="variant-size-options" placeholder="Ukuran"
                                               class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-primary @error('variantRows.'.$index.'.size') border-red-300 bg-red-50 @enderror">
                                        @error('variantRows.'.$index.'.size') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <select wire:model="variantRows.{{ $index }}.color"
                                                class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-primary @error('variantRows.'.$index.'.color') border-red-300 bg-red-50 @enderror">
                                            <option value="">Warna</option>
                                            @foreach($this->variantColorOptions as $color)
                                                <option value="{{ $color }}">{{ $color }}</option>
                                            @endforeach
                                        </select>
                                        @error('variantRows.'.$index.'.color') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <input wire:model="variantRows.{{ $index }}.stock" type="number" min="0" placeholder="Stok"
                                               class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-primary @error('variantRows.'.$index.'.stock') border-red-300 bg-red-50 @enderror">
                                        @error('variantRows.'.$index.'.stock') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <input wire:model="variantRows.{{ $index }}.sku" type="text" placeholder="SKU (opsional)"
                                               class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-primary @error('variantRows.'.$index.'.sku') border-red-300 bg-red-50 @enderror">
                                        @error('variantRows.'.$index.'.sku') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                                    </div>
                                    <button type="button" wire:click="removeVariantRow({{ $index }})"
                                            class="justify-self-start rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm font-semibold text-ink transition-colors hover:border-red-200 hover:text-red-600">
                                        Hapus
                                    </button>
                                </div>
                            @endforeach
                        </div>
                        <datalist id="variant-size-options">
                            @foreach($this->suggestedSizes as $size)
                                <option value="{{ $size }}"></option>
                            @endforeach
                        </datalist>
                    @endif
                </div>
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-1.5">Deskripsi</label>
                    <textarea wire:model="description" id="description" rows="4"
                              class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition-all duration-200 text-sm @error('description') border-red-300 bg-red-50 @enderror"
                              placeholder="Deskripsi produk"></textarea>
                    @error('description') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Kondisi Barang</label>
                    <div class="flex flex-wrap gap-4">
                        @foreach(ProductCondition::options() as $option)
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" wire:model="condition" value="{{ $option->value }}"
                                       class="size-4 border-gray-300 text-accent focus:ring-accent/20">
                                <span class="text-sm text-gray-700">{{ $option->label() }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('condition') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
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
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" wire:model="is_flash_sale"
                               class="size-4 rounded border-gray-300 text-accent focus:ring-accent/20">
                        <span class="text-sm text-gray-700">Flash Sale</span>
                    </label>
                </div>
                <div class="flex items-center gap-3 pt-2">
                    <button type="submit"
                            class="admin-btn px-6 py-2.5 text-sm">
                        {{ $editingProduct ? 'Simpan Perubahan' : 'Tambah Produk' }}
                    </button>
                    <button type="button" wire:click="cancel"
                            class="text-ink hover:text-gray-700 font-medium px-6 py-2.5 rounded-xl border border-gray-200 hover:bg-gray-50 transition-all duration-200 text-sm">
                        Batal
                    </button>
                </div>
            </form>
        </div>
    @endif

    {{-- Search --}}
    <div class="relative">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-ink">
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
                        <th class="text-left px-4 py-3 font-semibold text-ink">Gambar</th>
                        <th class="text-left px-4 py-3 font-semibold text-ink">Nama</th>
                        <th class="text-left px-4 py-3 font-semibold text-ink hidden sm:table-cell">Kategori</th>
                        <th class="text-left px-4 py-3 font-semibold text-ink hidden md:table-cell">Harga</th>
                        <th class="text-left px-4 py-3 font-semibold text-ink hidden md:table-cell">Stok</th>
                        <th class="text-left px-4 py-3 font-semibold text-ink hidden lg:table-cell">Label</th>
                        <th class="text-right px-4 py-3 font-semibold text-ink">Aksi</th>
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
                                <span class="text-ink">{{ $product->category->name }}</span>
                            </td>
                            <td class="px-4 py-3 hidden md:table-cell">
                                <span class="font-semibold text-gray-900">{{ $product->formatted_price }}</span>
                            </td>
                            <td class="px-4 py-3 hidden md:table-cell">
                                <span class="font-semibold {{ $product->is_out_of_stock ? 'text-red-600' : ($product->stock <= 5 ? 'text-amber-600' : 'text-gray-900') }}">
                                    {{ $product->stock }}
                                </span>
                                @if($product->variants_count > 0)
                                    <span class="block text-xs text-ink">{{ $product->variants_count }} varian</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 hidden lg:table-cell">
                                <div class="flex flex-wrap gap-1.5">
                                    <x-product-condition-badge :condition="$product->condition" />
                                    @if($product->is_best_seller)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-50 text-yellow-700">Best</span>
                                    @endif
                                    @if($product->is_new_arrival)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700">Arrival</span>
                                    @endif
                                    @if($product->is_flash_sale)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-orange-50 text-orange-700">Flash</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <button wire:click="edit('{{ $product->slug }}')"
                                            class="p-2 text-ink hover:text-accent hover:bg-accent/5 rounded-lg transition-all"
                                            title="Edit">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                        </svg>
                                    </button>
                                    <button wire:click="duplicate('{{ $product->slug }}')"
                                            class="p-2 text-ink hover:text-deep hover:bg-beige rounded-lg transition-all"
                                            title="Duplikat">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 0 1-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75m9 9.75h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 0 0-2.25-.25H8.625c-.621 0-1.125.504-1.125 1.125v3.5m9 9.75a1.875 1.875 0 0 0 1.875-1.875V12.75A1.875 1.875 0 0 0 16.5 10.875h-1.5a1.875 1.875 0 0 0-1.875 1.875v1.5c0 1.036.84 1.875 1.875 1.875h1.5Z" />
                                        </svg>
                                    </button>
                                    <button wire:click="confirmDelete('{{ $product->slug }}')"
                                            class="p-2 text-ink hover:text-red-500 hover:bg-red-50 rounded-lg transition-all"
                                            title="Hapus">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center text-ink">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-8 mx-auto mb-2 text-ink">
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
                    <p class="mt-2 text-sm text-ink">Apakah Anda yakin ingin menghapus produk ini? Tindakan ini tidak dapat dibatalkan.</p>
                </div>
                <div class="flex items-center gap-3 mt-6">
                    <button wire:click="delete"
                            class="flex-1 bg-red-500 hover:bg-red-600 text-ink font-semibold py-2.5 rounded-xl transition-all duration-200 text-sm">
                        Hapus
                    </button>
                    <button wire:click="cancelDelete"
                            class="flex-1 text-ink hover:text-gray-700 font-medium py-2.5 rounded-xl border border-gray-200 hover:bg-gray-50 transition-all duration-200 text-sm">
                        Batal
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>

