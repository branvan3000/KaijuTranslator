<?php

if (php_sapi_name() !== 'cli' && !defined('KT_WEB_BUILD')) {
    die("Must be run from CLI");
}

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/cli_helper.php';

use KaijuTranslator\Builder\Scanner;
use KaijuTranslator\Builder\StubGenerator;
use KaijuTranslator\Builder\SitemapGen;

echo "--- KaijuTranslator Builder ---\n";

// 1. Config
$config = kaiju_config();
$languages = $config['languages'];
$baseLang = $config['base_lang'];
$targetLangs = array_diff($languages, [$baseLang]);

echo "Base Lang: $baseLang\n";
echo "Target Langs: " . implode(', ', $targetLangs) . "\n";

// 2. Scan
echo "Scanning files...\n";
$scanner = new Scanner(
    $config['allowed_paths'],
    $config['excluded_paths'],
    realpath(__DIR__ . '/../../') // Project root
);
$files = $scanner->scan();
echo "Found " . count($files) . " files.\n";

// 3. Generate Stubs
echo "Generating stubs...\n";
$stubGen = new StubGenerator(realpath(__DIR__ . '/../../'));
$count = $stubGen->createStubs($files, $targetLangs);
echo "Created $count stubs.\n";

// 4. Generate Sitemaps
if ($config['seo']['hreflang_enabled']) {
    echo "Generating sitemaps...\n";
    $baseUrl = get_cli_base_url();
    if (!$baseUrl) {
        echo "WARNING: Could not determine base_url for sitemaps. Please define 'base_url' in kaiju-config.php. Skipping sitemaps.\n";
    } else {
        $sitemapGen = new SitemapGen($config['sitemaps_path'], $baseUrl);

        $generatedSitemaps = [];

        // Base lang sitemap
        echo "  - $baseLang\n";
        $generatedSitemaps[] = $sitemapGen->generate($baseLang, $files);

        // Target langs sitemaps
        foreach ($targetLangs as $lang) {
            echo "  - $lang\n";
            $generatedSitemaps[] = $sitemapGen->generate($lang, $files);
        }

        // Index
        $sitemapGen->generateIndex($generatedSitemaps);
        echo "Sitemap Index generated at $baseUrl\n";
    }
}

echo "Done.\n";
