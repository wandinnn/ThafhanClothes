<?php

use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component {
    public int $count = 0;

    public function mount(): void
    {
        $this->refresh();
    }

    #[On('cart-updated')]
    public function refresh(): void
    {
        $this->count = count(session('cart', []));
    }

    public function render()
    {
        return view('components.⚡cart-badge');
    }
};
?>

<a href="{{ route('cart') }}"
   class="relative p-2 text-[#4F252E] hover:text-[#1F2A2A] transition-colors duration-200">
    <svg xmlns="http://www.w3.org/2000/svg" class="size-6" fill="none" viewBox="0 0 24 24"
         stroke-width="1.5" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round"
              d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z"/>
    </svg>
    @if($count > 0)
        <span class="absolute -top-0.5 -right-0.5 bg-[#FFF6DE] text-[#1F2A2A] text-[10px] font-bold
                     rounded-full size-4.5 flex items-center justify-center leading-none">
            {{ $count }}
        </span>
    @endif
</a>



