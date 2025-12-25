<?php
// Since build runs in CLI, REQUEST_URI is not available.
// We need to guess or config the base URL.
// For now, let's assume localhost or config.

function get_cli_base_url()
{
    $candidates = [];

    if (function_exists('kaiju_config')) {
        $config = kaiju_config();
        if (!empty($config['base_url'])) {
            $candidates[] = $config['base_url'];
        }
    }

    $envBaseUrl = getenv('KAIJU_BASE_URL');
    if ($envBaseUrl) {
        $candidates[] = $envBaseUrl;
    }

    foreach ($candidates as $candidate) {
        if (is_valid_base_url($candidate)) {
            return rtrim($candidate, '/');
        }
    }

    $folder = basename(realpath(__DIR__ . '/../../'));
    if ($folder && !in_array(strtolower($folder), ['html', 'var'], true)) {
        $guess = 'http://localhost/' . $folder;
        if (is_valid_base_url($guess)) {
            return rtrim($guess, '/');
        }
    }

    return null;
}

function is_valid_base_url($url)
{
    if (function_exists('kaiju_is_valid_base_url')) {
        return kaiju_is_valid_base_url($url);
    }

    if (empty($url)) {
        return false;
    }

    $url = trim($url);
    if (!preg_match('/^https?:\/\//i', $url)) {
        return false;
    }

    return (bool) filter_var($url, FILTER_VALIDATE_URL);
}

