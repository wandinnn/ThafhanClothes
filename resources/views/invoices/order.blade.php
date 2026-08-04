<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Invoice {{ $order->order_number }} - {{ $shopName }}</title>
    <style>
        body { font-family: Georgia, 'Times New Roman', serif; color: #1f2937; margin: 0; background: #f8fafc; }
        .sheet { max-width: 800px; margin: 24px auto; background: #fff; padding: 32px; border: 1px solid #e5e7eb; }
        h1 { margin: 0; font-size: 28px; color: #2C3947; }
        .muted { color: #6b7280; font-size: 14px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { text-align: left; padding: 10px 8px; border-bottom: 1px solid #e5e7eb; font-size: 14px; }
        th { background: #f3f4f6; }
        .totals td { border: 0; }
        .totals .label { text-align: right; color: #6b7280; }
        .totals .value { text-align: right; font-weight: bold; }
        .actions { margin: 16px auto; max-width: 800px; display: flex; gap: 8px; }
        .btn { display: inline-block; padding: 10px 16px; background: #2C3947; color: #f5f0e6; text-decoration: none; border-radius: 999px; font-size: 14px; border: 0; cursor: pointer; }
        @media print {
            .actions { display: none; }
            body { background: #fff; }
            .sheet { border: 0; margin: 0; max-width: none; }
        }
    </style>
</head>
<body>
    <div class="actions">
        <button class="btn" onclick="window.print()">Cetak / Simpan PDF</button>
        <a class="btn" href="{{ route('order.detail', $order->order_number) }}">Kembali ke Pesanan</a>
    </div>

    <div class="sheet">
        <div style="display:flex; justify-content:space-between; gap:16px; flex-wrap:wrap;">
            <div>
                <h1>{{ $shopName }}</h1>
                <p class="muted">Invoice pesanan fashion</p>
            </div>
            <div style="text-align:right;">
                <p style="margin:0; font-size:18px; font-weight:bold;">{{ strtoupper($order->order_number) }}</p>
                <p class="muted" style="margin:4px 0 0;">Diterbitkan {{ $issuedAt->format('d M Y H:i') }}</p>
                <p class="muted" style="margin:4px 0 0;">Status: {{ $order->status_label }}</p>
            </div>
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-top:24px;">
            <div>
                <p style="margin:0; font-weight:bold;">Ditagihkan kepada</p>
                <p style="margin:6px 0 0;">{{ $order->customer_name }}</p>
                <p class="muted" style="margin:4px 0 0;">{{ $order->customer_email }}</p>
                <p class="muted" style="margin:4px 0 0;">{{ $order->customer_phone }}</p>
            </div>
            <div>
                <p style="margin:0; font-weight:bold;">Alamat pengiriman</p>
                <p style="margin:6px 0 0;">{{ $order->address }}</p>
                <p class="muted" style="margin:4px 0 0;">{{ $order->city }}</p>
                @if($order->shipping_service)
                    <p class="muted" style="margin:4px 0 0;">{{ $order->shipping_service }} · {{ $order->shipping_etd }}</p>
                @endif
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Produk</th>
                    <th>Varian</th>
                    <th>Qty</th>
                    <th>Harga</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $item)
                    <tr>
                        <td>{{ $item->product_name }}</td>
                        <td>{{ trim(($item->selected_size ?? '').' / '.($item->selected_color ?? ''), ' /') ?: '-' }}</td>
                        <td>{{ $item->quantity }}</td>
                        <td>Rp{{ number_format($item->product_price, 0, ',', '.') }}</td>
                        <td>Rp{{ number_format($item->subtotal, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <table class="totals" style="margin-top:8px;">
            <tr>
                <td class="label">Subtotal</td>
                <td class="value">Rp{{ number_format($order->subtotal, 0, ',', '.') }}</td>
            </tr>
            @if($order->discount > 0)
                <tr>
                    <td class="label">Diskon</td>
                    <td class="value">-Rp{{ number_format($order->discount, 0, ',', '.') }}</td>
                </tr>
            @endif
            <tr>
                <td class="label">Ongkir</td>
                <td class="value">Rp{{ number_format($order->shipping_cost, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="label" style="font-size:16px; color:#111;">Total</td>
                <td class="value" style="font-size:16px;">{{ $order->formatted_total }}</td>
            </tr>
        </table>

        <div style="margin-top:28px; padding-top:16px; border-top:1px solid #e5e7eb;">
            <p style="margin:0; font-weight:bold;">Pembayaran</p>
            <p class="muted" style="margin:6px 0 0;">
                Metode: {{ $order->payment_method ?: '-' }}
                · Gateway: {{ $order->payment_gateway ?: 'manual' }}
            </p>
            <p class="muted" style="margin:4px 0 0;">
                Transfer ke {{ $bank['name'] }} {{ $bank['account'] }} a.n. {{ $bank['holder'] }}
            </p>
        </div>
    </div>
</body>
</html>
