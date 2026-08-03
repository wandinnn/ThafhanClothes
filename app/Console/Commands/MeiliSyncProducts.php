<?php

namespace App\Console\Commands;

use App\Services\MeiliSearchService;
use Illuminate\Console\Command;

class MeiliSyncProducts extends Command
{
    protected $signature = 'meili:sync-products';

    protected $description = 'Sync all products to Meilisearch index';

    public function handle(MeiliSearchService $meili): int
    {
        if (! $meili->isEnabled()) {
            $this->error('Meilisearch is not enabled. Set MEILI_ENABLED=true in .env');

            return 1;
        }

        $this->info('Starting product import to Meilisearch...');
        $meili->importAllProducts();
        $this->info('Import finished.');

        return 0;
    }
}
