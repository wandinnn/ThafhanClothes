<?php

use App\Support\ShopSettings;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component {
    use WithFileUploads;

    public string $bankName = '';

    public string $bankAccount = '';

    public string $bankHolder = '';

    public string $whatsapp = '';

    public string $flashSaleEndsAt = '';

    public bool $mailEnabled = true;

    public bool $mailStatus = true;

    public bool $mailShipment = true;

    public bool $mailPaymentProof = true;

    public string $paymentDriver = 'manual';

    public $qrisFile = null;

    public ?string $qrisPreviewUrl = null;

    public string $aboutTitle = '';

    public string $aboutBody = '';

    /** @var array<int, array{q: string, a: string}> */
    public array $faqRows = [];

    public function mount(): void
    {
        $bank = ShopSettings::bank();

        $this->bankName = $bank['name'];
        $this->bankAccount = $bank['account'];
        $this->bankHolder = $bank['holder'];
        $this->whatsapp = ShopSettings::whatsapp();

        $endsAt = ShopSettings::flashSaleEndsAt();
        $this->flashSaleEndsAt = filled($endsAt)
            ? Carbon::parse($endsAt, config('shop.timezone', 'Asia/Jakarta'))->format('Y-m-d\TH:i')
            : '';

        $this->mailEnabled = ShopSettings::mailEnabled();
        $this->mailStatus = ShopSettings::mailStatusEnabled();
        $this->mailShipment = ShopSettings::mailShipmentEnabled();
        $this->mailPaymentProof = ShopSettings::mailPaymentProofCustomerEnabled();
        $this->paymentDriver = ShopSettings::paymentDriver();
        $this->qrisPreviewUrl = ShopSettings::qrisUrl();
        $this->aboutTitle = ShopSettings::aboutTitle();
        $this->aboutBody = ShopSettings::aboutBody();
        $this->faqRows = ShopSettings::faqItems();
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function rules(): array
    {
        return [
            'bankName' => ['required', 'string', 'max:100'],
            'bankAccount' => ['required', 'string', 'max:50', 'regex:/^[0-9\-\s]+$/'],
            'bankHolder' => ['required', 'string', 'max:120'],
            'whatsapp' => ['required', 'string', 'max:20', 'regex:/^[0-9]+$/'],
            'flashSaleEndsAt' => ['nullable', 'date'],
            'mailEnabled' => ['boolean'],
            'mailStatus' => ['boolean'],
            'mailShipment' => ['boolean'],
            'mailPaymentProof' => ['boolean'],
            'paymentDriver' => ['required', 'in:manual,fake,midtrans'],
            'qrisFile' => ['nullable', 'image', 'max:4096'],
            'aboutTitle' => ['required', 'string', 'max:200'],
            'aboutBody' => ['required', 'string', 'max:5000'],
            'faqRows' => ['required', 'array', 'min:1'],
            'faqRows.*.q' => ['required', 'string', 'max:255'],
            'faqRows.*.a' => ['required', 'string', 'max:2000'],
        ];
    }

    /**
     * @var array<string, string>
     */
    protected array $messages = [
        'bankName.required' => 'Nama bank wajib diisi.',
        'bankAccount.required' => 'Nomor rekening wajib diisi.',
        'bankAccount.regex' => 'Nomor rekening hanya boleh angka, spasi, atau strip.',
        'bankHolder.required' => 'Nama pemilik rekening wajib diisi.',
        'whatsapp.required' => 'Nomor WhatsApp wajib diisi.',
        'whatsapp.regex' => 'Nomor WhatsApp hanya boleh angka (contoh: 6281324825060).',
        'flashSaleEndsAt.date' => 'Tanggal flash sale tidak valid.',
        'mailPaymentProof.boolean' => 'Pengaturan email bukti bayar tidak valid.',
        'paymentDriver.required' => 'Metode pembayaran wajib dipilih.',
        'paymentDriver.in' => 'Metode pembayaran tidak valid.',
        'qrisFile.image' => 'File QRIS harus berupa gambar.',
        'qrisFile.max' => 'Ukuran gambar QRIS maksimal 4 MB.',
        'aboutTitle.required' => 'Judul tentang kami wajib diisi.',
        'aboutBody.required' => 'Isi tentang kami wajib diisi.',
        'faqRows.required' => 'Minimal satu FAQ.',
        'faqRows.*.q.required' => 'Pertanyaan FAQ wajib diisi.',
        'faqRows.*.a.required' => 'Jawaban FAQ wajib diisi.',
    ];

    public function addFaqRow(): void
    {
        $this->faqRows[] = ['q' => '', 'a' => ''];
    }

    public function removeFaqRow(int $index): void
    {
        unset($this->faqRows[$index]);
        $this->faqRows = array_values($this->faqRows);

        if ($this->faqRows === []) {
            $this->faqRows = [['q' => '', 'a' => '']];
        }
    }

    public function removeQris(): void
    {
        $path = ShopSettings::qrisPath();

        if (filled($path) && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }

        ShopSettings::set(ShopSettings::KEY_QRIS_PATH, null);
        $this->qrisFile = null;
        $this->qrisPreviewUrl = ShopSettings::qrisUrl();
        session()->flash('settingsSaved', 'Gambar QRIS dihapus dari pengaturan (fallback public/images/qris.png tetap dipakai jika ada).');
    }

    public function save(): void
    {
        $this->validate();

        $qrisPath = ShopSettings::qrisPath();

        if ($this->qrisFile) {
            if (filled($qrisPath) && Storage::disk('public')->exists($qrisPath)) {
                Storage::disk('public')->delete($qrisPath);
            }

            $qrisPath = $this->qrisFile->store('qris', 'public');
        }

        ShopSettings::putMany([
            ShopSettings::KEY_BANK_NAME => trim($this->bankName),
            ShopSettings::KEY_BANK_ACCOUNT => preg_replace('/\s+/', '', trim($this->bankAccount)) ?? trim($this->bankAccount),
            ShopSettings::KEY_BANK_HOLDER => trim($this->bankHolder),
            ShopSettings::KEY_WHATSAPP => trim($this->whatsapp),
            ShopSettings::KEY_FLASH_SALE_ENDS_AT => $this->flashSaleEndsAt !== ''
                ? Carbon::parse($this->flashSaleEndsAt, config('shop.timezone', 'Asia/Jakarta'))->format('Y-m-d H:i:s')
                : null,
            ShopSettings::KEY_MAIL_ENABLED => $this->mailEnabled ? '1' : '0',
            ShopSettings::KEY_MAIL_STATUS => $this->mailStatus ? '1' : '0',
            ShopSettings::KEY_MAIL_SHIPMENT => $this->mailShipment ? '1' : '0',
            ShopSettings::KEY_MAIL_PAYMENT_PROOF => $this->mailPaymentProof ? '1' : '0',
            ShopSettings::KEY_PAYMENT_DRIVER => $this->paymentDriver,
            ShopSettings::KEY_QRIS_PATH => $qrisPath,
            ShopSettings::KEY_ABOUT_TITLE => trim($this->aboutTitle),
            ShopSettings::KEY_ABOUT_BODY => trim($this->aboutBody),
            ShopSettings::KEY_FAQ_ITEMS => json_encode(
                collect($this->faqRows)
                    ->map(fn (array $row): array => [
                        'q' => trim((string) $row['q']),
                        'a' => trim((string) $row['a']),
                    ])
                    ->filter(fn (array $row): bool => $row['q'] !== '' && $row['a'] !== '')
                    ->values()
                    ->all(),
                JSON_UNESCAPED_UNICODE
            ),
        ]);

        $this->qrisFile = null;
        $this->qrisPreviewUrl = ShopSettings::qrisUrl();
        $this->faqRows = ShopSettings::faqItems();

        session()->flash('settingsSaved', 'Pengaturan toko berhasil disimpan.');
    }

    public function render()
    {
        return view('pages.admin-settings')
            ->layout('layouts.admin')
            ->title('Pengaturan Toko');
    }
};
?>

<div class="space-y-6 animate-fade-in max-w-3xl">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Pengaturan Toko</h1>
        <p class="mt-1 text-sm text-ink">Ubah info bank, WhatsApp, QRIS, FAQ, About, dan notifikasi tanpa edit kode.</p>
    </div>

    @if (session('settingsSaved'))
        <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
            {{ session('settingsSaved') }}
        </div>
    @endif

    <form wire:submit="save" class="space-y-6">
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 space-y-4">
            <h2 class="text-lg font-semibold text-gray-900">Metode Pembayaran</h2>
            <p class="text-xs text-ink">
                <strong>manual</strong> = transfer/QRIS + unggah bukti.
                <strong>midtrans</strong> = Snap (butuh <code class="text-[11px] bg-beige px-1 rounded">MIDTRANS_SERVER_KEY</code> &amp; client key di .env).
                <strong>fake</strong> = simulasi lokal tanpa gateway.
            </p>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Driver aktif</label>
                <select wire:model="paymentDriver" class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:border-beige focus:outline-none focus:ring-2 focus:ring-beige/20 @error('paymentDriver') border-red-300 @enderror">
                    <option value="manual">Manual (transfer + QRIS)</option>
                    <option value="midtrans">Midtrans Snap</option>
                    <option value="fake">Fake (simulasi lokal)</option>
                </select>
                @error('paymentDriver') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                <p class="mt-2 text-xs text-ink">
                    Webhook Midtrans: <code class="text-[11px] bg-beige px-1 rounded">{{ url('/webhooks/midtrans') }}</code>
                    — daftarkan di dashboard Midtrans (Settings → Configuration).
                </p>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 space-y-4">
            <h2 class="text-lg font-semibold text-gray-900">Rekening Transfer</h2>
            <p class="text-xs text-ink">Tampil di halaman pembayaran dan invoice.</p>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Nama Bank</label>
                <input wire:model="bankName" type="text" class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:border-beige focus:outline-none focus:ring-2 focus:ring-beige/20 @error('bankName') border-red-300 @enderror">
                @error('bankName') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Nomor Rekening</label>
                <input wire:model="bankAccount" type="text" class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:border-beige focus:outline-none focus:ring-2 focus:ring-beige/20 @error('bankAccount') border-red-300 @enderror">
                @error('bankAccount') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Atas Nama</label>
                <input wire:model="bankHolder" type="text" class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:border-beige focus:outline-none focus:ring-2 focus:ring-beige/20 @error('bankHolder') border-red-300 @enderror">
                @error('bankHolder') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 space-y-4">
            <h2 class="text-lg font-semibold text-gray-900">Kontak & Promo</h2>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Nomor WhatsApp (format 62…)</label>
                <input wire:model="whatsapp" type="text" placeholder="6281324825060" class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:border-beige focus:outline-none focus:ring-2 focus:ring-beige/20 @error('whatsapp') border-red-300 @enderror">
                @error('whatsapp') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Flash Sale berakhir (opsional)</label>
                <input wire:model="flashSaleEndsAt" type="datetime-local" class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:border-beige focus:outline-none focus:ring-2 focus:ring-beige/20 @error('flashSaleEndsAt') border-red-300 @enderror">
                <p class="mt-1 text-xs text-ink">Kosongkan untuk memakai default (akhir hari ini / nilai .env).</p>
                @error('flashSaleEndsAt') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 space-y-4">
            <h2 class="text-lg font-semibold text-gray-900">Gambar QRIS</h2>
            <p class="text-xs text-ink">Upload kapan saja setelah QRIS GoPay/sejenis siap. Belum ada gambar = slot kosong di halaman bayar.</p>

            @if ($qrisPreviewUrl)
                <img src="{{ $qrisFile ? $qrisFile->temporaryUrl() : $qrisPreviewUrl }}" alt="QRIS" class="w-48 h-48 object-contain rounded-xl border border-gray-200 bg-beige/40">
            @elseif ($qrisFile)
                <img src="{{ $qrisFile->temporaryUrl() }}" alt="Preview QRIS" class="w-48 h-48 object-contain rounded-xl border border-gray-200 bg-beige/40">
            @else
                <div class="w-48 h-48 rounded-xl border-2 border-dashed border-gray-200 bg-beige/40 flex items-center justify-center text-xs text-ink text-center px-3">
                    Belum ada gambar QRIS
                </div>
            @endif

            <input wire:model="qrisFile" type="file" accept="image/*" class="block w-full text-sm text-ink file:mr-3 file:rounded-lg file:border-0 file:bg-deep file:px-3 file:py-2 file:text-cream">
            @error('qrisFile') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror

            @if (ShopSettings::qrisPath())
                <button type="button" wire:click="removeQris" class="text-sm font-semibold text-red-600 hover:underline">Hapus gambar QRIS admin</button>
            @endif
        </div>

        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 space-y-4">
            <h2 class="text-lg font-semibold text-gray-900">Notifikasi Email</h2>
            <p class="text-xs text-ink">Email ke pembeli saat status berubah / resi / bukti bayar. Tidak perlu akun pembeli.</p>

            <label class="flex items-center gap-3 text-sm text-gray-800">
                <input wire:model="mailEnabled" type="checkbox" class="rounded border-gray-300 text-deep focus:ring-beige">
                Aktifkan semua notifikasi email toko
            </label>
            <label class="flex items-center gap-3 text-sm text-gray-800">
                <input wire:model="mailStatus" type="checkbox" class="rounded border-gray-300 text-deep focus:ring-beige">
                Email saat status pesanan berubah
            </label>
            <label class="flex items-center gap-3 text-sm text-gray-800">
                <input wire:model="mailShipment" type="checkbox" class="rounded border-gray-300 text-deep focus:ring-beige">
                Email saat nomor resi disimpan
            </label>
            <label class="flex items-center gap-3 text-sm text-gray-800">
                <input wire:model="mailPaymentProof" type="checkbox" class="rounded border-gray-300 text-deep focus:ring-beige">
                Email ke pembeli saat bukti bayar diunggah
            </label>
        </div>

        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 space-y-4">
            <h2 class="text-lg font-semibold text-gray-900">Halaman Tentang Kami</h2>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Judul</label>
                <input wire:model="aboutTitle" type="text" class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:border-beige focus:outline-none focus:ring-2 focus:ring-beige/20 @error('aboutTitle') border-red-300 @enderror">
                @error('aboutTitle') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Isi (pisahkan paragraf dengan baris kosong)</label>
                <textarea wire:model="aboutBody" rows="6" class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:border-beige focus:outline-none focus:ring-2 focus:ring-beige/20 @error('aboutBody') border-red-300 @enderror"></textarea>
                @error('aboutBody') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 space-y-4">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">FAQ</h2>
                    <p class="text-xs text-ink">Tampil di halaman FAQ publik.</p>
                </div>
                <button type="button" wire:click="addFaqRow" class="text-sm font-semibold text-deep hover:underline">+ Tambah</button>
            </div>

            @foreach ($faqRows as $index => $row)
                <div class="rounded-xl border border-gray-100 bg-beige/30 p-4 space-y-3" wire:key="faq-row-{{ $index }}">
                    <div class="flex items-center justify-between">
                        <p class="text-xs font-semibold text-ink uppercase">FAQ #{{ $index + 1 }}</p>
                        <button type="button" wire:click="removeFaqRow({{ $index }})" class="text-xs font-semibold text-red-600 hover:underline">Hapus</button>
                    </div>
                    <input wire:model="faqRows.{{ $index }}.q" type="text" placeholder="Pertanyaan" class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm @error('faqRows.'.$index.'.q') border-red-300 @enderror">
                    @error('faqRows.'.$index.'.q') <p class="text-xs text-red-500">{{ $message }}</p> @enderror
                    <textarea wire:model="faqRows.{{ $index }}.a" rows="3" placeholder="Jawaban" class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm @error('faqRows.'.$index.'.a') border-red-300 @enderror"></textarea>
                    @error('faqRows.'.$index.'.a') <p class="text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
            @endforeach
        </div>

        <div class="flex justify-end">
            <button type="submit"
                    class="admin-btn rounded-full px-6 py-3 text-sm"
                    style="background-color: var(--theme-deep); color: var(--theme-cream);">
                Simpan Pengaturan
            </button>
        </div>
    </form>
</div>
