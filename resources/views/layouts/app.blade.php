<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth" style="font-family: 'Times New Roman', Times, serif;">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'ThafhanClothes') - ThafhanClothes</title>
    <link rel="icon" href="/favicon.ico" sizes="any">
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
    @livewireStyles
</head>
<body class="bg-[#FFF6DE] text-[#1F2A2A] antialiased" style="font-family: 'Times New Roman', Times, serif;">

    {{-- ======================== NAVBAR ======================== --}}
    <header>
        {{-- Top bar --}}
        <div class="bg-[#8BDFDD] py-1.5 px-4 text-center text-xs text-[#1F2A2A]/90 hidden sm:flex items-center justify-between max-w-7xl mx-auto">
            <div class="flex items-center gap-1">
                <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5 text-[#1F2A2A]" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"/>
                </svg>
                <span>Indonesia</span>
            </div>
            <span class="text-[#1F2A2A] font-medium tracking-wide">Belanja Nyaman Kualitas Aman</span>
            <a href="https://wa.me/6281324825060" target="_blank" rel="noopener noreferrer"
               class="flex items-center gap-1 text-[#F48F68] hover:text-[#D96B4B] transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
                    <path d="M12 0C5.373 0 0 5.373 0 12c0 2.122.555 4.112 1.523 5.837L.057 23.882l6.197-1.624A11.945 11.945 0 0 0 12 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 22c-1.89 0-3.663-.5-5.197-1.373l-.373-.22-3.678.964.98-3.584-.243-.392A9.956 9.956 0 0 1 2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10z"/>
                </svg>
                <span>WA Kami</span>
            </a>
        </div>

        {{-- Main navbar --}}
        <nav class="bg-[#8BDFDD] shadow-lg sticky top-0 z-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center h-16 gap-6">
                    {{-- Logo --}}
                    <a href="{{ route('home') }}"
                       class="text-2xl font-bold tracking-tight shrink-0"
                       style="color: #FFF6DE; font-family: Georgia, serif; letter-spacing: 0.05em;">
                        ThafhanClothes
                    </a>

                    {{-- Search Autocomplete --}}
                    @livewire('search-bar')

                    {{-- Nav links --}}
                    <div class="hidden md:flex items-center gap-6">
                        <a href="{{ route('home') }}"
                           class="nav-link text-sm font-medium text-[#1F2A2A]/80 hover:text-[#1F2A2A] {{ request()->routeIs('home') ? 'active text-[#1F2A2A]' : '' }}">
                            Beranda
                        </a>
                        <a href="{{ route('products') }}"
                           class="nav-link text-sm font-medium text-[#1F2A2A]/80 hover:text-[#1F2A2A] {{ request()->routeIs('products') ? 'active text-[#1F2A2A]' : '' }}">
                            Produk
                        </a>
                        <a href="{{ route('about') }}"
                           class="nav-link text-sm font-medium text-[#1F2A2A]/80 hover:text-[#1F2A2A] {{ request()->routeIs('about') ? 'active text-[#1F2A2A]' : '' }}">
                            Tentang Kami
                        </a>
                        <a href="{{ route('how-to-shop') }}"
                           class="nav-link text-sm font-medium text-[#1F2A2A]/80 hover:text-[#1F2A2A] {{ request()->routeIs('how-to-shop') ? 'active text-[#1F2A2A]' : '' }}">
                            Cara Belanja
                        </a>
                        <a href="{{ route('track.order') }}"
                           class="nav-link text-sm font-medium text-[#1F2A2A]/80 hover:text-[#1F2A2A] {{ request()->routeIs('track.order') ? 'active text-[#1F2A2A]' : '' }}">
                            Lacak Pesanan
                        </a>
                    </div>

                    {{-- Cart --}}
                    <div class="ml-auto flex items-center gap-3">
                        @livewire('cart-badge')
                        <button id="mobile-menu-btn" class="md:hidden p-1.5 text-[#1F2A2A]/80 hover:text-[#1F2A2A] transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="h-0.5 bg-[#FFF6DE]/40 -mx-4 sm:-mx-6 lg:-mx-8"></div>

                {{-- Mobile menu --}}
                <div id="mobile-menu" class="hidden md:hidden pb-4">
                    <form action="{{ route('products') }}" method="GET" class="pt-3 pb-2 sm:hidden">
                        <div class="relative">
                            <input type="text" name="search" placeholder="Cari produk..."
                                   class="w-full h-9 pl-4 pr-10 rounded-md bg-[#8BDFDD] text-sm text-[#1F2A2A] placeholder-[#1F2A2A]/60 outline-none focus:ring-2 focus:ring-[#FFF6DE]/60">
                            <button type="submit"
                                    class="absolute inset-y-0 right-0 w-10 flex items-center justify-center bg-[#FFF6DE] text-[#1F2A2A] rounded-r-md">
                                <svg xmlns="http://www.w3.org/2000/svg" class="size-4 text-[#4F252E]" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
                                </svg>
                            </button>
                        </div>
                    </form>
                    <div class="flex flex-col gap-1 pt-1">
                        <a href="{{ route('home') }}" class="text-sm text-[#1F2A2A]/80 hover:text-[#1F2A2A] px-1 py-2 transition-colors border-b border-[#FFF6DE]/10">Beranda</a>
                        <a href="{{ route('products') }}" class="text-sm text-[#1F2A2A]/80 hover:text-[#1F2A2A] px-1 py-2 transition-colors border-b border-[#FFF6DE]/10">Produk</a>
                        <a href="{{ route('cart') }}" class="text-sm text-[#1F2A2A]/80 hover:text-[#1F2A2A] px-1 py-2 transition-colors border-b border-[#FFF6DE]/10">Keranjang</a>
                        <a href="{{ route('about') }}" class="text-sm text-[#1F2A2A]/80 hover:text-[#1F2A2A] px-1 py-2 transition-colors border-b border-[#FFF6DE]/10">Tentang Kami</a>
                        <a href="{{ route('how-to-shop') }}" class="text-sm text-[#1F2A2A]/80 hover:text-[#1F2A2A] px-1 py-2 transition-colors border-b border-[#FFF6DE]/10">Cara Belanja</a>
                        <a href="{{ route('track.order') }}" class="text-sm text-[#1F2A2A]/80 hover:text-[#1F2A2A] px-1 py-2 transition-colors">Lacak Pesanan</a>
                    </div>
                </div>
            </div>
        </nav>
    </header>

    {{-- ======================== MAIN CONTENT ======================== --}}
    <main>
        {{ $slot ?? '' }}
        @yield('content')
    </main>

    {{-- ======================== FOOTER ======================== --}}
    <footer class="bg-[#8BDFDD] text-[#1F2A2A]/80 mt-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
                <div class="lg:col-span-1">
                    <a href="{{ route('home') }}" class="text-2xl font-bold text-[#1F2A2A] tracking-wide" style="font-family: Georgia, serif;">
                        ThafhanClothes
                    </a>
                    <p class="mt-3 text-sm text-[#4F252E] leading-relaxed">
                        Toko fashion premium untuk gaya terbaikmu. Temukan koleksi pakaian, aksesoris, dan sepatu terbaru.
                    </p>
                    <a href="https://wa.me/6281324825060" target="_blank" rel="noopener noreferrer"
                       class="inline-flex items-center gap-2 mt-4 bg-[#F48F68] hover:bg-[#D96B4B] text-[#1F2A2A] text-sm font-semibold px-5 py-2 rounded-full transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
                            <path d="M12 0C5.373 0 0 5.373 0 12c0 2.122.555 4.112 1.523 5.837L.057 23.882l6.197-1.624A11.945 11.945 0 0 0 12 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 22c-1.89 0-3.663-.5-5.197-1.373l-.373-.22-3.678.964.98-3.584-.243-.392A9.956 9.956 0 0 1 2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10z"/>
                        </svg>
                        Hubungi WA
                    </a>
                </div>
                <div>
                    <h4 class="text-sm font-semibold text-[#4F252E] uppercase tracking-wider mb-4">Menu</h4>
                    <ul class="space-y-2.5">
                        <li><a href="{{ route('home') }}" class="text-sm text-[#1F2A2A]/80 hover:text-[#1F2A2A] transition-colors">Beranda</a></li>
                        <li><a href="{{ route('products') }}" class="text-sm text-[#1F2A2A]/80 hover:text-[#1F2A2A] transition-colors">Semua Produk</a></li>
                        <li><a href="{{ route('about') }}" class="text-sm text-[#1F2A2A]/80 hover:text-[#1F2A2A] transition-colors">Tentang Kami</a></li>
                        <li><a href="{{ route('how-to-shop') }}" class="text-sm text-[#1F2A2A]/80 hover:text-[#1F2A2A] transition-colors">Cara Belanja</a></li>
                        <li><a href="{{ route('track.order') }}" class="text-sm text-[#1F2A2A]/80 hover:text-[#1F2A2A] transition-colors">Lacak Pesanan</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-sm font-semibold text-[#4F252E] uppercase tracking-wider mb-4">Kategori</h4>
                    <ul class="space-y-2.5">
                        <li><a href="{{ route('products', ['category' => 'kemeja-t-shirt']) }}" class="text-sm text-[#1F2A2A]/70 hover:text-[#1F2A2A] transition-colors">Kemeja & T-Shirt</a></li>
                        <li><a href="{{ route('products', ['category' => 'jaket-hoodie']) }}" class="text-sm text-[#1F2A2A]/70 hover:text-[#1F2A2A] transition-colors">Jaket & Hoodie</a></li>
                        <li><a href="{{ route('products', ['category' => 'aksesoris']) }}" class="text-sm text-[#1F2A2A]/70 hover:text-[#1F2A2A] transition-colors">Aksesoris</a></li>
                        <li><a href="{{ route('products', ['category' => 'sepatu']) }}" class="text-sm text-[#1F2A2A]/70 hover:text-[#1F2A2A] transition-colors">Sepatu</a></li>
                        <li><a href="{{ route('products', ['category' => 'celana']) }}" class="text-sm text-[#1F2A2A]/70 hover:text-[#1F2A2A] transition-colors">Celana</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-sm font-semibold text-[#4F252E] uppercase tracking-wider mb-4">Ikuti Kami</h4>
                    <div class="flex gap-3">
                        <a href="#" class="size-9 rounded-full bg-white/10 hover:bg-[#FFF6DE] flex items-center justify-center transition-colors group">
                            <svg class="size-4 text-[#4F252E] group-hover:text-[#4F252E]" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 1 0 0 12.324 6.162 6.162 0 0 0 0-12.324zM12 16a4 4 0 1 1 0-8 4 4 0 0 1 0 8zm6.406-11.845a1.44 1.44 0 1 0 0 2.881 1.44 1.44 0 0 0 0-2.881z"/>
                            </svg>
                        </a>
                        <a href="#" class="size-9 rounded-full bg-white/10 hover:bg-[#FFF6DE] flex items-center justify-center transition-colors group">
                            <svg class="size-4 text-[#4F252E] group-hover:text-[#4F252E]" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                            </svg>
                        </a>
                        <a href="#" class="size-9 rounded-full bg-white/10 hover:bg-[#FFF6DE] flex items-center justify-center transition-colors group">
                            <svg class="size-4 text-[#4F252E] group-hover:text-[#4F252E]" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-2.88 2.5 2.89 2.89 0 0 1-2.89-2.89 2.89 2.89 0 0 1 2.89-2.89c.28 0 .54.04.79.1V9.01a6.33 6.33 0 0 0-.79-.05 6.34 6.34 0 0 0-6.34 6.34 6.34 6.34 0 0 0 6.34 6.34 6.34 6.34 0 0 0 6.33-6.34V8.69a8.18 8.18 0 0 0 4.78 1.52V6.76a4.85 4.85 0 0 1-1.01-.07z"/>
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="border-t border-[#FFF6DE]/30">
            <div class="max-w-7xl mx-auto px-4 py-4 text-center text-xs text-[#1F2A2A]/70">
                &copy; {{ date('Y') }} ThafhanClothes. All rights reserved.
            </div>
        </div>
    </footer>

    {{-- Floating WhatsApp --}}
    <a href="https://wa.me/6281324825060?text=Halo%20ThafhanClothes%2C%20saya%20ingin%20bertanya!"
       target="_blank" rel="noopener noreferrer"
       class="fixed bottom-6 right-6 z-50 bg-[#F48F68] hover:bg-[#D96B4B] text-[#1F2A2A] rounded-full p-3.5 shadow-lg hover:shadow-xl transition-all duration-300 hover:scale-110 animate-fade-in">
        <svg xmlns="http://www.w3.org/2000/svg" class="size-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z"/>
        </svg>
    </a>

    {{-- Flash message --}}
    @if(session()->has('message'))
        <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 3000)"
             x-show="show"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 translate-y-2"
             class="fixed top-20 right-6 z-50 bg-[#8BDFDD] text-[#1F2A2A] px-5 py-3 rounded-xl shadow-lg text-sm font-medium border border-[#FFF6DE]/40">
            {{ session('message') }}
        </div>
    @endif

    {{-- ======================== ADD-TO-CART MODAL (FIXED) ======================== --}}
    <div id="add-to-cart-modal"
         style="display:none"
         class="fixed inset-0 z-[60] flex items-center justify-center bg-black/50 px-4 py-6 backdrop-blur-sm">
        <div id="add-to-cart-panel"
             class="bg-white rounded-2xl w-full max-w-sm shadow-2xl transform transition-all duration-200">
            {{-- Header --}}
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                <div class="flex items-center gap-3">
                    <img id="modal-product-thumb" src="" alt=""
                         class="w-12 h-12 rounded-xl object-cover border border-gray-100">
                    <div>
                        <h3 id="modal-product-name" class="text-sm font-bold text-gray-900 leading-tight max-w-[180px] truncate"></h3>
                        <p id="modal-product-price" class="text-sm font-bold text-[#1F2A2A] mt-0.5"></p>
                    </div>
                </div>
                <button type="button" onclick="closeAddToCart()"
                        class="p-1.5 text-[#4F252E] hover:text-[#4F252E] hover:bg-gray-100 rounded-lg transition-all">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Body --}}
            <div class="px-5 py-4 space-y-4">
                {{-- Ukuran --}}
                <div>
                    <p class="text-xs font-semibold text-[#4F252E] uppercase tracking-wider mb-2">Ukuran</p>
                    <div id="modal-sizes" class="flex flex-wrap gap-2"></div>
                </div>

                {{-- Warna --}}
                <div>
                    <p class="text-xs font-semibold text-[#4F252E] uppercase tracking-wider mb-2">Warna</p>
                    <div id="modal-colors" class="flex flex-wrap gap-2"></div>
                </div>

                {{-- Jumlah --}}
                <div>
                    <p class="text-xs font-semibold text-[#4F252E] uppercase tracking-wider mb-2">Jumlah</p>
                    <div class="flex items-center gap-3">
                        <button type="button" onclick="modalChangeQty(-1)"
                                class="w-9 h-9 rounded-full border border-[#FFF6DE]/50 text-[#1F2A2A] hover:border-[#FFF6DE] hover:text-[#1F2A2A] transition-all font-bold text-lg flex items-center justify-center">
                            −
                        </button>
                        <span id="modal-qty-display" class="w-8 text-center font-bold text-gray-900 text-base">1</span>
                        <input id="modal-quantity" type="hidden" value="1">
                        <button type="button" onclick="modalChangeQty(1)"
                                class="w-9 h-9 rounded-full border border-[#FFF6DE]/50 text-[#1F2A2A] hover:border-[#FFF6DE] hover:text-[#1F2A2A] transition-all font-bold text-lg flex items-center justify-center">
                            +
                        </button>
                    </div>
                </div>
            </div>

            {{-- Footer --}}
            <div class="px-5 pb-5">
                <button id="modal-add-btn" type="button" onclick="modalAddToCart()"
                        class="w-full bg-[#FFF6DE] hover:bg-[#F48F68] text-[#1F2A2A] font-bold py-3 rounded-xl transition-all duration-200 flex items-center justify-center gap-2 text-sm hover:-translate-y-0.5 active:scale-[0.98]">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z"/>
                    </svg>
                    <span id="modal-add-label">Tambah ke Cart</span>
                </button>
            </div>
        </div>
    </div>

    {{-- Toast --}}
      <div id="global-toast" style="display:none"
         class="fixed left-1/2 -translate-x-1/2 top-6 z-[70]">
        <div id="global-toast-inner"
             class="bg-[#8BDFDD] text-[#4F252E] px-6 py-3 rounded-xl shadow-2xl flex items-center gap-3 border border-[#FFF6DE]/30">
            <svg id="global-toast-icon" class="size-4 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path d="M9 12l2 2 4-4" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                <circle cx="12" cy="12" r="9" stroke-width="2"/>
            </svg>
            <span id="global-toast-text" class="text-sm font-medium">Produk ditambahkan ke keranjang</span>
        </div>
    </div>

    {{-- Base URL untuk JS --}}
    <script>
        window.__cartAddBase = '{{ url("/cart/add") }}';
        window.__csrfToken   = '{{ csrf_token() }}';
    </script>

    @verbatim
    <script>
        window.currentModalProduct = null;

        /* ─── Buka modal dari tombol di listing ─── */
        function openAddToCartFromData(el) {
            const product = {
                id:              el.dataset.productId,
                slug:            el.dataset.productSlug,   // ← WAJIB pakai slug
                name:            el.dataset.productName,
                price:           Number(el.dataset.productPrice || 0),
                formatted_price: el.dataset.productFormattedPrice || '',
                category:        el.dataset.productCategory || '',
                image:           el.dataset.productImage || '',
            };
            openAddToCart(product);
        }

        function openAddToCart(product) {
            try {
                window.currentModalProduct = product;

                document.getElementById('modal-product-name').textContent  = product.name;
                document.getElementById('modal-product-thumb').src         = product.image || '';
                document.getElementById('modal-product-price').textContent = product.formatted_price;
                document.getElementById('modal-quantity').value            = 1;
                document.getElementById('modal-qty-display').textContent   = 1;

                // Build sizes
                const cat  = (product.category || '').toLowerCase();
                const name = (product.name || '').toLowerCase();
                let sizes  = ['XS', 'S', 'M', 'L', 'XL', 'XXL'];
                
                if (cat.includes('celana'))                                      
                    sizes = ['28','30','32','34','36'];
                else if (cat.includes('sepatu'))                                  
                    sizes = ['38','39','40','41','42'];
                else if (cat.includes('aksesoris')) {
                    // Jam tangan: 40mm dan 44mm
                    if (name.includes('jam') || name.includes('watch'))
                        sizes = ['40mm', '44mm'];
                    // Ransel, Kacamata, dan tas lainnya: One Size
                    else if (name.includes('ransel') || name.includes('backpack') || 
                             name.includes('kacamata') || name.includes('glasses') || 
                             name.includes('sunglasses') || name.includes('tas') || 
                             name.includes('bag'))
                        sizes = ['One Size'];
                    // Default aksesoris: One Size
                    else
                        sizes = ['One Size'];
                }

                const sizesWrap = document.getElementById('modal-sizes');
                sizesWrap.innerHTML = '';
                sizes.forEach(s => {
                    const b         = document.createElement('button');
                    b.type          = 'button';
                    b.textContent   = s;
                    b.className     = 'size-pill px-4 py-2 rounded-xl text-sm font-semibold border-2 border-[#FFF6DE]/50 text-[#1F2A2A] hover:border-[#FFF6DE] hover:text-[#1F2A2A] transition-all cursor-pointer';
                    b.dataset.value = s;
                    b.onclick = function () {
                        Array.from(sizesWrap.querySelectorAll('.size-pill')).forEach(ch => {
                            ch.classList.remove('bg-[#FFF6DE]','text-[#1F2A2A]','border-[#FFF6DE]','font-bold');
                            ch.classList.add('border-gray-200','text-gray-700');
                            ch.dataset.selected = '';
                        });
                        b.classList.remove('border-gray-200','text-gray-700');
                        b.classList.add('bg-[#FFF6DE]','text-[#1F2A2A]','border-[#FFF6DE]','font-bold');
                        b.dataset.selected = '1';
                    };
                    sizesWrap.appendChild(b);
                });

                // Build colors
                const colorMap = [
                    { name:'Hitam',   hex:'#1a1a1a' },
                    { name:'Putih',   hex:'#f5f5f5', border:true },
                    { name:'Navy',    hex:'#1e3a5f' },
                    { name:'Coklat',  hex:'#7c5c3a' },
                    { name:'Merah',   hex:'#c0392b' },
                    { name:'Biru',    hex:'#2980b9' },
                    { name:'Abu-abu', hex:'#95a5a6' },
                ];
                const colorsWrap = document.getElementById('modal-colors');
                colorsWrap.innerHTML = '';
                colorMap.forEach(c => {
                    const b        = document.createElement('button');
                    b.type         = 'button';
                    b.title        = c.name;
                    b.className    = 'color-pill w-8 h-8 rounded-full border-2 border-transparent hover:scale-110 transition-all cursor-pointer';
                    b.style.backgroundColor = c.hex;
                    if (c.border) b.style.border = '2px solid #d1d5db';
                    b.dataset.colorName = c.name;
                    b.onclick = function () {
                        Array.from(colorsWrap.querySelectorAll('.color-pill')).forEach(ch => {
                            ch.style.outline      = 'none';
                            ch.style.outlineOffset = '0';
                            ch.dataset.selected   = '';
                        });
                        b.style.outline       = '3px solid #FFF6DE';
                        b.style.outlineOffset = '2px';
                        b.dataset.selected    = '1';
                    };
                    colorsWrap.appendChild(b);
                });

                // Show modal
                const modal = document.getElementById('add-to-cart-modal');
                const panel = document.getElementById('add-to-cart-panel');
                modal.style.display   = 'flex';
                panel.style.opacity   = '0';
                panel.style.transform = 'scale(0.95) translateY(8px)';
                setTimeout(() => {
                    panel.style.transition = 'transform 0.2s ease, opacity 0.2s ease';
                    panel.style.transform  = 'scale(1) translateY(0)';
                    panel.style.opacity    = '1';
                }, 10);

            } catch (e) { console.error('Modal error:', e); }
        }

        function closeAddToCart() {
            const modal = document.getElementById('add-to-cart-modal');
            const panel = document.getElementById('add-to-cart-panel');
            panel.style.transition = 'transform 0.15s ease, opacity 0.15s ease';
            panel.style.transform  = 'scale(0.95) translateY(8px)';
            panel.style.opacity    = '0';
            setTimeout(() => {
                modal.style.display  = 'none';
                panel.style.transition = '';
                panel.style.transform  = '';
                panel.style.opacity    = '';
            }, 150);
        }

        document.getElementById('add-to-cart-modal').addEventListener('click', function (e) {
            if (e.target === this) closeAddToCart();
        });

        function modalChangeQty(delta) {
            const input   = document.getElementById('modal-quantity');
            const display = document.getElementById('modal-qty-display');
            let v = Math.max(1, (parseInt(input.value) || 1) + delta);
            input.value         = v;
            display.textContent = v;
        }

        async function modalAddToCart() {
            const sizesWrap  = document.getElementById('modal-sizes');
            const colorsWrap = document.getElementById('modal-colors');

            const sizeBtn  = sizesWrap  ? Array.from(sizesWrap.querySelectorAll('.size-pill')).find(b => b.dataset.selected === '1')  : null;
            const colorBtn = colorsWrap ? Array.from(colorsWrap.querySelectorAll('.color-pill')).find(b => b.dataset.selected === '1') : null;

            // ─── VALIDASI UKURAN ───────────────────────────────────────────
            if (!sizeBtn) {
                showToast('Silahkan pilih ukuran terlebih dahulu.', 'warning');
                // Highlight size section
                sizesWrap.style.outline       = '2px solid #FFF6DE';
            }

            // ─── VALIDASI WARNA ────────────────────────────────────────────
            if (!colorBtn) {
                showToast('Silahkan pilih warna terlebih dahulu.', 'warning');
                colorsWrap.style.outline      = '2px solid #FFF6DE';
                colorsWrap.style.borderRadius = '12px';
                setTimeout(() => { colorsWrap.style.outline = ''; }, 2000);
                return;
            }

            const qty   = parseInt(document.getElementById('modal-quantity').value) || 1;
            const size  = sizeBtn.dataset.value  || sizeBtn.textContent.trim();
            const color = colorBtn.dataset.colorName || '';

            // ─── FETCH ────────────────────────────────────────────────────
            const btn   = document.getElementById('modal-add-btn');
            const label = document.getElementById('modal-add-label');
            btn.disabled      = true;
            label.textContent = 'Menambahkan...';

            try {
                const url = window.__cartAddBase + '/' + encodeURIComponent(window.currentModalProduct.slug);
                const res = await fetch(url, {
                    method:  'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': window.__csrfToken,
                        'Accept':       'application/json',
                    },
                    body: JSON.stringify({ quantity: qty, color: color, size: size }),
                });

                const data = await res.json();

                if (res.ok && data.success) {
                    closeAddToCart();
                    showToast('Produk berhasil ditambahkan ke keranjang!', 'success');
                    if (window.Livewire) {
                        try { window.Livewire.dispatch('cart-updated'); } catch (_) {}
                    }
                } else {
                    showToast('Gagal menambahkan produk. Coba lagi.', 'error');
                }
            } catch (err) {
                console.error('Cart error:', err);
                showToast('Terjadi kesalahan jaringan. Coba lagi.', 'error');
            } finally {
                btn.disabled      = false;
                label.textContent = 'Tambah ke Cart';
            }
        }

        /* ─── Toast dengan dukungan tipe ─── */
        function showToast(text, type) {
            const t     = document.getElementById('global-toast');
            const inner = document.getElementById('global-toast-inner');
            const icon  = document.getElementById('global-toast-icon');
            const txt   = document.getElementById('global-toast-text');

            txt.textContent = text;

            // Style berdasarkan tipe
            const styles = {
                success: { bg:'bg-[#8BDFDD]', border:'border-[#FFF6DE]/30', iconPath:'<path d="M9 12l2 2 4-4" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/><circle cx="12" cy="12" r="9" stroke-width="2"/>' },
                error:   { bg:'bg-red-600',   border:'border-red-400/30',   iconPath:'<path d="M6 18 18 6M6 6l12 12" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>' },
                warning: { bg:'bg-[#F48F68]', border:'border-[#FFF6DE]/40', iconPath:'<path d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>' },
            };
            const s = styles[type] || styles.success;

            inner.className  = `${s.bg} text-[#4F252E] px-6 py-3 rounded-xl shadow-2xl flex items-center gap-3 border ${s.border}`;
            icon.innerHTML   = s.iconPath;

            t.style.display    = 'block';
            t.style.opacity    = '0';
            t.style.transform  = 'translateX(-50%) translateY(-10px)';
            t.style.transition = 'opacity 0.3s, transform 0.3s';

            requestAnimationFrame(() => {
                t.style.opacity   = '1';
                t.style.transform = 'translateX(-50%) translateY(0)';
            });

            setTimeout(() => {
                t.style.opacity   = '0';
                t.style.transform = 'translateX(-50%) translateY(-10px)';
                setTimeout(() => { t.style.display = 'none'; }, 300);
            }, 3000);
        }
    </script>
    <script>
        document.getElementById('mobile-menu-btn')?.addEventListener('click', function () {
            document.getElementById('mobile-menu').classList.toggle('hidden');
        });
    </script>
    @endverbatim

    @livewireScripts
</body>
</html>


