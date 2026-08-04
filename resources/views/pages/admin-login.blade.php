<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Component;

new class extends Component {
    public string $email = '';

    public string $password = '';

    /**
     * Batas percobaan login sebelum dikunci sementara.
     */
    private const MAX_ATTEMPTS = 5;

    private const DECAY_SECONDS = 60;

    public function login()
    {
        $this->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $throttleKey = $this->throttleKey();

        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            $this->addError('email', "Terlalu banyak percobaan. Coba lagi dalam {$seconds} detik.");

            return;
        }

        if (Auth::attempt(['email' => $this->email, 'password' => $this->password], false)) {
            $user = Auth::user();

            if (! $user->is_admin) {
                Auth::logout();
                RateLimiter::hit($throttleKey, self::DECAY_SECONDS);
                $this->addError('email', 'Akses ditolak. Hanya admin yang dapat masuk.');

                return;
            }

            RateLimiter::clear($throttleKey);
            session()->regenerate();

            return redirect()->intended(route('admin.dashboard'));
        }

        RateLimiter::hit($throttleKey, self::DECAY_SECONDS);
        $this->addError('email', 'Email atau password salah.');
    }

    private function throttleKey(): string
    {
        return 'admin-login:'.Str::lower($this->email).'|'.request()->ip();
    }

    public function render()
    {
        return view('pages.admin-login')
            ->layout('layouts.admin-guest')
            ->title('Login Admin');
    }
};
?>

<div class="min-h-[80vh] flex items-center justify-center">
    <div class="w-full max-w-md animate-fade-up">
        <div class="text-center mb-8">
            <a wire:navigate href="{{ route('home') }}" class="text-3xl font-bold text-accent tracking-tight">
                ThafhanClothes
            </a>
            <p class="mt-2 text-sm text-ink">Masuk ke panel admin</p>
        </div>

        <div class="bg-white rounded-2xl shadow-sm p-8">
            <form wire:submit="login" class="space-y-5">
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1.5">Email</label>
                    <input wire:model="email" id="email" type="email" autocomplete="email"
                           class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition-all duration-200 text-sm @error('email') border-red-300 bg-red-50 @enderror"
                           placeholder="admin@thafhanclothes.test">
                    @error('email') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <div x-data="{ show: false }">
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1.5">Password</label>
                    <div class="relative">
                        <input wire:model="password" id="password" :type="show ? 'text' : 'password'" autocomplete="current-password"
                               class="w-full px-4 py-2.5 pr-11 rounded-xl border border-gray-200 focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition-all duration-200 text-sm @error('password') border-red-300 bg-red-50 @enderror"
                               placeholder="Masukkan password">
                        <button type="button"
                                @click="show = !show"
                                class="absolute inset-y-0 right-0 flex items-center px-3 text-ink hover:text-deep transition-opacity duration-200"
                                :aria-label="show ? 'Sembunyikan password' : 'Tampilkan password'">
                            <svg x-show="!show" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                            </svg>
                            <svg x-show="show" x-cloak xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
                            </svg>
                        </button>
                    </div>
                    @error('password') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <button type="submit"
                        class="admin-btn w-full py-2.5 px-4 text-sm"
                        style="background-color: var(--theme-deep); color: var(--theme-cream);">
                    Masuk
                </button>
            </form>
        </div>

        <p class="mt-6 text-center">
            <a wire:navigate href="{{ route('home') }}" class="text-sm text-ink hover:text-accent transition-colors inline-flex items-center gap-1">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                </svg>
                Kembali ke toko
            </a>
        </p>
    </div>
</div>

