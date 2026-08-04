<x-mail::message>
# Status Pesanan Diperbarui

Halo **{{ $order->customer_name }}**,

Ada update untuk pesanan **{{ $order->order_number }}**.

**Status sebelumnya:** {{ \App\Models\Order::STATUSES[$previousStatus] ?? $previousStatus }}  
**Status sekarang:** {{ $order->status_label }}  
**Total:** {{ $order->formatted_total }}

{{ $order->customerStatusGuidance() }}

@if($order->hasShipmentInfo())
**Kurir:** {{ $order->shipping_courier }}  
**Nomor Resi:** {{ $order->tracking_number }}
@endif

<x-mail::button :url="route('order.detail', $order->order_number)">
Lihat Detail Pesanan
</x-mail::button>

<x-mail::button :url="route('track.order')">
Lacak Pesanan
</x-mail::button>

Gunakan nomor pesanan + 4 digit terakhir telepon untuk membuka detail.

Butuh bantuan? Chat WhatsApp: [wa.me/{{ \App\Support\ShopSettings::whatsapp() }}](https://wa.me/{{ \App\Support\ShopSettings::whatsapp() }}?text={{ urlencode('Halo ThafhanClothes, saya tanya pesanan '.$order->order_number) }})

Terima kasih,<br>
{{ config('app.name') }}
</x-mail::message>
