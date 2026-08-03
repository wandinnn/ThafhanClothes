<?php

use Livewire\Component;
use App\Models\Product;

new class extends Component {
    public string $searchQuery = '';
    public array $searchResults = [];
    public bool $showResults = false;

    public function updatedSearchQuery(): void
    {
        $this->showResults = false;

        if (strlen(trim($this->searchQuery)) < 2) {
            $this->searchResults = [];
            return;
        }

        $search = trim($this->searchQuery);
        
        // Fuzzy search: cari dengan toleransi typo
        $this->searchResults = Product::where(function ($q) use ($search) {
            // 1. Exact match (LIKE) - prioritas tertinggi
            $q->where('name', 'like', '%' . $search . '%')
                ->orWhere('description', 'like', '%' . $search . '%');
            
            // 2. Fuzzy match: Split ke kata-kata dan cari dengan OR
            $words = explode(' ', $search);
            foreach ($words as $word) {
                if (strlen($word) > 1) {
                    $q->orWhere('name', 'like', '%' . $word . '%');
                }
            }
        })
            ->select('id', 'name', 'slug', 'price', 'image_url')
            ->limit(6)
            ->get()
            ->toArray();

        $this->showResults = count($this->searchResults) > 0;
    }

    public function goToProduct(string $slug): void
    {
        $this->redirect(route('product.detail', $slug));
    }

    public function submitSearch(): void
    {
        if (empty(trim($this->searchQuery))) {
            return;
        }
        $this->redirect(route('products', ['search' => trim($this->searchQuery)]));
    }

    public function render()
    {
        return view('livewire.search-bar');
    }
};
?>
