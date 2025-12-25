<?php

// Prevent direct access if needed, though this file is usually included
if (!defined('KAIJU_START')) {
    define('KAIJU_START', microtime(true));
}

// 1. Class Autoloader
spl_autoload_register(function ($class) {
    $prefix = 'KaijuTranslator\\';
    $base_dir = __DIR__ . '/src/';

    // Does the class use the namespace prefix?
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    // Get the relative class name
    $relative_class = substr($class, $len);

    // Replace the namespace prefix with the base directory, replace namespace
    // separators with directory separators in the relative class name, append
    // with .php
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    // If the file exists, require it
    if (file_exists($file)) {
        require $file;
    }
});

if (!function_exists('kaiju_is_valid_base_url')) {
    function kaiju_is_valid_base_url(?string $url): bool
    {
        if (empty($url)) {
            return false;
        }

        $url = trim($url);
        if (!preg_match('/^https?:\/\//i', $url)) {
            return false;
        }

        return (bool) filter_var($url, FILTER_VALIDATE_URL);
    }
}

// 2. Load Configuration
if (!function_exists('kaiju_config')) {
    function kaiju_config($key = null, $default = null)
    {
        static $config;
        if (!$config) {
            // Config is now inside KT/
            $userConfigFile = __DIR__ . '/kaiju-config.php';
            $internalConfigFile = __DIR__ . '/config.php';

            if (file_exists($userConfigFile)) {
                $config = require $userConfigFile;
            } elseif (file_exists($internalConfigFile)) {
                $config = require $internalConfigFile;
            } else {
                // If no config file is found, initialize with an empty array
                // and then apply defaults if requested.
                $config = [];
            }
            // Apply default cache_path if not explicitly set in config files
            if (!isset($config['cache_path'])) {
                $config['cache_path'] = __DIR__ . '/cache';
            }

            if (!empty($config['base_url'])) {
                $config['base_url'] = rtrim($config['base_url'], '/');
            }

            // Support Environment Variables for API Key
            $envKey = getenv('KAIJU_API_KEY');
            if ($envKey) {
                $config['api_key'] = $envKey;
            }
        }

        if ($key === null) {
            return $config;
        }

        // Simple dot notation support (e.g., 'seo.hreflang_enabled')
        $keys = explode('.', $key);
        $value = $config;
        foreach ($keys as $k) {
            if (isset($value[$k])) {
                $value = $value[$k];
            } else {
                if ($key === 'cache_path')
                    return __DIR__ . '/cache';
                return $default;
            }
        }
        return $value;
    }
}

/**
 * Validates the core configuration and returns an array of errors/warnings.
 */
function kaiju_validate_config()
{
    $config = kaiju_config();
    $results = [
        'errors' => [],
        'warnings' => []
    ];

    // 1. Language Check
    if (empty($config['base_lang'])) {
        $results['errors'][] = "Missing 'base_lang'.";
    }
    if (empty($config['languages']) || !is_array($config['languages'])) {
        $results['errors'][] = "Missing or invalid 'languages' array.";
    } elseif (isset($config['base_lang']) && !in_array($config['base_lang'], $config['languages'])) {
        $results['errors'][] = "Base language '{$config['base_lang']}' not found in active languages list.";
    }

    // 2. Provider Check
    $allowedProviders = ['openai', 'deepseek', 'gemini', 'gpt4'];
    $provider = strtolower($config['translation_provider'] ?? '');
    if (empty($provider)) {
        $results['errors'][] = "Translation provider not set.";
    } elseif (!in_array($provider, $allowedProviders)) {
        $results['errors'][] = "Invalid provider '{$provider}'. Use: openai, deepseek, gemini, or gpt4.";
    }

    // 3. API Key Check
    if (empty($config['api_key'])) {
        $results['warnings'][] = "API Key is missing. Translation will run in Mock Mode.";
    }

    // 4. Base URL Check (for sitemaps)
    $baseUrl = $config['base_url'] ?? '';
    if (empty($baseUrl)) {
        $results['errors'][] = "Config 'base_url' is missing. Set it in KT/kaiju-config.php or setup.";
    } elseif (!kaiju_is_valid_base_url($baseUrl)) {
        $results['errors'][] = "Config 'base_url' is invalid. Use an absolute http(s) URL.";
    }

    // 5. Cache Path Check
    $cachePath = $config['cache_path'] ?? null;
    if ($cachePath) {
        if (!is_dir($cachePath)) {
            $results['warnings'][] = "Cache directory does not exist and might not be creatable.";
        } elseif (!is_writable($cachePath)) {
            $results['errors'][] = "Cache directory '{$cachePath}' is not writable.";
        }
    }

    return $results;
}
