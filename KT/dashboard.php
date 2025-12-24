<?php
require_once __DIR__ . '/bootstrap.php';

$config = kaiju_config();
$baseLang = $config['base_lang'] ?? 'es';
$langs = $config['languages'] ?? [];
$cachePath = $config['cache_path'] ?? __DIR__ . '/cache';

// 1. Simple Authentication
session_start();
$pass = $config['uninstall_password'] ?? 'kaiju123';

if (isset($_GET['logout'])) {
    unset($_SESSION['kt_auth']);
    header("Location: dashboard.php");
    exit;
}

if (!isset($_SESSION['kt_auth']) || $_SESSION['kt_auth'] !== true) {
    if (isset($_POST['password']) && $_POST['password'] === $pass) {
        $_SESSION['kt_auth'] = true;
    } else {
        ?>
        <!DOCTYPE html>
        <html lang="en">

        <head>
            <meta charset="UTF-8">
            <title>🦖 KT Login</title>
            <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600&display=swap" rel="stylesheet">
            <style>
                body {
                    background: #0f172a;
                    color: #f8fafc;
                    font-family: 'Outfit', sans-serif;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    min-height: 100vh;
                    margin: 0;
                }

                .login-card {
                    background: rgba(30, 41, 59, 0.7);
                    backdrop-filter: blur(12px);
                    border: 1px solid rgba(255, 255, 255, 0.1);
                    padding: 40px;
                    border-radius: 24px;
                    text-align: center;
                    width: 300px;
                }

                input {
                    background: rgba(0, 0, 0, 0.3);
                    border: 1px solid rgba(255, 255, 255, 0.1);
                    color: white;
                    padding: 12px;
                    border-radius: 12px;
                    width: 100%;
                    box-sizing: border-box;
                    margin-bottom: 20px;
                }

                button {
                    background: #38bdf8;
                    color: #0f172a;
                    border: none;
                    padding: 12px;
                    border-radius: 12px;
                    width: 100%;
                    font-weight: 600;
                    cursor: pointer;
                }
            </style>
        </head>

        <body>
            <div class="login-card">
                <h1>🦖 KT</h1>
                <p style="color: #94a3b8; margin-bottom: 20px;">Secure Dashboard Access</p>
                <form method="POST">
                    <input type="password" name="password" placeholder="Password" required autofocus>
                    <button type="submit">Unlock</button>
                </form>
            </div>
        </body>

        </html>
        <?php
        exit;
    }
}

// 3. Config Validation
function kaiju_validate_config()
{
    $config = kaiju_config();
    $errors = [];

    // Language Check
    if (!isset($config['base_lang'])) {
        $errors[] = "Missing 'base_lang' in configuration.";
    } elseif (!in_array($config['base_lang'], $config['languages'] ?? [])) {
        $errors[] = "The 'base_lang' ({$config['base_lang']}) must be present in the 'languages' array.";
    }

    // Provider Check
    $allowedProviders = ['openai', 'deepseek', 'gemini', 'gpt4'];
    if (isset($config['translation_provider']) && !in_array(strtolower($config['translation_provider']), $allowedProviders)) {
        $errors[] = "Invalid 'translation_provider'. Supported: " . implode(', ', $allowedProviders);
    }

    // API Key Check
    if (empty($config['api_key'])) {
        // Not a hard error, but a warning
    }

    return $errors;
}

// 2. Action Handlers
$message = '';
if (isset($_POST['action'])) {
    if ($_POST['action'] === 'build') {
        define('KT_WEB_BUILD', true);
        ob_start();
        include __DIR__ . '/cli/build.php';
        $message = "Build Complete!<pre>" . htmlspecialchars(ob_get_clean()) . "</pre>";
    } elseif ($_POST['action'] === 'clear_cache') {
        $files = glob($cachePath . '/*');
        foreach ($files as $file) {
            if (is_file($file))
                unlink($file);
        }
        $message = "Cache Cleared!";
    }
}

// Stats
$cacheFiles = glob($cachePath . '/*');
$cacheSize = 0;
foreach ($cacheFiles as $f)
    $cacheSize += filesize($f);
$cacheSizeStr = number_format($cacheSize / 1024, 2) . ' KB';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>🦖 KT Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #0f172a;
            --card: rgba(30, 41, 59, 0.7);
            --accent: #38bdf8;
            --text: #f8fafc;
            --text-dim: #94a3b8;
        }

        body {
            background: var(--bg);
            color: var(--text);
            font-family: 'Outfit', sans-serif;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: radial-gradient(circle at top right, #1e293b, #0f172a);
        }

        .container {
            width: 90%;
            max-width: 800px;
            background: var(--card);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 24px;
            padding: 40px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            margin: 20px;
        }

        h1 {
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--accent);
        }

        p.subtitle {
            color: var(--text-dim);
            margin-bottom: 32px;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.05);
            padding: 20px;
            border-radius: 16px;
            text-align: center;
        }

        .stat-val {
            font-size: 24px;
            font-weight: 600;
            display: block;
        }

        .stat-label {
            font-size: 12px;
            color: var(--text-dim);
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .actions {
            display: flex;
            gap: 15px;
        }

        button {
            background: var(--accent);
            color: #0f172a;
            border: none;
            padding: 12px 24px;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            font-family: inherit;
        }

        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(56, 189, 248, 0.3);
        }

        button.secondary {
            background: rgba(255, 255, 255, 0.1);
            color: var(--text);
        }

        button.secondary:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .alert {
            background: rgba(56, 189, 248, 0.1);
            border-left: 4px solid var(--accent);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        pre {
            background: #000;
            padding: 10px;
            border-radius: 8px;
            overflow-x: auto;
            color: #4ade80;
            font-size: 12px;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>🦖 KT Dashboard</h1>
        <p class="subtitle">Management Console for KaijuTranslator</p>

        <?php if ($message): ?>
            <div class="alert"><?php echo $message; ?></div>
        <?php endif; ?>

        <div class="grid">
            <div class="stat-card">
                <span class="stat-val"><?php echo count($langs); ?></span>
                <span class="stat-label">Languages</span>
            </div>
            <div class="stat-card">
                <span class="stat-val"><?php echo count($cacheFiles); ?></span>
                <span class="stat-label">Cached Pages</span>
            </div>
            <div class="stat-card">
                <span class="stat-val"><?php echo $cacheSizeStr; ?></span>
                <span class="stat-label">Cache Size</span>
            </div>
        </div>

        <div class="actions">
            <form method="POST">
                <button type="submit" name="action" value="build">Build Stubs</button>
                <button type="submit" name="action" value="clear_cache" class="secondary">Clear Cache</button>
                <a href="../uninstall.php" style="margin-left:auto;"><button type="button" class="secondary"
                        style="background:#ef4444; color:white;">Uninstall</button></a>
            </form>
        </div>
    </div>
</body>

</html>