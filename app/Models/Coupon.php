<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    protected $fillable = [
        'code', 'type', 'value', 'min_order', 'max_uses',
        'used_count', 'is_active', 'expires_at', 'description',
    ];

    protected $casts = [
        'expires_at' => 'date',
        'is_active' => 'boolean',
        'value' => 'integer',
        'min_order' => 'integer',
        'used_count' => 'integer',
    ];

    public function isValid(int $orderSubtotal = 0): bool
    {
        if (! $this->is_active) {
            return false;
        }
        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }
        if ($this->max_uses && $this->used_count >= $this->max_uses) {
            return false;
        }
        if ($orderSubtotal < $this->min_order) {
            return false;
        }

        return true;
    }

    public function calculateDiscount(int $subtotal): int
    {
        if ($this->type === 'percent') {
            return (int) round($subtotal * $this->value / 100);
        }

        return min($this->value, $subtotal);
    }

    public function getFormattedValueAttribute(): string
    {
        if ($this->type === 'percent') {
            return $this->value.'%';
        }

        return 'Rp'.number_format($this->value, 0, ',', '.');
    }
}
