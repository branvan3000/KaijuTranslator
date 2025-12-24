<?php

use KaijuTranslator\Processing\HtmlInjector;

run_test('HtmlInjector::injectSeo (Hreflang)', function () {
    $injector = new HtmlInjector();

    $html = '<html><head><title>Test</title></head><body></body></html>';
    $lang = 'en';
    $map = [
        'es' => 'http://site.com/about.php',
        'en' => 'http://site.com/en/about.php'
    ];

    $result = $injector->injectSeo($html, $lang, $map, '/about.php');

    // Check if link tags exist
    assert_true(strpos($result, 'hreflang="es"') !== false, "Missing hreflang es");
    assert_true(strpos($result, 'hreflang="en"') !== false, "Missing hreflang en");

    return true;
});

run_test('HtmlInjector::injectSeo (Canonical Only)', function () {
    $injector = new HtmlInjector();

    $html = '<html><head></head><body></body></html>';
    $lang = 'es';
    $map = ['es' => 'http://site.com/es/'];
    $config = [
        'seo' => [
            'hreflang_enabled' => false,
            'canonical_strategy' => 'self'
        ]
    ];

    $result = $injector->injectSeo($html, $lang, $map, '/', $config);

    assert_true(strpos($result, 'rel="canonical"') !== false, "Canonical tag missing when hreflang is disabled");
    assert_true(strpos($result, 'hreflang') === false, "Hreflang tags present when disabled");

    return true;
});

run_test('HtmlInjector::injectSeo (No SEO Tags)', function () {
    $injector = new HtmlInjector();

    $html = '<html><head></head><body></body></html>';
    $lang = 'es';
    $map = ['es' => 'http://site.com/es/'];
    $config = [
        'seo' => [
            'hreflang_enabled' => false,
            'canonical_strategy' => 'none'
        ]
    ];

    $result = $injector->injectSeo($html, $lang, $map, '/', $config);

    assert_true(strpos($result, 'rel="canonical"') === false, "Canonical tag present when strategy is none");
    assert_true(strpos($result, 'hreflang') === false, "Hreflang tags present when disabled");

    return true;
});
