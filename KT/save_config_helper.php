<?php

require_once __DIR__ . '/bootstrap.php';

function save_kaiju_config($baseUrl, $baseLang, $targetLangs, $provider, $model, $apiKeys, $widgetStyle = 'glass', $widgetContent = 'both', $uninstallPassword = null)
{
    $configFile = __DIR__ . '/kaiju-config.php';

    // Normalize API Keys to array
    if (is_string($apiKeys)) {
        // If it comes from the textarea, it might be newline separated
        $apiKeys = array_filter(array_map('trim', explode("\n", str_replace(["\r\n", "\r"], "\n", $apiKeys))));
        // If it's a single key string passed directly, wrap it
        if (empty($apiKeys) && !empty($apiKeys))
            $apiKeys = [$apiKeys];
    }
    if (empty($apiKeys))
        $apiKeys = [];
    $existingConfig = [];
    if (file_exists($configFile)) {
        $existingConfig = include $configFile;
    }

    if (!kaiju_is_valid_base_url($baseUrl)) {
        return false;
    }

    $baseUrl = rtrim($baseUrl, '/');

    if ($uninstallPassword === null) {
        $uninstallPassword = $existingConfig['uninstall_password'] ?? 'kaiju123';
    }
    // Allow empty password (disable auth) if explicitly set to empty string.
    // Only generate random if it is 'kaiju123' (default placeholder) to force a change on first run if desired,
    // OR if we want to enforce security. But user asked to remove it.
    // So we remove the auto-generation for empty values.
    /*
    if (!$uninstallPassword || $uninstallPassword === 'kaiju123') {
       // Removed forced generation
    }
    */

    $configContent = "<?php\n\nreturn [\n";
    $configContent .= "    // Base URL of your site (e.g. https://pablocirre.es). Required for CLI sitemap generation.\n";
    $configContent .= "    'base_url' => " . var_export($baseUrl, true) . ",\n\n";

    $configContent .= "    // --- Language Settings ---\n";
    $configContent .= "    'base_lang' => " . var_export($baseLang, true) . ",\n";
    $configContent .= "    'languages' => " . var_export($targetLangs, true) . ",\n\n";

    $configContent .= "    // --- AI Translation ---\n";
    $configContent .= "    'translation_provider' => " . var_export($provider, true) . ",\n";
    $configContent .= "    'model' => " . var_export($model, true) . ",\n";
    $configContent .= "    'api_key' => " . var_export($apiKeys, true) . ",\n\n";

    $configContent .= "    // --- Widget Design ---\n";
    $configContent .= "    'widget_style' => " . var_export($widgetStyle, true) . ",\n\n";

    $configContent .= "    // --- Advanced Settings (Defaults) ---\n";
    $configContent .= "    'uninstall_password' => " . var_export($uninstallPassword, true) . ",\n";
    $configContent .= "    'cache_path' => __DIR__ . '/cache',\n";
    $configContent .= "    'sitemaps_path' => __DIR__ . '/../sitemaps/kaiju',\n";
    $configContent .= "    'allowed_paths' => " . var_export($existingConfig['allowed_paths'] ?? [__DIR__ . '/../'], true) . ",\n";
    $configContent .= "    'excluded_paths' => " . var_export($existingConfig['excluded_paths'] ?? ['KT', 'vendor', '.git'], true) . ",\n";
    $configContent .= "    'seo' => " . var_export($existingConfig['seo'] ?? ['hreflang_enabled' => true, 'canonical_strategy' => 'self'], true) . ",\n";
    $configContent .= "];\n";

    return file_put_contents($configFile, $configContent, LOCK_EX);
}
