<x-mail::message>
# Pesanan Sedang Dikirim

Halo **{{ $order->customer_name }}**,

Pesanan **{{ $order->order_number }}** sudah diserahkan ke kurir dan sedang dalam perjalanan.

**Kurir:** {{ $order->shipping_courier }}  
**Nomor Resi:** {{ $order->tracking_number }}  
**Tujuan:** {{ $order->city }}  
**Total:** {{ $order->formatted_total }}

Simpan nomor resi ini untuk melacak paket di situs kurir.

<x-mail::button :url="route('order.detail', $order->order_number)">
Lihat Detail & Resi
</x-mail::button>

<x-mail::button :url="route('track.order')">
Lacak Pesanan
</x-mail::button>

Gunakan nomor pesanan + 4 digit terakhir telepon untuk membuka detail.

Butuh bantuan? Chat WhatsApp: [wa.me/{{ \App\Support\ShopSettings::whatsapp() }}](https://wa.me/{{ \App\Support\ShopSettings::whatsapp() }}?text={{ urlencode('Halo ThafhanClothes, saya tanya pengiriman pesanan '.$order->order_number) }})

Terima kasih,<br>
{{ config('app.name') }}
</x-mail::message>
