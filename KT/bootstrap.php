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
                $config = [];
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
    $errors = [];

    // 1. Language Check
    if (empty($config['base_lang'])) {
        $errors[] = "Missing 'base_lang'.";
    }
    if (empty($config['languages']) || !is_array($config['languages'])) {
        $errors[] = "Missing or invalid 'languages' array.";
    } elseif (isset($config['base_lang']) && !in_array($config['base_lang'], $config['languages'])) {
        $errors[] = "Base language '{$config['base_lang']}' not found in active languages list.";
    }

    // 2. Provider Check
    $allowedProviders = ['openai', 'deepseek', 'gemini', 'gpt4'];
    $provider = strtolower($config['translation_provider'] ?? '');
    if (empty($provider)) {
        $errors[] = "Translation provider not set.";
    } elseif (!in_array($provider, $allowedProviders)) {
        $errors[] = "Invalid provider '{$provider}'. Use: openai, deepseek, gemini, or gpt4.";
    }

    // 3. API Key Check
    if (empty($config['api_key'])) {
        $errors[] = "API Key is missing. Translation will run in Mock Mode.";
    }

    // 4. Base URL Check (for sitemaps)
    if (empty($config['base_url'])) {
        $errors[] = "Missing 'base_url'. SEO sitemaps cannot be generated via CLI/Dashboard without it.";
    }

    return $errors;
}
