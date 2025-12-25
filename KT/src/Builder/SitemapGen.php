<?php

namespace KaijuTranslator\Builder;

class SitemapGen
{
    protected $savePath;
    protected $baseUrl;

    protected $sitemapsUrl;

    public function __construct($savePath, $baseUrl, $sitemapsUrl = null)
    {
        $this->savePath = rtrim($savePath, '/');
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->sitemapsUrl = $sitemapsUrl ? rtrim($sitemapsUrl, '/') : $this->baseUrl . '/sitemaps/kaiju';

        if (!is_dir($this->savePath)) {
            mkdir($this->savePath, 0755, true);
        }
    }

    public function generate($lang, $files)
    {
        $filename = 'sitemap-' . $lang . '.xml';
        $content = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $content .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">' . "\n";

        foreach ($files as $file) {
            // $file is relative source path e.g. 'about.php'
            // If base lang, URL is /about.php
            // If other lang, URL is /en/about.php
            // Wait, logic needs to be precise.

            // Assume $files contains SOURCE paths.
            // Caller handles lang logic, or we do it here.
            // Let's assume we generate sitemap strictly for this lang.

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

            $content .= "  <url>\n";
            $content .= "    <loc>$url</loc>\n";
            // Optional: lastmod, changefreq
            $content .= "  </url>\n";
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
            $url = $this->sitemapsUrl . '/' . $sitemap;

            $content .= "  <sitemap>\n";
            $content .= "    <loc>$url</loc>\n";
            $content .= "  </sitemap>\n";
        }

        $content .= '</sitemapindex>';
        file_put_contents($this->savePath . '/' . $filename, $content);
    }
}
