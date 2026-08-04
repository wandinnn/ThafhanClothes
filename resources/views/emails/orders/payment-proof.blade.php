<x-mail::message>
# Bukti Pembayaran Masuk

Ada bukti pembayaran baru untuk pesanan **{{ $order->order_number }}**.

**Pelanggan:** {{ $order->customer_name }}  
**Telepon:** {{ $order->customer_phone }}  
**Total:** {{ $order->formatted_total }}  
**Metode:** {{ strtoupper($order->payment_method ?? '-') }}  
**Status:** {{ $order->status_label }}

<x-mail::button :url="route('admin.orders')">
Buka Panel Pesanan
</x-mail::button>

{{ config('app.name') }}
</x-mail::message>
