<?php

namespace App\Services;

use App\Mail\OrderPlacedMail;
use App\Mail\OrderShippedMail;
use App\Mail\OrderStatusUpdatedMail;
use App\Mail\PaymentProofUploadedMail;
use App\Models\Order;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Mengirim email terkait pesanan. Kegagalan pengiriman dicatat ke log
 * tetapi tidak menggagalkan transaksi order (penting saat Resend
 * masih memakai onboarding@resend.dev yang hanya boleh kirim ke akun sendiri).
 */
class OrderNotifier
{
    public function orderPlaced(Order $order): void
    {
        $order->loadMissing('items');

        if (filled($order->customer_email)) {
            $this->sendSafely(
                $order->customer_email,
                new OrderPlacedMail($order),
                'order_placed_customer',
                $order,
            );
        }

        $adminEmail = config('app.admin_email');

        if (filled($adminEmail)) {
            $this->sendSafely(
                $adminEmail,
                new OrderPlacedMail($order),
                'order_placed_admin',
                $order,
            );
        }
    }

    public function paymentProofUploaded(Order $order): void
    {
        $order->loadMissing('items');
        $adminEmail = config('app.admin_email');

        if (! filled($adminEmail)) {
            return;
        }

        $this->sendSafely(
            $adminEmail,
            new PaymentProofUploadedMail($order),
            'payment_proof_admin',
            $order,
        );
    }

    public function statusUpdated(Order $order, string $previousStatus): void
    {
        if ($previousStatus === $order->status || blank($order->customer_email)) {
            return;
        }

        $order->loadMissing('items');

        $this->sendSafely(
            $order->customer_email,
            new OrderStatusUpdatedMail($order, $previousStatus),
            'status_updated_customer',
            $order,
        );
    }

    /**
     * Beritahu pembeli begitu nomor resi tersedia.
     */
    public function shipmentUpdated(Order $order): void
    {
        if (! $order->hasShipmentInfo() || blank($order->customer_email)) {
            return;
        }

        $order->loadMissing('items');

        $this->sendSafely(
            $order->customer_email,
            new OrderShippedMail($order),
            'shipment_updated_customer',
            $order,
        );
    }

    private function sendSafely(string $to, object $mailable, string $context, Order $order): void
    {
        try {
            Mail::to($to)->send($mailable);
        } catch (Throwable $e) {
            Log::warning('Gagal mengirim email pesanan.', [
                'context' => $context,
                'order_number' => $order->order_number,
                'to' => $to,
                'exception' => $e->getMessage(),
            ]);
        }
    }
}
