@extends('layouts.app')

@section('title', 'Halaman Tidak Ditemukan')

@section('content')
<div class="min-h-[70vh] flex items-center justify-center px-4">
    <div class="text-center max-w-md">
        <div class="text-8xl sm:text-9xl font-bold text-primary/30 mb-4">404</div>
        <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 mb-3">Halaman Tidak Ditemukan</h1>
        <p class="text-ink mb-8 leading-relaxed">
            Maaf, halaman yang kamu cari tidak ditemukan atau mungkin sudah dipindahkan.
        </p>
        <div class="flex flex-col sm:flex-row gap-3 justify-center">
            <a wire:navigate href="{{ route('home') }}"
               class="inline-flex items-center justify-center gap-2 bg-accent hover:bg-secondary text-ink font-semibold px-8 py-3 rounded-full transition-all duration-200 hover:shadow-lg">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                </svg>
                Kembali ke Beranda
            </a>
            <a wire:navigate href="{{ route('products') }}"
               class="inline-flex items-center justify-center gap-2 bg-panel border-2 border-accent text-accent font-semibold px-8 py-3 rounded-full transition-all duration-200 hover:bg-accent hover:text-ink">
                Lihat Produk
            </a>
        </div>
    </div>
</div>
@endsection

