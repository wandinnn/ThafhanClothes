<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Wishlist extends Model
{
    protected $fillable = ['session_id', 'product_id'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public static function forSession(): Collection
    {
        return static::where('session_id', session()->getId())
            ->with('product.category')
            ->get();
    }

    public static function toggleForSession(int $productId): bool
    {
        $sessionId = session()->getId();
        $existing = static::where('session_id', $sessionId)
            ->where('product_id', $productId)
            ->first();

        if ($existing) {
            $existing->delete();

            return false; // removed
        }

        static::create(['session_id' => $sessionId, 'product_id' => $productId]);

        return true; // added
    }

    public static function countForSession(): int
    {
        return static::where('session_id', session()->getId())->count();
    }
}
