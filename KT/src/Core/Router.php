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
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $uriPath = parse_url($uri, PHP_URL_PATH);

        // Handle both "/en/" and "/en" (end of string)
        $prefix = '/' . $lang . '/';
        $pos = strpos($uriPath, $prefix);

        if ($pos !== false) {
            $before = substr($uriPath, 0, $pos);
            $after = substr($uriPath, $pos + strlen($prefix) - 1);
            return ($before ?: '') . ($after ?: '/');
        }

        // Check for trailing /en
        $suffix = '/' . $lang;
        if (substr($uriPath, -strlen($suffix)) === $suffix) {
            $before = substr($uriPath, 0, -strlen($suffix));
            return ($before ?: '') . '/';
        }

        return $uriPath;
    }

    public function getLocalizedUrl($lang, $sourcePath)
    {
        $baseLang = $this->config['base_lang'] ?? 'es';
        if ($lang === $baseLang) {
            return $sourcePath;
        }

        // We need to inject $lang into $sourcePath.
        // If $sourcePath is /about.php and the site is at root, we want /en/about.php
        // If site is at /sub/ and $sourcePath is /sub/about.php, we want /sub/en/about.php

        // Strategy: find where the "base" ends. 
        // We can use the current request as a hint if we are in a localized stub.
        $currentUri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $currentLang = defined('KT_LANG') ? KT_LANG : $baseLang;

        if ($currentLang !== $baseLang) {
            // We know where the language prefix is in the current URI
            $prefix = '/' . $currentLang;
            $pos = strpos($currentUri, $prefix);
            if ($pos !== false) {
                $basePath = substr($currentUri, 0, $pos);
                // The part of $sourcePath after $basePath
                $relPath = substr($sourcePath, strlen($basePath));
                return $basePath . '/' . $lang . $relPath;
            }
        }

        // Fallback or if we are in base language:
        // Try to guess if $sourcePath starts with a common directory structure or just /
        // For simplicity, if it's just / we return /$lang/
        if ($sourcePath === '/') {
            return '/' . $lang . '/';
        }

        // Otherwise, just prefix (standard behavior for root installs)
        return '/' . $lang . $sourcePath;
    }

    public function getBaseUrl(string $path = '')
    {
        // Construct absolute URL for internal loopback
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $scheme . '://' . $host . $path;
    }
}
