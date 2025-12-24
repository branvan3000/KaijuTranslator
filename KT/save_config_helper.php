<?php

function save_kaiju_config($baseLang, $targetLangs, $provider, $model, $apiKey)
{
    $configFile = __DIR__ . '/KT/kaiju-config.php';

    $configContent = "<?php\n\nreturn [\n";
    $configContent .= "    // --- Language Settings ---\n";
    $configContent .= "    'base_lang' => '$baseLang',\n";
    $configContent .= "    'languages' => " . var_export($targetLangs, true) . ",\n\n";

    $configContent .= "    // --- AI Translation ---\n";
    $configContent .= "    'translation_provider' => '$provider',\n";
    $configContent .= "    'model' => '$model',\n";
    $configContent .= "    'api_key' => '$apiKey',\n\n";

    $configContent .= "    // --- Advanced Settings (Defaults) ---\n";
    $configContent .= "    'mode' => 'on_demand', // 'on_demand' or 'prebuild'\n";
    $configContent .= "    'uninstall_password' => 'kaiju123', // Change this!\n";
    $configContent .= "    'cache_path' => __DIR__ . '/cache',\n";
    $configContent .= "    'sitemaps_path' => __DIR__ . '/../sitemaps/kaiju',\n";
    $configContent .= "    'allowed_paths' => [__DIR__ . '/../'],\n";
    $configContent .= "    'excluded_paths' => ['KT', 'vendor', '.git'],\n";
    $configContent .= "    'seo' => ['hreflang_enabled' => true],\n";
    $configContent .= "];\n";

    return file_put_contents($configFile, $configContent);
}
