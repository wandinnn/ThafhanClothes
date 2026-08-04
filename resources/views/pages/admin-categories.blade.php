<?php

use Livewire\Component;
use App\Models\Category;

new class extends Component {
    public $editingCategory = null;

    public string $name = '';

    public bool $showForm = false;

    public bool $confirmingDelete = false;

    public $deletingCategory = null;

    protected function rules(): array
    {
        return [
            'name' => ['required', 'min:2', 'max:255', 'unique:categories,name,'.($this->editingCategory ?? 'NULL')],
        ];
    }

    protected $messages = [
        'name.required' => 'Nama kategori wajib diisi.',
        'name.min' => 'Nama kategori minimal 2 karakter.',
        'name.unique' => 'Nama kategori sudah ada.',
    ];

    public function create(): void
    {
        $this->resetForm();
        $this->showForm = true;
        $this->editingCategory = null;
    }

    public function edit(Category $category): void
    {
        $this->resetForm();
        $this->editingCategory = $category->id;
        $this->name = $category->name;
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'name' => $this->name,
            'slug' => $this->uniqueSlug($this->name, $this->editingCategory),
        ];

        if ($this->editingCategory) {
            $category = Category::findOrFail($this->editingCategory);
            $category->update($data);
            $this->dispatch('notify', message: 'Kategori berhasil diperbarui.', type: 'success');
        } else {
            Category::create($data);
            $this->dispatch('notify', message: 'Kategori berhasil ditambahkan.', type: 'success');
        }

        $this->showForm = false;
        $this->resetForm();
    }

    /**
     * Slug unik dengan sufiks angka, karena kolom `categories.slug` punya unique index.
     */
    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = \Illuminate\Support\Str::slug($name) ?: 'kategori';
        $slug = $base;
        $suffix = 2;

        while (Category::where('slug', $slug)
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }

    public function confirmDelete(Category $category): void
    {
        $this->deletingCategory = $category->id;
        $this->confirmingDelete = true;
    }

    public function delete(): void
    {
        if ($this->deletingCategory) {
            $category = Category::withCount('products')->findOrFail($this->deletingCategory);
            if ($category->products_count > 0) {
                $this->dispatch('notify', message: 'Kategori tidak dapat dihapus karena masih memiliki produk.', type: 'error');
            } else {
                $category->delete();
                $this->dispatch('notify', message: 'Kategori berhasil dihapus.', type: 'success');
            }
            $this->confirmingDelete = false;
            $this->deletingCategory = null;
        }
    }

    public function cancelDelete(): void
    {
        $this->confirmingDelete = false;
        $this->deletingCategory = null;
    }

    public function cancel(): void
    {
        $this->showForm = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->name = '';
        $this->resetErrorBag();
    }

    public function render()
    {
        $categories = Category::withCount('products')->orderBy('name')->get();

        return view('pages.admin-categories', compact('categories'))
            ->layout('layouts.admin')
            ->title('Kategori');
    }
};
?>

<div class="space-y-6 animate-fade-in">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Kategori</h1>
            <p class="mt-1 text-sm text-ink">Kelola kategori produk</p>
        </div>
        @if(!$showForm)
            <button wire:click="create"
                    class="inline-flex items-center gap-2 bg-accent hover:bg-secondary text-ink font-semibold px-5 py-2.5 rounded-xl transition-all duration-200 hover:-translate-y-0.5 active:translate-y-0 text-sm">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Tambah Kategori
            </button>
        @endif
    </div>

    {{-- Form --}}
    @if($showForm)
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 animate-fade-up max-w-xl">
            <h2 class="text-lg font-semibold text-gray-900 mb-6">{{ $editingCategory ? 'Edit Kategori' : 'Tambah Kategori' }}</h2>
            <form wire:submit="save" class="space-y-5">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1.5">Nama Kategori</label>
                    <input wire:model="name" id="name" type="text"
                           class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition-all duration-200 text-sm @error('name') border-red-300 bg-red-50 @enderror"
                           placeholder="Nama kategori">
                    @error('name') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <div class="flex items-center gap-3 pt-2">
                    <button type="submit"
                            class="bg-accent hover:bg-secondary text-ink font-semibold px-6 py-2.5 rounded-xl transition-all duration-200 hover:-translate-y-0.5 active:translate-y-0 text-sm">
                        {{ $editingCategory ? 'Simpan Perubahan' : 'Tambah Kategori' }}
                    </button>
                    <button type="button" wire:click="cancel"
                            class="text-ink hover:text-gray-700 font-medium px-6 py-2.5 rounded-xl border border-gray-200 hover:bg-gray-50 transition-all duration-200 text-sm">
                        Batal
                    </button>
                </div>
            </form>
        </div>
    @endif

    {{-- List --}}
    <div class="grid gap-4">
        @forelse($categories as $category)
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 flex items-center justify-between group hover:shadow-md transition-all duration-200 animate-fade-up stagger-{{ min($loop->iteration, 6) }}">
                <div>
                    <h3 class="font-semibold text-gray-900">{{ $category->name }}</h3>
                    <p class="text-sm text-ink mt-0.5">{{ $category->products_count }} produk</p>
                </div>
                <div class="flex items-center gap-1">
                    <button wire:click="edit({{ $category->id }})"
                            class="p-2 text-ink hover:text-accent hover:bg-accent/5 rounded-lg transition-all">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                        </svg>
                    </button>
                    <button wire:click="confirmDelete({{ $category->id }})"
                            class="p-2 text-ink hover:text-red-500 hover:bg-red-50 rounded-lg transition-all">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                        </svg>
                    </button>
                </div>
            </div>
        @empty
            <div class="bg-white rounded-2xl p-12 shadow-sm border border-gray-100 text-center">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-8 mx-auto mb-2 text-ink">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6Z" />
                </svg>
                <p class="text-sm text-ink">Belum ada kategori.</p>
            </div>
        @endforelse
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
                    <h3 class="text-lg font-semibold text-gray-900">Hapus Kategori</h3>
                    <p class="mt-2 text-sm text-ink">Apakah Anda yakin ingin menghapus kategori ini? Tindakan ini tidak dapat dibatalkan.</p>
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

