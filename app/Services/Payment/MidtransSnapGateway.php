<?php

namespace App\Services\Payment;

use App\Contracts\PaymentGateway;
use App\Data\PaymentSession;
use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Integrasi Snap Midtrans lewat HTTP. Hanya aktif jika server key terisi.
 * Tanpa key, createSession melempar exception yang jelas (jangan dipakai sebagai default).
 */
class MidtransSnapGateway implements PaymentGateway
{
    public function driver(): string
    {
        return 'midtrans';
    }

    public function supportsProofUpload(): bool
    {
        return false;
    }

    public function supportsInstantPay(): bool
    {
        return true;
    }

    public function createSession(Order $order): PaymentSession
    {
        $order->loadMissing('items');

        $serverKey = (string) config('shop.payment.midtrans.server_key');
        $clientKey = (string) config('shop.payment.midtrans.client_key');

        if ($serverKey === '') {
            throw new RuntimeException('MIDTRANS_SERVER_KEY belum diisi. Pakai PAYMENT_DRIVER=manual atau fake untuk lokal.');
        }

        $isProduction = (bool) config('shop.payment.midtrans.is_production');
        $baseUrl = $isProduction
            ? 'https://app.midtrans.com'
            : 'https://app.sandbox.midtrans.com';

        $payload = [
            'transaction_details' => [
                'order_id' => $order->order_number,
                'gross_amount' => (int) $order->total,
            ],
            'customer_details' => [
                'first_name' => $order->customer_name,
                'email' => $order->customer_email,
                'phone' => $order->customer_phone,
            ],
            'item_details' => $this->itemDetails($order),
            'callbacks' => [
                'finish' => route('order.success', ['order' => $order->order_number]),
            ],
        ];

        $response = Http::withBasicAuth($serverKey, '')
            ->acceptJson()
            ->post("{$baseUrl}/snap/v1/transactions", $payload);

        if (! $response->successful()) {
            throw new RuntimeException('Gagal membuat sesi Midtrans: '.$response->body());
        }

        $token = (string) $response->json('token');
        $redirectUrl = (string) $response->json('redirect_url');

        $order->forceFill([
            'payment_gateway' => 'midtrans',
            'payment_transaction_id' => $token,
            'payment_payload' => $response->json(),
        ])->save();

        // Snap.js popup jika client key ada; jika tidak, redirect ke halaman Midtrans.
        $mode = filled($clientKey) ? 'snap' : 'redirect';

        return new PaymentSession(
            mode: $mode,
            redirectUrl: $redirectUrl,
            transactionId: $token,
            meta: [
                'token' => $token,
                'client_key' => $clientKey,
                'is_production' => $isProduction,
            ],
        );
    }

    public function settle(Order $order, ?string $transactionId = null): Order
    {
        return app(OrderPaymentSettler::class)->settle(
            $order,
            'midtrans',
            $transactionId ?: $order->payment_transaction_id,
            'midtrans',
        );
    }

    /**
     * Detail item wajib jumlahnya sama dengan gross_amount (aturan Midtrans).
     *
     * @return list<array{id: string, price: int, quantity: int, name: string}>
     */
    private function itemDetails(Order $order): array
    {
        $items = $order->items->map(fn ($item): array => [
            'id' => (string) ($item->product_id ?: $item->id),
            'price' => (int) $item->product_price,
            'quantity' => (int) $item->quantity,
            'name' => Str::limit((string) $item->product_name, 50, ''),
        ])->values()->all();

        if ((int) $order->shipping_cost > 0) {
            $items[] = [
                'id' => 'SHIPPING',
                'price' => (int) $order->shipping_cost,
                'quantity' => 1,
                'name' => 'Ongkir',
            ];
        }

        if ((int) $order->discount > 0) {
            $items[] = [
                'id' => 'DISCOUNT',
                'price' => -1 * (int) $order->discount,
                'quantity' => 1,
                'name' => Str::limit('Diskon '.($order->coupon_code ?: ''), 50, ''),
            ];
        }

        $sum = collect($items)->sum(fn (array $row): int => $row['price'] * $row['quantity']);

        if ($sum !== (int) $order->total) {
            return [[
                'id' => 'ORDER-'.$order->order_number,
                'price' => (int) $order->total,
                'quantity' => 1,
                'name' => Str::limit('Pesanan '.$order->order_number, 50, ''),
            ]];
        }

        return $items;
    }
}
