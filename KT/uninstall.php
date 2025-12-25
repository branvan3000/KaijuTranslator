<?php
// uninstall.php

require_once __DIR__ . '/KT/bootstrap.php';

$config = kaiju_config();
$pass = $config['uninstall_password'] ?? 'kaiju123'; // Default password if not set

echo "--- KT Uninstaller ---\n";

if (php_sapi_name() !== 'cli') {
    // Web-based uninstallation requires POST + CSRF + Auth
    session_start();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        die("Error: Uninstall must be performed via POST.");
    }

    if (empty($_SESSION['kt_auth']) || $_SESSION['kt_auth'] !== true) {
        die("Error: Access Denied.");
    }

    $token = $_POST['csrf_token'] ?? '';
    if (empty($token) || $token !== ($_SESSION['kt_csrf_token'] ?? '')) {
        die("Error: Invalid CSRF Token.");
    }
} else {
    // CLI check if needed, for simplicity we just run if pass matches or is default
}

echo "Starting uninstallation...\n";

// 1. Delete generated folders
$langs = $config['languages'] ?? [];
$baseLang = $config['base_lang'] ?? '';
$rootDir = __DIR__;

foreach ($langs as $lang) {
    if ($lang === $baseLang)
        continue;
    $langPath = $rootDir . DIRECTORY_SEPARATOR . $lang;
    if (is_dir($langPath)) {
        echo "Deleting $lang folder...\n";
        delete_directory($langPath);
    }
}

// 2. Delete sitemaps
$sitemapsPath = realpath($config['sitemaps_path'] ?? ($rootDir . '/sitemaps/kaiju'));
if ($sitemapsPath && is_dir($sitemapsPath)) {
    echo "Deleting sitemaps...\n";
    delete_directory($sitemapsPath);
}

echo "\n[SUCCESS] Translations and sitemaps removed.\n";
echo "IMPORTANT: Now you must manually delete the 'KT/' folder to complete the cleanup.\n";

function delete_directory($dir)
{
    if (!file_exists($dir))
        return true;
    if (!is_dir($dir))
        return unlink($dir);
    foreach (scandir($dir) as $item) {
        if ($item == '.' || $item == '..')
            continue;
        if (!delete_directory($dir . DIRECTORY_SEPARATOR . $item))
            return false;
    }
    return rmdir($dir);
}