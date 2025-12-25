<?php
require_once __DIR__ . '/bootstrap.php';

$config = kaiju_config();
$baseLang = $config['base_lang'] ?? 'es';
$langs = $config['languages'] ?? [];
$cachePath = $config['cache_path'] ?? __DIR__ . '/cache';

// 1. Simple Authentication
session_start();
if (empty($_SESSION['kt_csrf_token'])) {
    $_SESSION['kt_csrf_token'] = bin2hex(random_bytes(32));
}
$pass = $config['uninstall_password'] ?? 'kaiju123';

if (isset($_GET['logout'])) {
    unset($_SESSION['kt_auth']);
    header("Location: dashboard.php");
    exit;
}

if (!isset($_SESSION['kt_auth']) || $_SESSION['kt_auth'] !== true) {
    // Safety check: Ensure base_url is set if we are in a context where it might be used (e.g., CLI build)
    // This specific snippet seems misplaced from a base_url resolution function.
    // Assuming the intent was to add a check related to base_url for dashboard context.
    // The original password check is restored for functional correctness.
    if (isset($_POST['password']) && $_POST['password'] === $pass) {
        session_regenerate_id(true);
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


// 2. State & Actions
$validation = kaiju_validate_config();
$alerts = [
    'success' => [],
    'error' => $validation['errors'],
    'warning' => $validation['warnings']
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // CSRF Check
    $token = $_POST['csrf_token'] ?? '';
    if (empty($token) || $token !== ($_SESSION['kt_csrf_token'] ?? '')) {
        $alerts['error'][] = "CSRF Token Validation Failed. Please refresh and try again.";
    } else {
        if ($_POST['action'] === 'build') {
            define('KT_WEB_BUILD', true);
            ob_start();
            try {
                include __DIR__ . '/cli/build.php';
                $alerts['success'][] = "Build Complete!<pre>" . htmlspecialchars(ob_get_clean()) . "</pre>";
            } catch (\Exception $e) {
                ob_end_clean();
                $alerts['error'][] = "Build Failed: " . $e->getMessage();
            }
        } elseif ($_POST['action'] === 'clear_cache') {
            $files = glob($cachePath . '/*');
            $count = 0;
            if ($files) {
                foreach ($files as $file) {
                    if (is_file($file)) {
                        if (unlink($file))
                            $count++;
                        else
                            $alerts['warning'][] = "Failed to delete: " . basename($file);
                    }
                }
            }
            $alerts['success'][] = "Cache Cleared! ($count files removed)";
        }
    }
}

// Stats
$cacheFiles = is_dir($cachePath) ? glob($cachePath . '/*') : [];
if ($cacheFiles === false)
    $cacheFiles = [];
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
            --error: #ef4444;
            --warning: #fbbf24;
            --success: #4ade80;
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
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 14px;
            border-left: 4px solid;
        }

        .alert-success {
            background: rgba(74, 222, 128, 0.1);
            border-color: var(--success);
            color: var(--success);
        }

        .alert-warning {
            background: rgba(251, 191, 36, 0.1);
            border-color: var(--warning);
            color: var(--warning);
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            border-color: var(--error);
            color: var(--error);
        }

        .alert ul {
            margin: 8px 0 0 20px;
            padding: 0;
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
        <p class="subtitle">Management Console for KaijuTranslator | <a href="?logout=1"
                style="color:var(--accent); text-decoration:none;">Logout</a></p>

        <?php foreach ($alerts['error'] as $msg): ?>
            <div class="alert alert-error"><strong>Error:</strong> <?php echo $msg; ?></div>
        <?php endforeach; ?>

        <?php foreach ($alerts['success'] as $msg): ?>
            <div class="alert alert-success"><?php echo $msg; ?></div>
        <?php endforeach; ?>

        <?php if (!empty($alerts['warning'])): ?>
            <div class="alert alert-warning">
                <strong>Warnings:</strong>
                <ul>
                    <?php foreach ($alerts['warning'] as $err): ?>
                        <li><?php echo htmlspecialchars($err); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
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
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['kt_csrf_token'] ?? ''; ?>">
                <button type="submit" name="action" value="build">Build Stubs</button>
                <button type="submit" name="action" value="clear_cache" class="secondary">Clear Cache</button>
            </form>

            <form method="POST" action="../uninstall.php"
                onsubmit="return confirm('Are you sure you want to uninstall KaijuTranslator? This cannot be undone.');">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['kt_csrf_token'] ?? ''; ?>">
                <button type="submit" class="secondary"
                    style="background:#ef4444; color:white; margin-left: 15px;">Uninstall</button>
            </form>
        </div>
    </div>
</body>

</html>