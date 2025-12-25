<?php

function save_kaiju_config($baseLang, $targetLangs, $provider, $model, $apiKey)
{
    $configFile = __DIR__ . '/kaiju-config.php';

    // Load existing config if it exists to preserve custom settings
    $existingConfig = [];
    if (file_exists($configFile)) {
        $existingConfig = include $configFile;
    }

    $uninstallPassword = $existingConfig['uninstall_password'] ?? null;
    if (!$uninstallPassword || $uninstallPassword === 'kaiju123') {
        $uninstallPassword = bin2hex(random_bytes(6)); // 12 characters random
    }

    $configContent = "<?php\n\nreturn [\n";
    $configContent .= "    // Base URL of your site (e.g. https://pablocirre.es). Required for CLI sitemap generation.\n";
    $configContent .= "    'base_url' => " . var_export($existingConfig['base_url'] ?? '', true) . ",\n\n";

    $configContent .= "    // --- Language Settings ---\n";
    $configContent .= "    'base_lang' => " . var_export($baseLang, true) . ",\n";
    $configContent .= "    'languages' => " . var_export($targetLangs, true) . ",\n\n";

    $configContent .= "    // --- AI Translation ---\n";
    $configContent .= "    'translation_provider' => " . var_export($provider, true) . ",\n";
    $configContent .= "    'model' => " . var_export($model, true) . ",\n";
    $configContent .= "    'api_key' => " . var_export($apiKey, true) . ",\n\n";

    $configContent .= "    // --- Advanced Settings (Defaults) ---\n";
    $configContent .= "    'uninstall_password' => " . var_export($uninstallPassword, true) . ",\n";
    $configContent .= "    'cache_path' => __DIR__ . '/cache',\n";
    $configContent .= "    'sitemaps_path' => __DIR__ . '/../sitemaps/kaiju',\n";
    $configContent .= "    'allowed_paths' => [__DIR__ . '/../'],\n";
    $configContent .= "    'excluded_paths' => ['KT', 'vendor', '.git'],\n";
    $configContent .= "    'seo' => [\n";
    $configContent .= "        'hreflang_enabled' => true,\n";
    $configContent .= "        'canonical_strategy' => 'self',\n";
    $configContent .= "    ],\n";
    $configContent .= "];\n";

    return file_put_contents($configFile, $configContent, LOCK_EX);
}
