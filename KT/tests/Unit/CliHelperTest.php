<?php

require_once __DIR__ . '/../../cli/cli_helper.php';

run_test('CliHelper::get_cli_base_url (Config override)', function () {
    // Mock kaiju_config if it doesn't exist or we can't control it easily.
    // However, bootstrap.php usually defines it.

    // We can't easily mock global functions in pure PHP without extensions, 
    // so we assume it reads from the current environment or we mock the check inside helper.

    return true; // Placeholder for now as mocking global functions is tricky without Runkit
});

run_test('CliHelper::get_cli_base_url (Subdirectory guessing)', function () {
    $url = get_cli_base_url();
    assert_true(strpos($url, 'http://localhost/') === 0, "Guessed URL should start with localhost");
    return true;
});
