<?php

use App\Models\Coupon;
use App\Models\User;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->admin()->create());
});

function couponForm(string $code = 'HEMAT10'): Testable
{
    return Livewire::test('pages::admin-coupons')
        ->set('code', $code)
        ->set('type', 'fixed')
        ->set('value', 20000)
        ->set('min_order', 100000);
}

test('a new coupon is stored with an uppercase code', function () {
    couponForm('hemat10')->call('save');

    $coupon = Coupon::sole();

    expect($coupon->code)->toBe('HEMAT10')
        ->and($coupon->value)->toBe(20000)
        ->and($coupon->min_order)->toBe(100000)
        ->and($coupon->used_count)->toBe(0)
        ->and($coupon->is_active)->toBeTrue();
});

test('a duplicate coupon code is rejected', function () {
    Coupon::factory()->create(['code' => 'HEMAT10']);

    couponForm()->call('save')->assertHasErrors(['code' => 'unique']);

    expect(Coupon::count())->toBe(1);
});

test('a percentage coupon above one hundred percent is rejected', function () {
    couponForm()
        ->set('type', 'percent')
        ->set('value', 150)
        ->call('save')
        ->assertHasErrors(['value' => 'max']);

    expect(Coupon::count())->toBe(0);
});

test('a coupon code with symbols is rejected', function () {
    couponForm('HEMAT 10%')->call('save')->assertHasErrors(['code' => 'regex']);

    expect(Coupon::count())->toBe(0);
});

test('editing a coupon keeps its own code available', function () {
    $coupon = Coupon::factory()->create(['code' => 'HEMAT10', 'value' => 20000]);

    Livewire::test('pages::admin-coupons')
        ->call('edit', $coupon->id)
        ->set('value', 35000)
        ->call('save');

    expect($coupon->fresh()->code)->toBe('HEMAT10')
        ->and($coupon->fresh()->value)->toBe(35000);
});

test('toggling a coupon switches it off and on again', function () {
    $coupon = Coupon::factory()->create(['is_active' => true]);

    Livewire::test('pages::admin-coupons')
        ->call('toggleActive', $coupon->id);

    expect($coupon->fresh()->is_active)->toBeFalse();
});

test('deleting a coupon removes it from the list', function () {
    $coupon = Coupon::factory()->create(['code' => 'BUANG10']);

    Livewire::test('pages::admin-coupons')
        ->call('confirmDelete', $coupon->id)
        ->call('delete');

    expect(Coupon::count())->toBe(0);
});

test('the coupon list can be filtered by code', function () {
    Coupon::factory()->create(['code' => 'HEMAT10']);
    Coupon::factory()->create(['code' => 'GRATISONGKIR']);

    Livewire::test('pages::admin-coupons')
        ->set('search', 'HEMAT')
        ->assertSee('HEMAT10')
        ->assertDontSee('GRATISONGKIR');
});

test('the coupon page is closed to guests', function () {
    auth()->logout();

    $this->get(route('admin.coupons'))->assertRedirect(route('admin.login'));
});
