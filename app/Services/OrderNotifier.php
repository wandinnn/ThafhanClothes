<?php

namespace App\Services;

use App\Mail\OrderPlacedMail;
use App\Mail\OrderShippedMail;
use App\Mail\OrderStatusUpdatedMail;
use App\Mail\PaymentProofReceivedMail;
use App\Mail\PaymentProofUploadedMail;
use App\Models\Order;
use App\Support\ShopSettings;
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
        if (! ShopSettings::mailEnabled()) {
            return;
        }

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
        if (! ShopSettings::mailEnabled()) {
            return;
        }

        $order->loadMissing('items');

        if (ShopSettings::mailPaymentProofCustomerEnabled() && filled($order->customer_email)) {
            $this->sendSafely(
                $order->customer_email,
                new PaymentProofReceivedMail($order),
                'payment_proof_customer',
                $order,
            );
        }

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
        if (! ShopSettings::mailEnabled() || ! ShopSettings::mailStatusEnabled()) {
            return;
        }

        if ($previousStatus === $order->status || blank($order->customer_email)) {
            return;
        }

        $order->loadMissing('items');

        if ($order->status === 'shipped' && $order->hasShipmentInfo()) {
            $this->shipmentUpdated($order);

            return;
        }

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
        if (! ShopSettings::mailEnabled() || ! ShopSettings::mailShipmentEnabled()) {
            return;
        }

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
