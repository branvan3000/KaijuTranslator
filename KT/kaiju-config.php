<?php

return [
  // --- Language Settings ---
  'base_lang' => 'es',
  'languages' => array(
    0 => 'es',
    1 => 'en',
    2 => 'fr',
  ),

  // --- AI Translation ---
  'translation_provider' => 'openai',
  'model' => 'gpt-4o-mini',
  'api_key' => '',

  // --- Advanced Settings (Defaults) ---
  'uninstall_password' => 'kaiju123',
  'cache_path' => __DIR__ . '/cache',
  'sitemaps_path' => __DIR__ . '/../sitemaps/kaiju',
  'allowed_paths' => [__DIR__ . '/../'],
  'excluded_paths' => ['KT', 'vendor', '.git'],
  'seo' => ['hreflang_enabled' => true],
];
