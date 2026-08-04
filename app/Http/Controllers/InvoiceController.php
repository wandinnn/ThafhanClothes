<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\Invoice\OrderInvoicePresenter;
use App\Support\OrderAccess;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    public function __invoke(string $order, OrderInvoicePresenter $presenter): View
    {
        $model = Order::query()
            ->where('order_number', strtoupper(trim($order)))
            ->firstOrFail();

        if (! OrderAccess::has($model->order_number)) {
            abort(403, 'Verifikasi nomor telepon di halaman pesanan terlebih dahulu.');
        }

        return view('invoices.order', $presenter->present($model));
    }
}
