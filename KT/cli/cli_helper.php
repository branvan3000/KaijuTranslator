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

function is_valid_base_url($url)
{
    if (empty($url))
        return false;
    // Basic schema check: must start with http:// or https://
    return (bool) preg_match('/^https?:\/\//i', $url);
}
