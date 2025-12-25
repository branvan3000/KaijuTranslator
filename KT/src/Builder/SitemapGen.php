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

        $urlCount = 0;
        foreach ($files as $file) {
            $urlCount++;
            if ($urlCount > 50000) {
                // Warn about size limit (could split into multiple files in future)
                error_log("Warning: Sitemap $filename exceeds 50,000 URLs.");
            }

            // Normalize file path for URL
            $urlPath = ltrim(str_replace('\\', '/', $file), '/');

            // Construct URL
            if ($lang === kaiju_config('base_lang')) {
                $url = $this->baseUrl . '/' . $urlPath;
            } else {
                $url = $this->baseUrl . '/' . $lang . '/' . $urlPath;
            }

            // Get Last Modified Date
            // $file is relative to project root? Wait, caller passes relative paths from Scanner scan()
            // Scanner scan() returns paths relative to rootDir.
            // We need full path to get filemtime.
            // But strict project structure: rootDir is parent of KT?
            // Let's assume we can find the file using allowed_paths logic or just relative to __DIR__/../../ ??
            // Actually, Scanner should probably provide full paths or we look it up.
            // If we can't find file, skip lastmod.

            $fullPath = realpath(__DIR__ . '/../../../' . $file);
            $lastMod = $fullPath && file_exists($fullPath) ? date('c', filemtime($fullPath)) : date('c');

            $content .= "  <url>\n";
            $content .= "    <loc>" . htmlspecialchars($url) . "</loc>\n";
            $content .= "    <lastmod>$lastMod</lastmod>\n";
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
