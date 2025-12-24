<?php

namespace KaijuTranslator\Core;

class Router
{
    protected $config;
    protected $currentLang;
    protected $sourcePath;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->parseRequest();
    }

    protected function parseRequest()
    {
        // Simple logic: Assume script is running inside a subfolder stub, 
        // OR we are parsing REQUEST_URI if using rewrites (which we are not, per spec, but good to be robust).

        // However, per spec "Idiomas por subcarpetas físicas", the stubs are physically at /en/index.php etc.
        // So the stub itself knows which language it is serving (it's hardcoded in the stub).

        // But for "on_demand" or dynamic testing, we might need to know the requested URI relative to the root.

        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $uri = parse_url($uri, PHP_URL_PATH);

        // Detect language from path prefix if needed, or rely on manual setting from the stub.
        // For now, we'll assume the caller (the stub) sets the language explicitly.
    }

    public function resolveSourceUrl($lang)
    {
        // Converts current translated request back to source URL
        // E.g. /en/about.php -> /about.php
        // E.g. /sub/folder/en/about.php -> /sub/folder/about.php

        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $uriPath = parse_url($uri, PHP_URL_PATH);

        // More robust: search for "/$lang/" or matching the end
        $prefix = '/' . $lang . '/';
        $pos = strpos($uriPath, $prefix);

        if ($pos !== false) {
            // Reconstruct: part before prefix + part after prefix
            $before = substr($uriPath, 0, $pos);
            $after = substr($uriPath, $pos + strlen($prefix) - 1); // Keep the leading slash
            return ($before ?: '') . ($after ?: '/');
        }

        return $uriPath; // Fallback
    }

    public function getBaseUrl(string $path = '')
    {
        // Construct absolute URL for internal loopback
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $scheme . '://' . $host . $path;
    }
}
