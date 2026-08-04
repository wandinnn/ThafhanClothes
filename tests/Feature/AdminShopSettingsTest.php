<?php

use App\Models\Order;
use App\Models\User;
use App\Support\OrderAccess;
use App\Support\ShopSettings;
use Livewire\Livewire;

beforeEach(function () {
    ShopSettings::forgetCache();
    $this->actingAs(User::factory()->admin()->create());
});

test('guests cannot open shop settings', function () {
    auth()->logout();

    $this->get(route('admin.settings'))->assertRedirect();
});

test('admin can save shop settings and they apply to bank helpers', function () {
    Livewire::test('pages::admin-settings')
        ->set('bankName', 'BCA')
        ->set('bankAccount', '1234567890')
        ->set('bankHolder', 'NAUFAL THAFHAN')
        ->set('whatsapp', '6281111222333')
        ->set('flashSaleEndsAt', '2026-12-31T23:59')
        ->set('mailEnabled', true)
        ->set('mailStatus', false)
        ->set('mailShipment', true)
        ->set('mailPaymentProof', true)
        ->set('paymentDriver', 'manual')
        ->set('aboutTitle', 'Judul About')
        ->set('aboutBody', 'Isi about toko.')
        ->set('faqRows', [
            ['q' => 'Pertanyaan 1?', 'a' => 'Jawaban 1.'],
        ])
        ->call('save')
        ->assertHasNoErrors();

    expect(ShopSettings::bank())->toMatchArray([
        'name' => 'BCA',
        'account' => '1234567890',
        'holder' => 'NAUFAL THAFHAN',
    ])
        ->and(ShopSettings::whatsapp())->toBe('6281111222333')
        ->and(ShopSettings::flashSaleEndsAt())->not->toBeNull()
        ->and(ShopSettings::mailStatusEnabled())->toBeFalse()
        ->and(ShopSettings::mailShipmentEnabled())->toBeTrue()
        ->and(ShopSettings::paymentDriver())->toBe('manual')
        ->and(ShopSettings::aboutTitle())->toBe('Judul About')
        ->and(ShopSettings::faqItems()[0]['q'])->toBe('Pertanyaan 1?');
});

test('admin can switch payment driver to midtrans from settings', function () {
    Livewire::test('pages::admin-settings')
        ->set('bankName', 'BCA')
        ->set('bankAccount', '1234567890')
        ->set('bankHolder', 'NAUFAL THAFHAN')
        ->set('whatsapp', '6281111222333')
        ->set('paymentDriver', 'midtrans')
        ->set('aboutTitle', 'About')
        ->set('aboutBody', 'Body about.')
        ->set('faqRows', [['q' => 'Q?', 'a' => 'A.']])
        ->call('save')
        ->assertHasNoErrors();

    expect(ShopSettings::paymentDriver())->toBe('midtrans');
});

test('payment page shows bank account from shop settings', function () {
    ShopSettings::putMany([
        ShopSettings::KEY_BANK_NAME => 'SeaBank Test',
        ShopSettings::KEY_BANK_ACCOUNT => '999888777',
        ShopSettings::KEY_BANK_HOLDER => 'THAFHAN TEST',
    ]);

    $order = Order::create([
        'order_number' => 'SET'.fake()->unique()->numerify('######'),
        'customer_name' => 'Pembeli',
        'customer_email' => 'a@example.com',
        'customer_phone' => '081324825060',
        'city' => 'Bandung',
        'address' => 'Jl Test',
        'subtotal' => 100000,
        'discount' => 0,
        'shipping_cost' => 0,
        'total' => 100000,
        'status' => 'pending_payment',
    ]);

    OrderAccess::grant($order->order_number);
    auth()->logout();

    $this->get(route('payment', $order->order_number))
        ->assertSuccessful()
        ->assertSee('999888777')
        ->assertSee('THAFHAN TEST');
});
