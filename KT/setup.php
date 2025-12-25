<?php

require_once __DIR__ . '/KT/bootstrap.php';

$configFile = __DIR__ . '/KT/kaiju-config.php';
$existingConfig = file_exists($configFile) ? include $configFile : [];

if (php_sapi_name() !== 'cli') {
    // --- Web UI ---
    $allLangs = include __DIR__ . '/KT/languages.php';
    $error = '';

    $baseUrlInput = $_POST['base_url'] ?? ($existingConfig['base_url'] ?? '');
    $baseLangInput = $_POST['base_lang'] ?? ($existingConfig['base_lang'] ?? 'es');
    $targetLangsInput = $_POST['target_langs'] ?? ($existingConfig['languages'] ?? []);
    if (!is_array($targetLangsInput)) {
        $targetLangsInput = [];
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $baseUrl = trim($_POST['base_url'] ?? '');
        $baseLang = $baseLangInput;
        $targetLangs = $targetLangsInput;
        if (!in_array($baseLang, $targetLangs))
            array_unshift($targetLangs, $baseLang);

        $provider = $_POST['provider'] ?? 'openai';
        $model = $_POST['model'] ?? 'gpt-4o-mini';
        $apiKey = $_POST['api_key'] ?? '';

        // Save Config
        include __DIR__ . '/KT/save_config_helper.php';
        if (save_kaiju_config($baseUrl, $baseLang, $targetLangs, $provider, $model, $apiKey)) {
            header("Location: KT/dashboard.php");
            exit;
        }

        $error = "Could not save configuration. Ensure the Base URL is a valid http(s) address and that KT/kaiju-config.php is writable.";
    }
    ?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <title>🦖 KT Setup | Global Website</title>
        <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600&display=swap" rel="stylesheet">
        <style>
            body {
                background: #0f172a;
                color: white;
                font-family: 'Outfit', sans-serif;
                padding: 40px;
                margin: 0;
                min-height: 100vh;
                display: flex;
                align-items: center;
            }

            .card {
                background: rgba(30, 41, 59, 0.8);
                backdrop-filter: blur(10px);
                padding: 40px;
                border-radius: 24px;
                border: 1px solid rgba(255, 255, 255, 0.1);
                max-width: 900px;
                margin: auto;
                box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5);
            }

            h1 {
                color: #38bdf8;
                margin-top: 0;
                font-size: 28px;
            }

            .form-grid {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 40px;
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
                padding: 12px;
                border-radius: 8px;
                color: white;
                box-sizing: border-box;
                outline: none;
            }

            input:focus,
            select:focus {
                border-color: #38bdf8;
            }

            .lang-grid {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 8px;
                max-height: 350px;
                overflow-y: auto;
                background: rgba(0, 0, 0, 0.3);
                padding: 15px;
                border-radius: 12px;
                border: 1px solid rgba(255, 255, 255, 0.05);
            }

            .lang-item {
                font-size: 13px;
                display: flex;
                align-items: center;
                gap: 10px;
                padding: 5px;
                border-radius: 6px;
                transition: background 0.2s;
            }

            .lang-item:hover {
                background: rgba(255, 255, 255, 0.05);
            }

            .lang-item input {
                width: auto;
                cursor: pointer;
            }

            .search-box {
                margin-bottom: 15px;
                border-color: rgba(56, 189, 248, 0.3);
            }

            button {
                background: linear-gradient(135deg, #38bdf8, #0ea5e9);
                color: #0f172a;
                border: none;
                width: 100%;
                padding: 18px;
                border-radius: 12px;
                font-weight: 700;
                margin-top: 30px;
                cursor: pointer;
                font-size: 16px;
                text-transform: uppercase;
                letter-spacing: 1px;
                transition: transform 0.2s, box-shadow 0.2s;
            }

            button:hover {
                transform: translateY(-2px);
                box-shadow: 0 10px 20px rgba(56, 189, 248, 0.3);
            }

            ::-webkit-scrollbar {
                width: 6px;
            }

            ::-webkit-scrollbar-thumb {
                background: #38bdf8;
                border-radius: 10px;
            }
        </style>
    </head>

    <body>
        <div class="card">
            <h1>🦖 KT Global Setup</h1>
            <p style="color: #94a3b8; font-size: 14px; margin-bottom: 30px;">Choose your base language and all the target
                markets you want to dominate.</p>
            <?php if ($error): ?>
                <div style="background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.4); color: #fecaca; padding: 12px 18px; border-radius: 12px; margin-bottom: 20px;">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            <form method="POST">
                <div class="form-grid">
                    <div>
                        <label>Site Base URL</label>
                        <input type="text" name="base_url" placeholder="https://example.com"
                            value="<?php echo htmlspecialchars($baseUrlInput); ?>" required>
                        <label>Base Language</label>
                        <select name="base_lang">
                            <?php foreach ($allLangs as $code => $name): ?>
                                <option value="<?= $code ?>" <?= $code == $baseLangInput ? 'selected' : '' ?>><?= $name ?></option>
                            <?php endforeach; ?>
                        </select>
                        <label>Translation Provider</label>
                        <select name="provider">
                            <option value="openai">OpenAI (Recommended)</option>
                            <option value="deepseek">DeepSeek</option>
                            <option value="gemini">Google Gemini</option>
                        </select>
                        <label>AI Model</label>
                        <input type="text" name="model" value="gpt-4o-mini">
                        <label>API Key</label>
                        <input type="password" name="api_key" placeholder="Paste your API key here">
                    </div>
                    <div>
                        <label>Target Languages (Select as many as you want)</label>
                        <input type="text" class="search-box" id="langSearch" placeholder="🔍 Search for a language..."
                            onkeyup="filterLangs()">
                        <div class="lang-grid" id="langGrid">
                            <?php foreach ($allLangs as $code => $name): ?>
                                <div class="lang-item">
                                    <input type="checkbox" name="target_langs[]" value="<?= $code ?>" id="lang_<?= $code ?>"
                                        <?= in_array($code, $targetLangsInput) ? 'checked' : '' ?>>
                                    <label for="lang_<?= $code ?>"
                                        style="margin:0; color:white; cursor:pointer; font-weight: 300;"><?= $name ?></label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <button type="submit">Deploy Global Website</button>
            </form>
        </div>
        <script>
            function filterLangs() {
                var input = document.getElementById('langSearch');
                var filter = input.value.toLowerCase();
                var items = document.getElementsByClassName('lang-item');
                for (var i = 0; i < items.length; i++) {
                    var label = items[i].getElementsByTagName('label')[0];
                    if (label.innerHTML.toLowerCase().indexOf(filter) > -1) {
                        items[i].style.display = "";
                    } else {
                        items[i].style.display = "none";
                    }
                }
            }
        </script>
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

do {
    $defaultBaseUrl = $existingConfig['base_url'] ?? 'https://example.com';
    $baseUrl = prompt("Enter your site base URL (e.g. https://yourdomain.com)", $defaultBaseUrl);
    if (kaiju_is_valid_base_url($baseUrl)) {
        break;
    }
    echo "Invalid base URL. Please include http(s):// and retry.\n";
} while (true);

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
include __DIR__ . '/KT/save_config_helper.php';

if (save_kaiju_config($baseUrl, $baseLang, $targetLangs, $provider, $model, $apiKey)) {
    echo "\n[SUCCESS] Configuration saved to 'KT/kaiju-config.php'!\n";
    echo "\nNext steps:\n";
    echo "1. Run: php KT/cli/build.php\n";
    echo "2. Add the widget to your HTML header.\n";
} else {
    echo "\n[ERROR] Could not write to 'KT/kaiju-config.php'. Check permissions.\n";
}
