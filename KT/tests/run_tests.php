<?php

echo "\n🦖 KaijuTranslator Test Runner 🦖\n";
echo "====================================\n\n";

$passes = 0;
$fails = 0;

function run_test($name, $callback)
{
    global $passes, $fails;
    printf("  %-45s ", $name);
    try {
        if ($callback()) {
            echo "✅ PASS\n";
            $passes++;
        } else {
            echo "❌ FAIL\n";
            $fails++;
        }
    } catch (Exception $e) {
        echo "❌ ERROR: " . $e->getMessage() . "\n";
        $fails++;
    }
}

function assert_true($condition, $message = '')
{
    if (!$condition)
        throw new Exception("Assertion Failed: " . $message);
    return true;
}

function assert_equals($expected, $actual, $message = '')
{
    if ($expected !== $actual)
        throw new Exception("Expected '$expected' but got '$actual'. $message");
    return true;
}

// Load Bootstrap
require_once __DIR__ . '/../bootstrap.php';

// --- Run Unit Tests ---
echo "\n[Unit Tests]\n";
$unitTests = glob(__DIR__ . '/Unit/*.php') ?: [];
foreach ($unitTests as $testFile) {
    require $testFile;
}

// --- Run Integration Tests ---
echo "\n[Integration Tests]\n";
$integrationTests = glob(__DIR__ . '/Integration/*.php') ?: [];
foreach ($integrationTests as $testFile) {
    require $testFile;
}

echo "\n" . str_repeat("=", 40) . "\n";
echo "Summary: $passes Passed, $fails Failed.\n";
echo str_repeat("=", 40) . "\n";
if ($fails > 0)
    exit(1);
