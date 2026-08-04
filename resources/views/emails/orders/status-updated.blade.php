<x-mail::message>
# Status Pesanan Diperbarui

Halo **{{ $order->customer_name }}**,

Status pesanan **{{ $order->order_number }}** telah diperbarui.

**Status sebelumnya:** {{ \App\Models\Order::STATUSES[$previousStatus] ?? $previousStatus }}  
**Status sekarang:** {{ $order->status_label }}  
**Total:** {{ $order->formatted_total }}

@if($order->hasShipmentInfo())
**Kurir:** {{ $order->shipping_courier }}
**Nomor Resi:** {{ $order->tracking_number }}
@endif

<x-mail::button :url="route('order.detail', $order->order_number)">
Lihat Detail Pesanan
</x-mail::button>

Gunakan nomor pesanan + 4 digit terakhir telepon untuk membuka detail.

Terima kasih,<br>
{{ config('app.name') }}
</x-mail::message>
