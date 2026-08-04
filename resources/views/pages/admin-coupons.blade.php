<?php

use App\Models\Coupon;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public string $search = '';

    public ?int $editingCoupon = null;

    public string $code = '';

    public string $type = 'fixed';

    public int $value = 0;

    public int $min_order = 0;

    public ?int $max_uses = null;

    public bool $is_active = true;

    public string $expires_at = '';

    public string $description = '';

    public bool $showForm = false;

    public ?int $deletingCoupon = null;

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function rules(): array
    {
        return [
            'code' => [
                'required', 'min:3', 'max:32', 'regex:/^[A-Za-z0-9]+$/',
                Rule::unique('coupons', 'code')->ignore($this->editingCoupon),
            ],
            'type' => ['required', Rule::in(['fixed', 'percent'])],
            'value' => ['required', 'integer', 'min:1', $this->type === 'percent' ? 'max:100' : 'max:100000000'],
            'min_order' => ['required', 'integer', 'min:0'],
            'max_uses' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['boolean'],
            'expires_at' => ['nullable', 'date'],
            'description' => ['nullable', 'max:255'],
        ];
    }

    /**
     * @var array<string, string>
     */
    protected array $messages = [
        'code.required' => 'Kode kupon wajib diisi.',
        'code.regex' => 'Kode kupon hanya boleh berisi huruf dan angka.',
        'code.unique' => 'Kode kupon sudah dipakai.',
        'value.required' => 'Nilai potongan wajib diisi.',
        'value.min' => 'Nilai potongan minimal 1.',
        'value.max' => 'Diskon persen maksimal 100.',
        'min_order.min' => 'Minimum belanja tidak boleh negatif.',
        'max_uses.min' => 'Batas pemakaian minimal 1.',
        'expires_at.date' => 'Tanggal kadaluarsa tidak valid.',
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function create(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $coupon = Coupon::findOrFail($id);

        $this->resetForm();
        $this->editingCoupon = $coupon->id;
        $this->code = $coupon->code;
        $this->type = $coupon->type;
        $this->value = (int) $coupon->value;
        $this->min_order = (int) $coupon->min_order;
        $this->max_uses = $coupon->max_uses;
        $this->is_active = (bool) $coupon->is_active;
        $this->expires_at = $coupon->expires_at?->format('Y-m-d') ?? '';
        $this->description = (string) ($coupon->description ?? '');
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'code' => strtoupper(trim($this->code)),
            'type' => $this->type,
            'value' => $this->value,
            'min_order' => $this->min_order,
            'max_uses' => $this->max_uses,
            'is_active' => $this->is_active,
            'expires_at' => $this->expires_at !== '' ? $this->expires_at : null,
            'description' => $this->description !== '' ? $this->description : null,
        ];

        if ($this->editingCoupon) {
            Coupon::findOrFail($this->editingCoupon)->update($data);
            $this->dispatch('notify', message: 'Kupon berhasil diperbarui.', type: 'success');
        } else {
            Coupon::create($data + ['used_count' => 0]);
            $this->dispatch('notify', message: 'Kupon berhasil ditambahkan.', type: 'success');
        }

        $this->showForm = false;
        $this->resetForm();
    }

    public function toggleActive(int $id): void
    {
        $coupon = Coupon::findOrFail($id);
        $coupon->update(['is_active' => ! $coupon->is_active]);

        $this->dispatch(
            'notify',
            message: $coupon->is_active ? "Kupon {$coupon->code} diaktifkan." : "Kupon {$coupon->code} dinonaktifkan.",
            type: 'success',
        );
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingCoupon = $id;
    }

    public function cancelDelete(): void
    {
        $this->deletingCoupon = null;
    }

    public function delete(): void
    {
        if (! $this->deletingCoupon) {
            return;
        }

        Coupon::findOrFail($this->deletingCoupon)->delete();
        $this->deletingCoupon = null;
        $this->dispatch('notify', message: 'Kupon berhasil dihapus.', type: 'success');
    }

    public function cancel(): void
    {
        $this->showForm = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->editingCoupon = null;
        $this->code = '';
        $this->type = 'fixed';
        $this->value = 0;
        $this->min_order = 0;
        $this->max_uses = null;
        $this->is_active = true;
        $this->expires_at = '';
        $this->description = '';
        $this->resetErrorBag();
    }

    public function render()
    {
        $coupons = Coupon::when($this->search, fn ($query) => $query->where('code', 'like', '%'.$this->search.'%'))
            ->orderByDesc('created_at')
            ->paginate(10);

        return view('pages.admin-coupons', compact('coupons'))
            ->layout('layouts.admin')
            ->title('Kupon');
    }
};
?>

