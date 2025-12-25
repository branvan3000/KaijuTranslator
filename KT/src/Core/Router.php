<?php

namespace KaijuTranslator\Core;

class Router
{
    protected $basePath = '';
    protected $config = [];

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->detectBasePath();
    }

    protected function detectBasePath()
    {
        // Calculate the project root relative to the domain root
        $projectRoot = realpath(__DIR__ . '/../../../');
        $docRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '');

        if ($docRoot && $projectRoot && strpos($projectRoot, $docRoot) === 0) {
            $base = substr($projectRoot, strlen($docRoot));
            $this->basePath = rtrim(str_replace('\\', '/', $base), '/');
        } else {
            // Fallback for CLI or cases where project root is not under doc root
            $this->basePath = '';
        }
    }

    public function getBasePath()
    {
        return $this->basePath;
    }

    public function resolveSourceUrl($lang)
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $uriPath = parse_url($uri, PHP_URL_PATH) ?: '/';

        // Remove the basePath prefix to work with relative routes
        $pathWithoutBase = $uriPath;
        if ($this->basePath && strpos($uriPath, $this->basePath) === 0) {
            $pathWithoutBase = substr($uriPath, strlen($this->basePath));
        }
        $pathWithoutBase = '/' . ltrim($pathWithoutBase, '/');

        // Handle both "/en/" and "/en" (end of string)
        $prefix = '/' . $lang . '/';
        $pos = strpos($pathWithoutBase, $prefix);

        if ($pos !== false) {
            $before = substr($pathWithoutBase, 0, $pos);
            $after = substr($pathWithoutBase, $pos + strlen($prefix) - 1);
            $rel = ($before ?: '') . ($after ?: '/');
            return $this->basePath . $rel;
        }

        // Check for trailing /en
        $suffix = '/' . $lang;
        if ($pathWithoutBase === $suffix || substr($pathWithoutBase, -strlen($suffix)) === $suffix) {
            $before = substr($pathWithoutBase, 0, -strlen($suffix));
            $rel = ($before ?: '') . '/';
            return $this->basePath . $rel;
        }

        return $uriPath;
    }

    public function getLocalizedUrl($lang, $sourcePath)
    {
        $baseLang = $this->config['base_lang'] ?? 'es';

        // Ensure sourcePath is relative to basePath if it's already absolute from domain root
        $relPath = $sourcePath;
        if ($this->basePath && strpos($sourcePath, $this->basePath) === 0) {
            $relPath = substr($sourcePath, strlen($this->basePath));
        }
        $relPath = '/' . ltrim($relPath, '/');

        if ($lang === $baseLang) {
            return $this->basePath . $relPath;
        }

        return $this->basePath . '/' . $lang . $relPath;
    }

    public function getBaseUrl(string $path = '')
    {
        // In CLI, use base_url from config if available
        if (php_sapi_name() === 'cli' && !empty($this->config['base_url'])) {
            return rtrim($this->config['base_url'], '/') . $path;
        }

        // Check for common proxy headers
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        if (isset($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
            $scheme = $_SERVER['HTTP_X_FORWARDED_PROTO'];
        }

        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        if (isset($_SERVER['HTTP_X_FORWARDED_HOST'])) {
            $host = $_SERVER['HTTP_X_FORWARDED_HOST'];
        }

        return $scheme . '://' . $host . $path;
    }
}
