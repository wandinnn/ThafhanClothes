<?php

namespace App\Support;

use App\Models\Order;

/**
 * Akses detail pesanan dilindungi nomor pesanan + 4 digit terakhir telepon,
 * lalu disimpan di session supaya pembeli tidak perlu mengisi ulang.
 */
class OrderAccess
{
    public const SESSION_KEY = 'verified_orders';

    public static function grant(string $orderNumber): void
    {
        $orderNumber = strtoupper(trim($orderNumber));
        $verified = session(self::SESSION_KEY, []);
        $verified[$orderNumber] = true;
        session([self::SESSION_KEY => $verified]);
    }

    public static function has(string $orderNumber): bool
    {
        $orderNumber = strtoupper(trim($orderNumber));

        return (bool) (session(self::SESSION_KEY, [])[$orderNumber] ?? false);
    }

    public static function matchesPhone(Order $order, string $phoneTail): bool
    {
        $expected = self::phoneTail($order->customer_phone);
        $given = preg_replace('/\D+/', '', $phoneTail) ?? '';

        return $expected !== '' && $given === $expected;
    }

    public static function phoneTail(?string $phone): string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone) ?? '';

        if (strlen($digits) < 4) {
            return $digits;
        }

        return substr($digits, -4);
    }

    public static function findAndVerify(string $orderNumber, string $phoneTail): ?Order
    {
        $order = Order::with('items')
            ->where('order_number', strtoupper(trim($orderNumber)))
            ->first();

        if (! $order || ! self::matchesPhone($order, $phoneTail)) {
            return null;
        }

        self::grant($order->order_number);

        return $order;
    }
}
