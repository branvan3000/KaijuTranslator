<?php

return [
    // Base URL of your site (e.g. https://pablocirre.es). Required for CLI sitemap generation.
    'base_url' => 'http://localhost/PabloCirre2',

    // --- Language Settings ---
    'base_lang' => 'es',
    'languages' => ['en', 'es', 'fr', 'ja'],

    // --- AI Translation ---
    'translation_provider' => 'openai',
    'model' => 'gpt-3.5-turbo',
    'api_key' => [],

    // --- Advanced Settings (Defaults) ---
    'uninstall_password' => '',
    'cache_path' => __DIR__ . '/cache',
    'sitemaps_path' => __DIR__ . '/../sitemaps/kaiju',
    'allowed_paths' => [__DIR__ . '/../'],
    'excluded_paths' => ['KT', 'vendor', '.git'],
    'seo' => ['hreflang_enabled' => true, 'canonical_strategy' => 'self'],
];
