<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = [
        'order_number', 'customer_name', 'customer_email', 'customer_phone',
        'city', 'address', 'notes', 'subtotal', 'discount', 'shipping_cost',
        'total', 'coupon_code', 'status', 'payment_method',
        'payment_proof_path', 'paid_at',
        'shipping_courier', 'tracking_number', 'shipped_at',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'shipped_at' => 'datetime',
        'subtotal' => 'integer',
        'discount' => 'integer',
        'shipping_cost' => 'integer',
        'total' => 'integer',
    ];

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

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
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
