<x-mail::message>
# Pesanan Sedang Dikirim

Halo **{{ $order->customer_name }}**,

Pesanan **{{ $order->order_number }}** sudah diserahkan ke kurir dan sedang dalam perjalanan.

**Kurir:** {{ $order->shipping_courier }}
**Nomor Resi:** {{ $order->tracking_number }}
**Tujuan:** {{ $order->city }}

<x-mail::button :url="route('track.order')">
Lacak Pesanan
</x-mail::button>

Gunakan nomor pesanan + 4 digit terakhir telepon untuk membuka detail pesanan.

Terima kasih,<br>
{{ config('app.name') }}
</x-mail::message>
