<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\OrderNotifier;
use App\Services\Payment\OrderPaymentSettler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentWebhookController extends Controller
{
    /**
     * Webhook Midtrans (dan payload uji lokal tanpa signature jika server key kosong).
     */
    public function midtrans(Request $request, OrderPaymentSettler $settler, OrderNotifier $notifier): JsonResponse
    {
        $orderId = (string) $request->input('order_id', '');
        $status = (string) $request->input('transaction_status', $request->input('status', ''));
        $fraud = (string) $request->input('fraud_status', 'accept');
        $signature = (string) $request->input('signature_key', '');
        $statusCode = (string) $request->input('status_code', '');
        $grossAmount = (string) $request->input('gross_amount', '');

        $order = Order::with('items')->where('order_number', $orderId)->first();

        if (! $order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        $serverKey = (string) config('shop.payment.midtrans.server_key');

        if ($serverKey !== '') {
            if ($signature === '') {
                Log::warning('Midtrans webhook missing signature.', ['order' => $orderId]);

                return response()->json(['message' => 'Missing signature'], 403);
            }

            $expected = hash('sha512', $orderId.$statusCode.$grossAmount.$serverKey);

            if (! hash_equals($expected, $signature)) {
                Log::warning('Midtrans webhook signature mismatch.', ['order' => $orderId]);

                return response()->json(['message' => 'Invalid signature'], 403);
            }
        }

        if ($grossAmount !== '' && (int) $grossAmount !== (int) $order->total) {
            Log::warning('Midtrans webhook amount mismatch.', [
                'order' => $orderId,
                'expected' => $order->total,
                'got' => $grossAmount,
            ]);

            return response()->json(['message' => 'Amount mismatch'], 422);
        }

        $paidStatuses = ['capture', 'settlement', 'success'];
        if (! in_array($status, $paidStatuses, true) || ($status === 'capture' && $fraud !== 'accept')) {
            return response()->json(['message' => 'Ignored', 'status' => $status]);
        }

        if (in_array($order->status, ['confirmed', 'processing', 'shipped', 'delivered'], true)) {
            return response()->json(['message' => 'Already paid']);
        }

        $previous = $order->status;
        $settled = $settler->settle(
            $order,
            'midtrans',
            (string) $request->input('transaction_id', $order->payment_transaction_id),
            'midtrans',
        );
        $notifier->statusUpdated($settled, $previous);

        return response()->json(['message' => 'OK']);
    }
}
