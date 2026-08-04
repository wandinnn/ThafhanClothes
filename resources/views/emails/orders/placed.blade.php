<x-mail::message>
# Pesanan Berhasil Dibuat

Halo **{{ $order->customer_name }}**,

Terima kasih sudah berbelanja di **ThafhanClothes**. Pesanan Anda sudah kami terima.

**Nomor pesanan:** {{ $order->order_number }}  
**Total:** {{ $order->formatted_total }}  
**Status:** {{ $order->status_label }}

## Ringkasan Item
@foreach ($order->items as $item)
- {{ $item->product_name }} ({{ $item->selected_size }} · {{ $item->selected_color }}) × {{ $item->quantity }} — Rp{{ number_format($item->subtotal, 0, ',', '.') }}
@endforeach

<x-mail::button :url="route('payment', $order->order_number)">
Lanjut ke Pembayaran
</x-mail::button>

Atau lacak pesanan:

<x-mail::button :url="route('track.order')">
Lacak Pesanan
</x-mail::button>

Gunakan nomor pesanan + 4 digit terakhir telepon saat melacak.

Terima kasih,<br>
{{ config('app.name') }}
</x-mail::message>
