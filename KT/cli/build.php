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

// 0. Validate Config
$validation = kaiju_validate_config();
if (!empty($validation['errors'])) {
    echo "CRITICAL: Configuration errors found:\n";
    foreach ($validation['errors'] as $e)
        echo " - $e\n";
    die("Please fix kaiju-config.php and try again.\n");
}
if (!empty($validation['warnings'])) {
    echo "WARNING: Configuration warnings:\n";
    foreach ($validation['warnings'] as $w)
        echo " - $w\n";
}

// 1. Config
$config = kaiju_config();
$languages = $config['languages'];
$baseLang = $config['base_lang'] ?? 'es';
$targetLangs = array_diff($languages, [$baseLang]);

echo "Base Lang: $baseLang\n";
echo "Target Langs: " . implode(', ', $targetLangs) . "\n";

// 2. Scan
if (empty($config['allowed_paths']) || !is_array($config['allowed_paths'])) {
    die("CRITICAL: 'allowed_paths' must be a non-empty array in config.\n");
}

echo "Scanning files...\n";
$scanner = new Scanner(
    $config['allowed_paths'],
    $config['excluded_paths'],
    realpath(__DIR__ . '/../../') // Project root
);
$files = $scanner->scan();

if (empty($files)) {
    echo "WARNING: Found 0 files to translate. Check your 'allowed_paths' or 'excluded_paths'.\n";
    exit;
}
echo "Found " . count($files) . " files.\n";

// 3. Generate Stubs
if (empty($targetLangs)) {
    echo "NOTICE: No target languages defined (only base lang exists). Stubs will not be created.\n";
} else {
    $stubGen = new StubGenerator(realpath(__DIR__ . '/../../'));

    echo "Cleaning obsoletes...\n";
    $del = $stubGen->deleteObsoleteStubs($files, $targetLangs);
    if ($del > 0)
        echo "Deleted $del obsolete stubs.\n";

    echo "Generating stubs...\n";
    $count = $stubGen->createStubs($files, $targetLangs);
    echo "Created $count stubs.\n";
}

// 4. Generate Sitemaps
$seoConfig = $config['seo'] ?? [];
if (!empty($seoConfig['hreflang_enabled'])) {
    if (empty($targetLangs)) {
        echo "NOTICE: No target languages defined. Skipping sitemap generation.\n";
    } else {
        echo "Generating sitemaps...\n";
        $baseUrl = get_cli_base_url();
        if (empty($baseUrl)) {
            echo "CRITICAL: 'base_url' is missing in config and could not be guessed.\n";
            echo "Please define 'base_url' => 'https://yoursite.com' in kaiju-config.php.\n";
            echo "Skipping sitemaps...\n";
        } elseif (!is_valid_base_url($baseUrl)) {
            echo "CRITICAL: 'base_url' is invalid: '$baseUrl'. It MUST include a protocol (http:// or https://).\n";
            echo "Skipping sitemaps...\n";
        } else {
            $sitemapsUrl = $config['sitemaps_url'] ?? null;

            // Heuristic check: if path is custom but URL is missing, warn user
            $sitemapsPath = rtrim($config['sitemaps_path'], '/\\');
            $isDefaultPath = substr($sitemapsPath, -14) === 'sitemaps/kaiju'; // Check end of string

            if (!$sitemapsUrl && !$isDefaultPath) {
                echo "WARNING: Custom 'sitemaps_path' detected but 'sitemaps_url' is missing.\n";
                echo "         Sitemap index will assume default '/sitemaps/kaiju' URL structure, which may be incorrect.\n";
            }

            $sitemapGen = new SitemapGen($config['sitemaps_path'], $baseUrl, $sitemapsUrl);

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
}

echo "Done.\n";
