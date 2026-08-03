<?php

return [
    // Meilisearch server URL
    'host' => env('MEILISEARCH_HOST', 'http://127.0.0.1:7700'),
    // Master key (if configured)
    'key' => env('MEILISEARCH_KEY', null),
    // Index name for products
    'index' => env('MEILISEARCH_INDEX', 'products'),
    // Enable toggle
    'enabled' => env('MEILI_ENABLED', false),
];
