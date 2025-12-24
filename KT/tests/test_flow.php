<?php
// tests/test_flow.php

// Mock server environment
$folder = basename(realpath(__DIR__ . '/../../'));
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REQUEST_URI'] = "/$folder/en/test_page.php";
$_SERVER['HTTPS'] = 'off';

// Define the constant as the stub would
define('KT_LANG', 'en');

// Manually require the runner
// Note: We need to make sure the relative require in run.php works or we adjust here.
// run.php uses __DIR__ . '/bootstrap.php', so it's fine.
// But run.php expects to be included by a stub in /en/, so it might rely on that for some logic?
// The stub does: require __DIR__ . '/../KT/run.php';

echo "--- Simulating Request to /en/test_page.php ---\n";

// We need to capture the output of run.php
ob_start();
try {
    require __DIR__ . '/../run.php';
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
$output = ob_get_clean();

echo "Output length: " . strlen($output) . "\n";
echo "Preview:\n" . substr($output, 0, 500) . "...\n";

if (strpos($output, '[EN]') !== false) {
    echo "SUCCESS: Translation mock tag [EN] found.\n";
} else {
    echo "WARNING: Translation mock tag not found (API key missing or Capture failed?)\n";
}
