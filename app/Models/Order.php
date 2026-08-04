<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = [
        'order_number', 'customer_name', 'customer_email', 'customer_phone',
        'city', 'address', 'notes', 'subtotal', 'discount', 'shipping_cost',
        'shipping_service', 'shipping_etd',
        'total', 'coupon_code', 'status', 'payment_method',
        'payment_gateway', 'payment_transaction_id', 'payment_payload',
        'payment_proof_path', 'paid_at', 'payment_expires_at',
        'shipping_courier', 'tracking_number', 'shipped_at',
    ];

    protected function casts(): array
    {
        return [
            'paid_at' => 'datetime',
            'payment_expires_at' => 'datetime',
            'shipped_at' => 'datetime',
            'payment_payload' => 'array',
            'subtotal' => 'integer',
            'discount' => 'integer',
            'shipping_cost' => 'integer',
            'total' => 'integer',
        ];
    }

    public const STATUSES = [
        'pending_payment' => 'Menunggu Pembayaran',
        'payment_uploaded' => 'Bukti Dikirim',
        'confirmed' => 'Pembayaran Dikonfirmasi',
        'processing' => 'Sedang Diproses',
        'shipped' => 'Dalam Pengiriman',
        'delivered' => 'Pesanan Diterima',
        'cancelled' => 'Dibatalkan',
    ];

    /** @var list<string> */
    public const COURIERS = [
        'JNE', 'J&T Express', 'SiCepat', 'AnterAja', 'Ninja Xpress',
        'POS Indonesia', 'TIKI', 'Lion Parcel', 'ID Express', 'Instant (Gojek/Grab)',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function hasShipmentInfo(): bool
    {
        return filled($this->tracking_number) && filled($this->shipping_courier);
    }

    public function isPaymentExpired(): bool
    {
        return $this->status === 'pending_payment'
            && $this->payment_expires_at !== null
            && $this->payment_expires_at->isPast();
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    /**
     * Teks panduan singkat untuk email notifikasi status ke pembeli.
     */
    public function customerStatusGuidance(): string
    {
        return match ($this->status) {
            'pending_payment' => 'Segera selesaikan pembayaran sebelum batas waktu habis, lalu unggah bukti bayar.',
            'payment_uploaded' => 'Bukti pembayaran sudah kami terima. Mohon tunggu konfirmasi dari admin.',
            'confirmed' => 'Pembayaran sudah dikonfirmasi. Pesananmu segera kami proses.',
            'processing' => 'Pesanan sedang disiapkan. Kami akan kabari lagi saat barang dikirim.',
            'shipped' => 'Pesanan sudah dalam pengiriman. Cek nomor resi di detail pesanan atau email pengiriman.',
            'delivered' => 'Pesanan ditandai sudah diterima. Terima kasih sudah belanja di ThafhanClothes!',
            'cancelled' => 'Pesanan dibatalkan. Jika ada pertanyaan, hubungi kami via WhatsApp.',
            default => 'Cek detail pesanan untuk informasi terbaru.',
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending_payment' => 'text-yellow-700 bg-yellow-50 border-yellow-200',
            'payment_uploaded' => 'text-blue-700 bg-blue-50 border-blue-200',
            'confirmed' => 'text-green-700 bg-green-50 border-green-200',
            'processing' => 'text-indigo-700 bg-indigo-50 border-indigo-200',
            'shipped' => 'text-purple-700 bg-purple-50 border-purple-200',
            'delivered' => 'text-emerald-700 bg-emerald-50 border-emerald-200',
            'cancelled' => 'text-red-700 bg-red-50 border-red-200',
            default => 'text-gray-700 bg-gray-50 border-gray-200',
        };
    }

    public function getFormattedTotalAttribute(): string
    {
        return 'Rp'.number_format($this->total, 0, ',', '.');
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}
