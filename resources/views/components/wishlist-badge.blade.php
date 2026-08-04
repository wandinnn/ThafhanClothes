<?php

use App\Models\Wishlist;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component {
    public int $count = 0;

    public function mount(): void
    {
        $this->refresh();
    }

    #[On('wishlist-updated')]
    public function refresh(?int $count = null): void
    {
        $this->count = $count ?? Wishlist::countForSession();
    }

    public function render()
    {
        return view('components.wishlist-badge');
    }
};
?>

<a wire:navigate href="{{ route('wishlist') }}"
   data-badge-wrapper
   class="relative p-2 text-deep hover:text-coral transition-colors duration-200"
   title="Wishlist">
    <svg xmlns="http://www.w3.org/2000/svg" class="size-6" fill="none" viewBox="0 0 24 24"
         stroke-width="1.5" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round"
              d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z"/>
    </svg>
    @if($count > 0)
        <span class="badge-count absolute -top-0.5 -right-0.5 bg-coral text-on-coral text-[10px] font-bold
                     rounded-full size-4.5 flex items-center justify-center leading-none">
            {{ $count }}
        </span>
    @endif
</a>
