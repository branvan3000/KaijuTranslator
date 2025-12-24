<?php

if (php_sapi_name() !== 'cli') {
    // --- Web UI ---
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $baseLang = $_POST['base_lang'] ?? 'es';
        $targetLangs = array_map('trim', explode(',', $_POST['target_langs'] ?? 'en,fr'));
        if (!in_array($baseLang, $targetLangs))
            array_unshift($targetLangs, $baseLang);

        $provider = $_POST['provider'] ?? 'openai';
        $model = $_POST['model'] ?? 'gpt-4o-mini';
        $apiKey = $_POST['api_key'] ?? '';

        // Save Config
        include __DIR__ . '/KT/save_config_helper.php'; 
        save_kaiju_config($baseLang, $targetLangs, $provider, $model, $apiKey);

        header("Location: dashboard.php");
        exit;
    }
    ?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <title>🦖 KT Setup</title>
        <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600&display=swap" rel="stylesheet">
        <style>
            body {
                background: #0f172a;
                color: white;
                font-family: 'Outfit', sans-serif;
                display: flex;
                justify-content: center;
                align-items: center;
                min-height: 100vh;
                margin: 0;
            }

            .card {
                background: rgba(30, 41, 59, 0.8);
                backdrop-filter: blur(10px);
                padding: 40px;
                border-radius: 24px;
                border: 1px solid rgba(255, 255, 255, 0.1);
                width: 400px;
            }

            h1 {
                color: #38bdf8;
                margin-top: 0;
            }

            label {
                display: block;
                margin: 15px 0 5px;
                font-size: 14px;
                color: #94a3b8;
            }

            input,
            select {
                width: 100%;
                background: rgba(0, 0, 0, 0.2);
                border: 1px solid rgba(255, 255, 255, 0.1);
                padding: 10px;
                border-radius: 8px;
                color: white;
                box-sizing: border-box;
            }

            button {
                background: #38bdf8;
                color: #0f172a;
                border: none;
                width: 100%;
                padding: 12px;
                border-radius: 8px;
                font-weight: 600;
                margin-top: 20px;
                cursor: pointer;
            }
        </style>
    </head>

    <body>
        <div class="card">
            <h1>🦖 KT Setup</h1>
            <form method="POST">
                <label>Base Language (e.g. es)</label>
                <input type="text" name="base_lang" placeholder="es" required>
                <label>Target Languages (comma separated)</label>
                <input type="text" name="target_langs" placeholder="en,fr" required>
                <label>Provider</label>
                <select name="provider">
                    <option value="openai">OpenAI</option>
                    <option value="deepseek">DeepSeek</option>
                    <option value="gemini">Gemini</option>
                </select>
                <label>Model</label>
                <input type="text" name="model" placeholder="gpt-4o-mini">
                <label>API Key</label>
                <input type="password" name="api_key">
                <button type="submit">Complete Setup</button>
            </form>
        </div>
    </body>

    </html>
    <?php
    exit;
}

echo "\n";
echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║             KaijuTranslator - Setup Wizard                   ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n";
echo "\n";
echo "Welcome! Let's get your website translated in seconds.\n\n";

// Helper for input
function prompt($question, $default = '')
{
    echo $question . ($default ? " [$default]" : "") . ": ";
    $handle = fopen("php://stdin", "r");
    $line = fgets($handle);
    fclose($handle);
    $input = trim($line);
    return $input ?: $default;
}

// 1. Base Language
$baseLang = prompt("What is your website's main language code? (e.g. es, en)", "es");

// 2. Target Languages
$targetLangsInput = prompt("Which languages do you want to translate into? (comma separated, e.g. en,fr)", "en,fr");
$targetLangs = array_map('trim', explode(',', $targetLangsInput));
// Ensure base lang is in the list
if (!in_array($baseLang, $targetLangs)) {
    array_unshift($targetLangs, $baseLang);
}

// 3. API Key & Provider
echo "\n--- Translation Provider ---\n";
echo "1. OpenAI (gpt-4o-mini, gpt-4o)\n";
echo "2. DeepSeek (deepseek-chat)\n";
echo "3. Gemini (gemini-1.5-flash)\n";
$choice = prompt("Select provider [1-3]", "1");

switch ($choice) {
    case '2':
        $provider = 'deepseek';
        $defaultModel = 'deepseek-chat';
        break;
    case '3':
        $provider = 'gemini';
        $defaultModel = 'gemini-1.5-flash';
        break;
    case '1':
    default:
        $provider = 'openai';
        $defaultModel = 'gpt-4o-mini';
        break;
}

$model = prompt("Enter model name", $defaultModel);
$apiKey = prompt("Enter your $provider API Key (leave empty for mock mode)");

// 4. Generate Config
$configFile = __DIR__ . '/KT/kaiju-config.php';

$configContent = "<?php\n\nreturn [\n";
$configContent .= "    // --- Language Settings ---\n";
$configContent .= "    'base_lang' => '$baseLang',\n";
$configContent .= "    'languages' => " . var_export($targetLangs, true) . ",\n\n";

$configContent .= "    // --- AI Translation ---\n";
$configContent .= "    'translation_provider' => '$provider',\n";
$configContent .= "    'model' => '$model',\n";
$configContent .= "    'api_key' => '$apiKey',\n\n";

$configContent .= "    // --- Advanced Settings (Defaults) ---\n";
$configContent .= "    'mode' => 'on_demand', // 'on_demand' or 'prebuild'\n";
$configContent .= "    'uninstall_password' => 'kaiju123', // Change this!\n";
$configContent .= "    'cache_path' => __DIR__ . '/cache',\n";
$configContent .= "    'sitemaps_path' => __DIR__ . '/../sitemaps/kaiju',\n";
$configContent .= "    'allowed_paths' => [__DIR__ . '/../'],\n";
$configContent .= "    'excluded_paths' => ['KT', 'vendor', '.git'],\n";
$configContent .= "    'seo' => ['hreflang_enabled' => true],\n";
$configContent .= "];\n";

if (file_put_contents($configFile, $configContent)) {
    echo "\n[SUCCESS] Configuration saved to 'KT/kaiju-config.php'!\n";
    echo "\nNext steps:\n";
    echo "1. Run: php KT/cli/build.php\n";
    echo "2. Add the widget to your HTML header.\n";
} else {
    echo "\n[ERROR] Could not write to 'KT/kaiju-config.php'. Check permissions.\n";
}
