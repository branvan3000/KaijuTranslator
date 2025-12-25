<?php

return [
    // Base URL is required for sitemap generation and CLI tooling
    'base_url' => 'https://example.com',

    // 1. Language Configuration
    'base_lang' => 'es',
    'languages' => ['es', 'en', 'fr'],

    // 2. Translation Provider
    'translation_provider' => 'openai',
    'api_key' => getenv('KAIJU_API_KEY') ?: '',

    // 3. Discovery (Builder)
    'allowed_paths' => [
        __DIR__ . '/../',
    ],
    'excluded_paths' => [
        'KT',
        'vendor',
        'node_modules',
        '.git',
    ],

    // 4. Paths
    'cache_path' => __DIR__ . '/cache',
    'sitemaps_path' => __DIR__ . '/../sitemaps/kaiju',

    // 5. SEO
    'seo' => [
        'hreflang_enabled' => true,
        'canonical_strategy' => 'self', // 'self' points to the translated URL
    ],
];
