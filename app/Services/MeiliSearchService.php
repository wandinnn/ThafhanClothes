<?php

namespace App\Services;

use App\Models\Product;
use Meilisearch\Client;

class MeiliSearchService
{
    private ?Client $client = null;

    private string $indexName;

    public function __construct()
    {
        $this->indexName = config('meilisearch.index', 'products');
    }

    public function isEnabled(): bool
    {
        return (bool) config('meilisearch.enabled');
    }

    /**
     * Client dibuat saat dibutuhkan saja, agar aplikasi tetap bisa jalan
     * ketika Meilisearch dimatikan atau tidak terpasang.
     */
    private function client(): Client
    {
        return $this->client ??= new Client(
            config('meilisearch.host'),
            config('meilisearch.key'),
        );
    }

    public function indexProduct(Product $p): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        $doc = [
            'id' => $p->id,
            'name' => $p->name,
            'description' => $p->description,
            'category' => $p->category->name ?? null,
            'price' => (float) $p->price,
            'image_url' => $p->image_url,
            'slug' => $p->slug,
        ];

        $index = $this->client()->index($this->indexName);
        $index->addDocuments([$doc]);
    }

    public function deleteProduct(int $id): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        $index = $this->client()->index($this->indexName);
        $index->deleteDocument($id);
    }

    public function search(string $query, array $options = []): array
    {
        if (! $this->isEnabled()) {
            return [];
        }

        $index = $this->client()->index($this->indexName);
        $params = array_merge([
            'limit' => $options['limit'] ?? 12,
            'attributesToRetrieve' => ['id', 'name', 'slug', 'price', 'image_url'],
        ], $options['params'] ?? []);

        $res = $index->search($query, $params);

        return $res->getHits();
    }

    public function importAllProducts(): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        $rows = Product::with('category')->get();
        $docs = [];
        foreach ($rows as $p) {
            $docs[] = [
                'id' => $p->id,
                'name' => $p->name,
                'description' => $p->description,
                'category' => $p->category->name ?? null,
                'price' => (float) $p->price,
                'image_url' => $p->image_url,
                'slug' => $p->slug,
            ];
        }

        if (! empty($docs)) {
            $index = $this->client()->index($this->indexName);
            $index->addDocuments($docs);
        }
    }
}
