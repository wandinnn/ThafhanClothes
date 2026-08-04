<x-mail::message>
# Bukti Pembayaran Diterima

Halo **{{ $order->customer_name }}**,

Bukti pembayaran untuk pesanan **{{ $order->order_number }}** sudah kami terima.

**Total:** {{ $order->formatted_total }}  
**Status sekarang:** {{ $order->status_label }}

{{ $order->customerStatusGuidance() }}

Kami akan kabari lagi lewat email setelah pembayaran dikonfirmasi.

<x-mail::button :url="route('order.detail', $order->order_number)">
Lihat Detail Pesanan
</x-mail::button>

Gunakan nomor pesanan + 4 digit terakhir telepon untuk membuka detail.

Terima kasih,<br>
{{ config('app.name') }}
</x-mail::message>
