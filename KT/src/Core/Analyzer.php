<?php
namespace KaijuTranslator\Core;

class Analyzer
{
    protected $rootDir;
    protected $baseUrl;

    public function __construct($rootDir, $baseUrl)
    {
        $this->rootDir = realpath($rootDir);
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    public function scanStructure($recursive = true)
    {
        $structure = [
            'folders' => [],
            'files' => [],
            'total_files' => 0
        ];

        if (!is_dir($this->rootDir))
            return $structure;

        $directory = new \RecursiveDirectoryIterator($this->rootDir, \RecursiveDirectoryIterator::SKIP_DOTS);
        if ($recursive) {
            $iterator = new \RecursiveIteratorIterator($directory, \RecursiveIteratorIterator::SELF_FIRST);
        } else {
            $iterator = new \IteratorIterator($directory);
        }

        foreach ($iterator as $item) {
            $relativePath = str_replace($this->rootDir, '', $item->getPathname());
            if ($this->isExcluded($relativePath))
                continue;

            if ($item->isDir()) {
                $structure['folders'][] = $relativePath;
            } else {
                $ext = pathinfo($item->getPathname(), PATHINFO_EXTENSION);
                if (in_array($ext, ['php', 'html', 'htm'])) {
                    $structure['files'][] = $relativePath;
                    $structure['total_files']++;
                }
            }
        }
        return $structure;
    }

    public function buildFolderTree($dir = null)
    {
        $dir = $dir ?? $this->rootDir;
        $tree = [];
        if (!is_dir($dir))
            return $tree;

        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..')
                continue;

            $fullPath = $dir . DIRECTORY_SEPARATOR . $item;
            $relPath = str_replace($this->rootDir, '', $fullPath);
            if ($this->isExcluded($relPath))
                continue;

            if (is_dir($fullPath)) {
                $children = $this->buildFolderTree($fullPath);
                $folderSize = 0;
                $fileCount = 0;
                foreach ($children as $child) {
                    $folderSize += ($child['size'] ?? 0);
                    $fileCount += ($child['type'] === 'file' ? 1 : ($child['item_count'] ?? 0));
                }
                $tree[] = [
                    'name' => $item,
                    'type' => 'folder',
                    'path' => $relPath,
                    'children' => $children,
                    'size' => $folderSize,
                    'item_count' => $fileCount
                ];
            } else {
                $ext = pathinfo($item, PATHINFO_EXTENSION);
                if (in_array($ext, ['php', 'html', 'htm'])) {
                    $tree[] = [
                        'name' => $item,
                        'type' => 'file',
                        'path' => $relPath,
                        'size' => filesize($fullPath),
                        'lang' => $this->detectFileLanguage($fullPath)
                    ];
                }
            }
        }
        return $tree;
    }

    public function detectFileLanguage($path)
    {
        $content = @file_get_contents($path, false, null, 0, 8192); // Read more for better heuristic
        if (!$content)
            return '??';

        // 1. Check <html lang="...">
        if (preg_match('/<html[^>]*lang=["\']([^"\']+)["\']/i', $content, $matches)) {
            return strtoupper(substr($matches[1], 0, 2));
        }

        // 2. Simple Heuristic (Stop words)
        $lower = strtolower($content);
        $es_hits = preg_match_all('/\b(el|la|de|que|en|un|con|por|para|sus)\b/', $lower);
        $en_hits = preg_match_all('/\b(the|and|of|to|in|for|with|on|at|by)\b/', $lower);

        if ($es_hits > $en_hits && $es_hits > 5)
            return 'ES';
        if ($en_hits > $es_hits && $en_hits > 5)
            return 'EN';

        return '??';
    }

    public function detectSourceLanguage()
    {
        $indexFiles = ['/index.php', '/index.html', '/home.php', '/home.html'];
        foreach ($indexFiles as $f) {
            $path = $this->rootDir . $f;
            if (file_exists($path)) {
                $lang = $this->detectFileLanguage($path);
                if ($lang !== '??')
                    return strtolower($lang);
            }
        }
        return 'en';
    }

    public function detectSitemap()
    {
        $candidates = ['/sitemap.xml', '/sitemap_index.xml', '/sitemaps/sitemap.xml'];
        $found = [];
        foreach ($candidates as $path) {
            if (file_exists($this->rootDir . $path)) {
                $found[] = [
                    'type' => 'local',
                    'path' => $path,
                    'url' => $this->baseUrl . $path
                ];
            }
        }
        return $found;
    }

    public function compareSitemapToFiles($localSitemapPath)
    {
        $fullPath = $this->rootDir . $localSitemapPath;
        if (!file_exists($fullPath))
            return null;

        $content = @file_get_contents($fullPath);
        $urls = [];
        if ($content && preg_match_all('/<loc>(.*?)<\/loc>/', $content, $matches)) {
            foreach ($matches[1] as $url) {
                $rel = str_replace($this->baseUrl, '', $url);
                $rel = '/' . ltrim($rel, '/');
                $urls[] = $rel;
            }
        }

        $scan = $this->scanStructure(true);
        $files = $scan['files'];

        $missingInSitemap = array_diff($files, $urls);
        $ghostPages = array_diff($urls, $files);

        return [
            'sitemap_urls' => count($urls),
            'local_files' => count($files),
            'missing_in_sitemap' => array_values($missingInSitemap),
            'ghost_pages' => array_values($ghostPages),
            'suggestion' => count($missingInSitemap) > (count($files) * 0.4)
                ? "We found many files not in your sitemap. Local folder scan is recommended."
                : "Your sitemap is accurate. Using it will preserve SEO."
        ];
    }

    protected function isExcluded($path)
    {
        $path = str_replace('\\', '/', $path);
        if ($path === '')
            return false;
        $excludes = ['/KT', '/vendor', '/.git', '/node_modules', '/cache'];
        foreach ($excludes as $ex) {
            if (strpos($path, $ex) === 0)
                return true;
        }
        return false;
    }
}
