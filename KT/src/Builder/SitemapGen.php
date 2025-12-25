<?php

namespace KaijuTranslator\Builder;

class SitemapGen
{
    protected $savePath;
    protected $baseUrl;
    protected $sitemapsUrl;
    protected $projectRoot;
    protected $maxUrls;
    protected $maxBytes;
    protected $warnings = [];

    public function __construct($savePath, $baseUrl, $sitemapsUrl = null, $projectRoot = null, $maxUrls = 50000, $maxBytes = 52428800)
    {
        $this->savePath = rtrim($savePath, '/');
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->sitemapsUrl = $sitemapsUrl ? rtrim($sitemapsUrl, '/') : $this->baseUrl . '/sitemaps/kaiju';
        $this->projectRoot = $projectRoot ? rtrim($projectRoot, '/') : null;
        $this->maxUrls = max(1, (int) $maxUrls);
        $this->maxBytes = max(1024, (int) $maxBytes);

        if (!is_dir($this->savePath)) {
            mkdir($this->savePath, 0755, true);
        }
    }

    public function generate($lang, $files)
    {
        $this->warnings = [];

        $filename = 'sitemap-' . $lang . '.xml';
        $content = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $content .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">' . "\n";

        $urlCount = 0;
        $contentSize = strlen($content);

        $totalFiles = count($files);

        foreach ($files as $file) {
            if ($urlCount >= $this->maxUrls) {
                $this->warnings[] = "Sitemap {$filename} truncated to {$this->maxUrls} URLs (original: {$totalFiles}).";
                break;
            }

            // $file is relative source path e.g. 'about.php'
            // If base lang, URL is /about.php
            // If other lang, URL is /en/about.php
            // Wait, logic needs to be precise.

            // Normalize file path for URL
            $urlPath = ltrim(str_replace('\\', '/', $file), '/');

            // Construct URL
            if ($lang === kaiju_config('base_lang')) {
                $url = $this->baseUrl . '/' . ltrim($file, '/');
            } else {
                // Correctly handle subdirectories: baseUrl might already have a path
                // But generally, we want /lang/ after the domain-root relative path if any.
                // However, since we now have Router::buildLangPath concept, we should be careful.
                // For sitemaps, usually it's domain.com/subdir/en/file.php

                $parsed = parse_url($this->baseUrl);
                $path = $parsed['path'] ?? '';
                $scheme = $parsed['scheme'] . '://';
                $host = $parsed['host'];
                $port = isset($parsed['port']) ? ':' . $parsed['port'] : '';

                $baseUrlWithoutPath = $scheme . $host . $port;
                $url = $baseUrlWithoutPath . rtrim($path, '/') . '/' . $lang . '/' . ltrim($file, '/');
            }

            $loc = htmlspecialchars($url, ENT_XML1 | ENT_COMPAT, 'UTF-8');
            $lastmod = $this->resolveLastMod($file);

            $entry = "  <url>\n";
            $entry .= "    <loc>{$loc}</loc>\n";
            if ($lastmod) {
                $entry .= "    <lastmod>{$lastmod}</lastmod>\n";
            }
            $entry .= "  </url>\n";

            $entrySize = strlen($entry);
            if (($contentSize + $entrySize) > $this->maxBytes) {
                $this->warnings[] = "Sitemap {$filename} truncated due to size limit ({$this->maxBytes} bytes).";
                break;
            }

            $content .= $entry;
            $contentSize += $entrySize;
            $urlCount++;
        }

        $content .= '</urlset>';
        file_put_contents($this->savePath . '/' . $filename, $content);
        return $filename;
    }

    public function generateIndex($sitemaps)
    {
        $filename = 'sitemap-index.xml';
        $content = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $content .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($sitemaps as $sitemap) {
            $url = htmlspecialchars($this->sitemapsUrl . '/' . $sitemap, ENT_XML1 | ENT_COMPAT, 'UTF-8');
            $lastmod = date('c');

            $content .= "  <sitemap>\n";
            $content .= "    <loc>{$url}</loc>\n";
            $content .= "    <lastmod>{$lastmod}</lastmod>\n";
            $content .= "  </sitemap>\n";
        }

        $content .= '</sitemapindex>';
        file_put_contents($this->savePath . '/' . $filename, $content);
    }

    public function getWarnings(): array
    {
        return $this->warnings;
    }

    protected function resolveLastMod($relativeFile)
    {
        if (!$this->projectRoot) {
            return date('c');
        }

        $fullPath = $this->projectRoot . '/' . ltrim($relativeFile, '/');
        if (!file_exists($fullPath)) {
            return date('c');
        }

        $mtime = @filemtime($fullPath);
        if ($mtime === false) {
            return date('c');
        }

        return date('c', $mtime);
    }
}
