<?php
// Manually require the runner
require __DIR__ . '/../run.php';

use KaijuTranslator\Core\Router;
use KaijuTranslator\Core\Translator;
use KaijuTranslator\Core\Cache;
use KaijuTranslator\Loopback\Capture;
use KaijuTranslator\Processing\HtmlInjector;
use KaijuTranslator\Core\Logger;

// 1. Initialize Components
$config = kaiju_config();
$router = new Router($config);
$cache = new Cache(kaiju_config('cache_path'));
$translator = new Translator(
    kaiju_config('translation_provider'),
    kaiju_config('api_key'),
    kaiju_config('model')
);
$injector = new HtmlInjector();
$capture = new Capture();

// 2. Determine Context
$lang = defined('KT_LANG') ? KT_LANG : ($config['base_lang'] ?? 'en');
$sourcePath = $router->resolveSourceUrl($lang); // e.g. /about.php

// 3. Cache Lookup
$cacheKey = $cache->generateKey($sourcePath, $lang, ''); // Content hash empty for now, could be improved
$cachedHtml = $cache->get($cacheKey);

if ($cachedHtml) {
    echo $cachedHtml;
    exit;
}

// 4. Capture Original Content
$baseUrl = $router->getBaseUrl($sourcePath);
$originalHtml = $capture->fetch($baseUrl);

if (!$originalHtml) {
    // Fallback: redirects to original? Or shows error?
    // For now simple 404 or redirect
    header("Location: " . $sourcePath);
    exit;
}

// 5. Translate
$translatedHtml = $translator->translateHtml($originalHtml, kaiju_config('base_lang'), $lang);

$finalHtml = $translatedHtml;
if ($config['seo']['hreflang_enabled'] ?? true) {
    // 6. Inject SEO/Switchers
    // Need to know full map of URLs for this page for hreflang
    $translationsMap = [];
    foreach (kaiju_config('languages') as $l) {
        // Assume structure matches
        if ($l === kaiju_config('base_lang')) {
            $translationsMap[$l] = $router->getBaseUrl($sourcePath);
        } else {
            $translationsMap[$l] = $router->getBaseUrl('/' . $l . $sourcePath);
        }
    }

    $finalHtml = $injector->injectSeo($translatedHtml, $lang, $translationsMap, $sourcePath);
}

// 7. Save Cache
$cache->set($cacheKey, $finalHtml);

// 8. Output
echo $finalHtml;
