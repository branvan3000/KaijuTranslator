<?php

require_once __DIR__ . '/../../cli/cli_helper.php';

run_test('CliHelper::get_cli_base_url returns valid URL from config', function () {
    $url = get_cli_base_url();
    assert_true($url !== null, 'Base URL should not be null when config provides one');
    assert_true(is_valid_base_url($url), 'Config base URL should be valid');
    return true;
});

run_test('CliHelper::get_cli_base_url ignores invalid env override', function () {
    $previousEnv = getenv('KAIJU_BASE_URL');
    putenv('KAIJU_BASE_URL=ftp://invalid');

    $url = get_cli_base_url();

    // Restore env
    if ($previousEnv === false) {
        putenv('KAIJU_BASE_URL');
    } else {
        putenv('KAIJU_BASE_URL=' . $previousEnv);
    }

    assert_true(is_valid_base_url($url), 'Function should fall back to a valid URL when env override is invalid');
    return true;
});

run_test('CliHelper::is_valid_base_url validation cases', function () {
    assert_true(is_valid_base_url('https://example.com'), 'https scheme should be accepted');
    assert_true(!is_valid_base_url('ftp://example.com'), 'Non-http schemes should be rejected');
    assert_true(!is_valid_base_url(''), 'Empty strings should be rejected');
    return true;
});
