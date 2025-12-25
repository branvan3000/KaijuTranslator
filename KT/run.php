<?php
// This file is included by the generated stubs (e.g. /en/about.php)

require_once __DIR__ . '/bootstrap.php';

use KaijuTranslator\Core\Router;
use KaijuTranslator\Core\Translator;
use KaijuTranslator\Core\Cache;
use KaijuTranslator\Loopback\Capture;
use KaijuTranslator\Processing\HtmlInjector;


// 1. Initialize Components
$validation = kaiju_validate_config();
if (!empty($validation['errors'])) {
    die("<h1>KaijuTranslator Configuration Error</h1><ul><li>" . implode("</li><li>", $validation['errors']) . "</li></ul>");
}

$config = kaiju_config();
$router = new Router($config);
$cache = new Cache(kaiju_config('cache_path', __DIR__ . '/cache'));
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

// 3. Capture Original Content
$baseUrl = $router->getBaseUrl($sourcePath);
$originalHtml = $capture->fetch($baseUrl);

if (!$originalHtml) {
    http_response_code(502);
    die("<h1>KaijuTranslator: Failed to capture source content</h1><p>Check if the base site is running at: <a href='$baseUrl'>$baseUrl</a></p>");
}

// 4. Cache Check (with Content Hashing for Invalidation)
$contentHash = md5($originalHtml);
$cacheKey = $cache->generateKey($sourcePath, $lang, $contentHash);
$cachedHtml = $cache->get($cacheKey);

if ($cachedHtml) {
    echo $cachedHtml;
    exit;
}

// 5. Translate
$translatedHtml = $translator->translateHtml($originalHtml, kaiju_config('base_lang'), $lang);

// 6. Inject SEO/Switchers
$translationsMap = [];
foreach (kaiju_config('languages') as $l) {
    $translationsMap[$l] = $router->getBaseUrl($router->getLocalizedUrl($l, $sourcePath));
}

$finalHtml = $injector->injectSeo($translatedHtml, $lang, $translationsMap, $sourcePath, $config);

// 7. Save Cache
$cache->set($cacheKey, $finalHtml);

// 8. Output
echo $finalHtml;
