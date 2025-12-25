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
    foreach ($validation['errors'] as $error) {
        error_log("KaijuTranslator Critical Error: $error");
    }

    // Friendly Error Screen
    http_response_code(500);
    ?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <title>KaijuTranslator | Configuration Error</title>
        <style>
            body {
                font-family: system-ui, -apple-system, sans-serif;
                background: #fef2f2;
                color: #991b1b;
                display: flex;
                height: 100vh;
                justify-content: center;
                align-items: center;
                margin: 0;
            }

            .card {
                background: white;
                padding: 2rem;
                border-radius: 1rem;
                box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
                max-width: 500px;
                width: 90%;
                border: 1px solid #fee2e2;
            }

            h1 {
                margin-top: 0;
                color: #ef4444;
                font-size: 1.5rem;
                display: flex;
                align-items: center;
                gap: 0.5rem;
            }

            ul {
                background: #fef2f2;
                padding: 1rem 2rem;
                border-radius: 0.5rem;
                border: 1px solid #fecaca;
            }

            li {
                margin-bottom: 0.5rem;
            }

            li:last-child {
                margin-bottom: 0;
            }

            .help {
                margin-top: 1.5rem;
                font-size: 0.875rem;
                color: #7f1d1d;
            }

            a {
                color: #b91c1c;
                font-weight: 600;
                text-decoration: underline;
            }
        </style>
    </head>

    <body>
        <div class="card">
            <h1>🦖 KaijuTranslator Error</h1>
            <p>We found critical configuration issues that prevent the translation engine from running:</p>
            <ul>
                <?php foreach ($validation['errors'] as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
            <p class="help">Please check your <code>KT/kaiju-config.php</code> file or run <code>setup.php</code> to fix
                these issues.</p>
        </div>
    </body>

    </html>
    <?php
    exit;
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
