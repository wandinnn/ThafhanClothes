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
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1.5">Password</label>
                    <input wire:model="password" id="password" type="password" autocomplete="current-password"
                           class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition-all duration-200 text-sm @error('password') border-red-300 bg-red-50 @enderror"
                           placeholder="Masukkan password">
                    @error('password') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <button type="submit"
                        class="w-full bg-accent hover:bg-secondary text-ink font-semibold py-2.5 px-4 rounded-xl transition-all duration-200 hover:-translate-y-0.5 active:translate-y-0 text-sm">
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