<div class="space-y-6 animate-fade-in">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Kupon</h1>
            <p class="mt-1 text-sm text-ink">Kelola kode promo dan potongan harga</p>
        </div>
        @if(! $showForm)
            <button wire:click="create"
                    class="admin-btn px-5 py-2.5 text-sm">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Tambah Kupon
            </button>
        @endif
    </div>

    {{-- Form --}}
    @if($showForm)
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 animate-fade-up">
            <h2 class="text-lg font-semibold text-gray-900 mb-6">{{ $editingCoupon ? 'Edit Kupon' : 'Tambah Kupon' }}</h2>
            <form wire:submit="save" class="space-y-5">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                    <div>
                        <label for="code" class="block text-sm font-medium text-gray-700 mb-1.5">Kode Kupon</label>
                        <input wire:model="code" id="code" type="text" placeholder="HEMAT10"
                               class="w-full px-4 py-2.5 rounded-xl border border-gray-200 uppercase focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition-all duration-200 text-sm @error('code') border-red-300 bg-red-50 @enderror">
                        @error('code') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="type" class="block text-sm font-medium text-gray-700 mb-1.5">Jenis Potongan</label>
                        <select wire:model.live="type" id="type"
                                class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition-all duration-200 text-sm">
                            <option value="fixed">Nominal (Rp)</option>
                            <option value="percent">Persen (%)</option>
                        </select>
                    </div>
                    <div>
                        <label for="value" class="block text-sm font-medium text-gray-700 mb-1.5">
                            Nilai Potongan {{ $type === 'percent' ? '(%)' : '(Rp)' }}
                        </label>
                        <input wire:model="value" id="value" type="number" min="1"
                               class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition-all duration-200 text-sm @error('value') border-red-300 bg-red-50 @enderror"
                               placeholder="{{ $type === 'percent' ? '10' : '20000' }}">
                        @error('value') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="min_order" class="block text-sm font-medium text-gray-700 mb-1.5">Minimum Belanja (Rp)</label>
                        <input wire:model="min_order" id="min_order" type="number" min="0"
                               class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition-all duration-200 text-sm @error('min_order') border-red-300 bg-red-50 @enderror"
                               placeholder="0">
                        @error('min_order') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="max_uses" class="block text-sm font-medium text-gray-700 mb-1.5">
                            Batas Pemakaian <span class="text-ink font-normal">— kosongkan bila tanpa batas</span>
                        </label>
                        <input wire:model="max_uses" id="max_uses" type="number" min="1"
                               class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition-all duration-200 text-sm @error('max_uses') border-red-300 bg-red-50 @enderror"
                               placeholder="100">
                        @error('max_uses') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="expires_at" class="block text-sm font-medium text-gray-700 mb-1.5">
                            Kadaluarsa <span class="text-ink font-normal">— opsional</span>
                        </label>
                        <input wire:model="expires_at" id="expires_at" type="date"
                               class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition-all duration-200 text-sm @error('expires_at') border-red-300 bg-red-50 @enderror">
                        @error('expires_at') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-1.5">
                        Keterangan <span class="text-ink font-normal">— opsional</span>
                    </label>
                    <input wire:model="description" id="description" type="text" placeholder="Promo akhir pekan"
                           class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition-all duration-200 text-sm @error('description') border-red-300 bg-red-50 @enderror">
                    @error('description') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" wire:model="is_active" class="size-4 rounded border-gray-300 text-accent focus:ring-accent/20">
                    <span class="text-sm text-gray-700">Kupon aktif</span>
                </label>
                <div class="flex items-center gap-3 pt-2">
                    <button type="submit"
                            class="admin-btn px-6 py-2.5 text-sm">
                        {{ $editingCoupon ? 'Simpan Perubahan' : 'Tambah Kupon' }}
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
        <input wire:model.live.debounce.300ms="search" type="text" placeholder="Cari kode kupon..."
               class="w-full max-w-xs pl-10 pr-4 py-2.5 rounded-xl border border-gray-200 focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition-all duration-200 text-sm bg-white">
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100">
                        <th class="text-left px-4 py-3 font-semibold text-ink">Kode</th>
                        <th class="text-left px-4 py-3 font-semibold text-ink">Potongan</th>
                        <th class="text-left px-4 py-3 font-semibold text-ink hidden md:table-cell">Min. Belanja</th>
                        <th class="text-left px-4 py-3 font-semibold text-ink hidden md:table-cell">Terpakai</th>
                        <th class="text-left px-4 py-3 font-semibold text-ink hidden lg:table-cell">Kadaluarsa</th>
                        <th class="text-left px-4 py-3 font-semibold text-ink">Status</th>
                        <th class="text-right px-4 py-3 font-semibold text-ink">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($coupons as $coupon)
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-4 py-3">
                                <p class="font-mono font-bold text-gray-900">{{ $coupon->code }}</p>
                                @if($coupon->description)
                                    <p class="text-xs text-ink">{{ $coupon->description }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-3 font-semibold text-gray-900">{{ $coupon->formatted_value }}</td>
                            <td class="px-4 py-3 hidden md:table-cell text-ink">
                                {{ $coupon->min_order > 0 ? 'Rp'.number_format($coupon->min_order, 0, ',', '.') : '—' }}
                            </td>
                            <td class="px-4 py-3 hidden md:table-cell text-ink">
                                {{ $coupon->used_count }}{{ $coupon->max_uses ? ' / '.$coupon->max_uses : '' }}
                            </td>
                            <td class="px-4 py-3 hidden lg:table-cell text-ink">
                                {{ $coupon->expires_at?->format('d M Y') ?? 'Tanpa batas' }}
                            </td>
                            <td class="px-4 py-3">
                                @if($coupon->isValid())
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-50 text-green-700">Aktif</span>
                                @elseif(! $coupon->is_active)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">Nonaktif</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-50 text-amber-700">Tidak berlaku</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <button wire:click="toggleActive({{ $coupon->id }})"
                                            class="px-2.5 py-1.5 text-xs font-semibold text-ink hover:text-gray-900 border border-gray-200 rounded-lg transition-all hover:border-accent">
                                        {{ $coupon->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                    </button>
                                    <button wire:click="edit({{ $coupon->id }})"
                                            class="p-2 text-ink hover:text-accent hover:bg-accent/5 rounded-lg transition-all" aria-label="Edit kupon">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                        </svg>
                                    </button>
                                    <button wire:click="confirmDelete({{ $coupon->id }})"
                                            class="p-2 text-ink hover:text-red-500 hover:bg-red-50 rounded-lg transition-all" aria-label="Hapus kupon">
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
                                <p class="text-sm">Belum ada kupon.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($coupons->hasPages())
            <div class="px-4 py-3 border-t border-gray-100">{{ $coupons->links() }}</div>
        @endif
    </div>

    {{-- Delete Confirmation Modal --}}
    @if($deletingCoupon)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/30" wire:click="cancelDelete"></div>
            <div class="relative bg-white rounded-2xl shadow-xl p-6 max-w-sm w-full animate-fade-up">
                <div class="text-center">
                    <div class="mx-auto size-12 rounded-full bg-red-100 flex items-center justify-center mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6 text-red-500">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900">Hapus Kupon</h3>
                    <p class="mt-2 text-sm text-ink">Kupon yang dihapus tidak bisa dipakai lagi oleh pembeli.</p>
                </div>
                <div class="flex items-center gap-3 mt-6">
                    <button wire:click="delete"
                            class="flex-1 bg-red-500 hover:bg-red-600 text-white font-semibold py-2.5 rounded-xl transition-all duration-200 text-sm">
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
