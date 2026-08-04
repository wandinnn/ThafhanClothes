<?php

use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

test('an administrator can sign in', function () {
    User::factory()->admin()->create(['email' => 'admin@thafhanclothes.test']);

    Livewire::test('pages::admin-login')
        ->set('email', 'admin@thafhanclothes.test')
        ->set('password', 'password')
        ->call('login')
        ->assertRedirect(route('admin.dashboard'));

    expect(auth()->check())->toBeTrue();
});

test('a user without admin rights is signed out again', function () {
    User::factory()->create(['email' => 'pembeli@thafhanclothes.test']);

    Livewire::test('pages::admin-login')
        ->set('email', 'pembeli@thafhanclothes.test')
        ->set('password', 'password')
        ->call('login')
        ->assertHasErrors('email')
        ->assertNoRedirect();

    expect(auth()->check())->toBeFalse();
});

test('repeated failed sign ins are locked out for a while', function () {
    User::factory()->admin()->create(['email' => 'admin@thafhanclothes.test']);

    $component = Livewire::test('pages::admin-login')
        ->set('email', 'admin@thafhanclothes.test')
        ->set('password', 'salah-password');

    foreach (range(1, 5) as $ignored) {
        $component->call('login');
    }

    $component->call('login');

    expect($component->errors()->first('email'))->toContain('Coba lagi dalam');
});

test('a successful sign in clears the earlier failed attempts', function () {
    User::factory()->admin()->create(['email' => 'admin@thafhanclothes.test']);

    $component = Livewire::test('pages::admin-login')
        ->set('email', 'admin@thafhanclothes.test')
        ->set('password', 'salah-password')
        ->call('login');

    $component->set('password', 'password')->call('login');

    expect(RateLimiter::attempts('admin-login:admin@thafhanclothes.test|127.0.0.1'))->toBe(0);
});
