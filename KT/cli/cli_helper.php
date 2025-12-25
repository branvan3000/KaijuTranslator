<?php
// Since build runs in CLI, REQUEST_URI is not available.
// We need to guess or config the base URL.
// For now, let's assume localhost or config.

function get_cli_base_url()
{
    // Guess from directory name for local dev, but allow config override.
    $config = function_exists('kaiju_config') ? kaiju_config() : [];
    if (!empty($config['base_url']))
        return $config['base_url'];

    $folder = basename(realpath(__DIR__ . '/../../'));
    if ($folder === 'html' || $folder === 'var')
        return null; // Too generic for guessing

    return 'http://localhost/' . $folder;
}

function normalize_url($url)
{
    if (empty($url))
        return '';
    // Ensure protocol
    if (!preg_match('/^https?:\/\//i', $url)) {
        $url = 'http://' . $url;
    }
    // Remove trailing slash
    return rtrim($url, '/');
}

function is_valid_base_url($url)
{
    if (empty($url))
        return false;

    // Basic formatting
    if (!filter_var($url, FILTER_VALIDATE_URL))
        return false;

    // Optional: host check (no simple loose strings)
    $host = parse_url($url, PHP_URL_HOST);
    if (!$host || strpos($host, '.') === false && $host !== 'localhost') {
        // Warning: Hosts like 'myserver' are valid internally but risky for public SEO
    }

    return true;
}

function check_url_reachability($url)
{
    // Simple HEAD request to see if it responds
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 3);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    // Trust localhost in CLI
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return $code >= 200 && $code < 400;
}
