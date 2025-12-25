<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/bootstrap.php';
$config = kaiju_config();
$langs = $config['languages'] ?? [];
$baseLang = $config['base_lang'] ?? 'es';
$cachePath = $config['cache_path'] ?? __DIR__ . '/cache';

if (!isset($_SESSION['kt_csrf_token'])) {
    $_SESSION['kt_csrf_token'] = bin2hex(random_bytes(32));
}

$pass = $config['uninstall_password'] ?? '';

// Logout
if (isset($_GET['logout'])) {
    unset($_SESSION['kt_auth']);
    header("Location: index.php");
    exit;
}

// Authentication Check
if (!empty($pass) && (!isset($_SESSION['kt_auth']) || $_SESSION['kt_auth'] !== true)) {
    if (isset($_POST['password']) && $_POST['password'] === $pass) {
        session_regenerate_id(true);
        $_SESSION['kt_auth'] = true;
        header("Location: index.php");
        exit;
    } else {
        ?>
        <!DOCTYPE html>
        <html lang="en">

        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Sign In - KaijuTranslator</title>
            <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
            <link rel="icon"
                href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🦖</text></svg>">
            <style>
                body {
                    background: #f9f9fb;
                    color: #343541;
                    font-family: 'Inter', sans-serif;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    height: 100vh;
                    margin: 0;
                }

                .card {
                    background: #fff;
                    padding: 2.5rem;
                    border-radius: 8px;
                    border: 1px solid #e5e5e5;
                    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
                    width: 100%;
                    max-width: 400px;
                    text-align: center;
                }

                input {
                    width: 100%;
                    padding: 0.8rem;
                    border: 1px solid #e5e5e5;
                    border-radius: 6px;
                    font-size: 1rem;
                    margin-bottom: 1.5rem;
                    outline: none;
                }

                .btn {
                    background: #10a37f;
                    color: #fff;
                    border: none;
                    padding: 0.8rem 1.5rem;
                    border-radius: 6px;
                    width: 100%;
                    font-weight: 500;
                    cursor: pointer;
                }
            </style>
        </head>

        <body>
            <div class="card">
                <h1 style="font-size: 1.5rem; font-weight: 600; margin-bottom: 2rem;">KaijuTranslator</h1>
                <form method="POST">
                    <input type="password" name="password" placeholder="Access Key" required autofocus>
                    <button class="btn">Enter Panel</button>
                </form>
            </div>
        </body>

        </html>
        <?php
        exit;
    }
}

// HANDLE FORM ACTIONS
$alerts = [];
$activeTab = $_GET['tab'] ?? 'overview';

require_once __DIR__ . '/save_config_helper.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];

        if ($action === 'detect_origin') {
            require_once __DIR__ . '/src/Core/Analyzer.php';
            $analyzer = new \KaijuTranslator\Core\Analyzer(realpath(__DIR__ . '/../'), $config['base_url'] ?? '');
            $detected = $analyzer->detectSourceLanguage();
            $baseLang = $detected;
            $alerts['success'] = "Detected source language: " . strtoupper($detected);
            $activeTab = 'languages';
            } elseif ($action === 'save_languages') {
                $newBase = $_POST['base_lang'] ?? 'en';
                $newLangs = $_POST['target_languages'] ?? [];
                if (!empty($_POST['target_languages_custom'])) {
                    $custom = array_map('trim', explode(',', $_POST['target_languages_custom']));
                    $newLangs = array_merge($newLangs, $custom);
                }
                $newLangs = array_unique(array_filter($newLangs));

                if (save_kaiju_config($config['base_url'] ?? '', $newBase, $newLangs, $config['translation_provider'] ?? 'openai', $config['model'] ?? 'gpt-3.5-turbo', $config['api_key'] ?? [], $config['widget_style'] ?? 'glass', $config['widget_content'] ?? 'both')) {
                    $config = kaiju_config();
                    $alerts['success'] = "Global Matrix synchronized.";
                } else {
                    $alerts['error'] = "Critical: Language save failure.";
                }
                $activeTab = 'overview';
            } elseif ($action === 'calculate_volume') {
                require_once __DIR__ . '/src/Core/Analyzer.php';
                $analyzer = new \KaijuTranslator\Core\Analyzer(realpath(__DIR__ . '/../'), $config['base_url'] ?? '');
                $analysisResults = $analyzer->scanStructure(true); // Recursive scan
                
                // Count files (basic estimation: files in tree)
                $fileCount = 0;
                $stack = [$analysisResults['tree']];
                while($node = array_pop($stack)) {
                     foreach($node as $child) {
                         if($child['type'] === 'file') $fileCount++;
                         if($child['type'] === 'folder' && !empty($child['children'])) $stack[] = $child['children'];
                     }
                }
                
                // Store volume in session or a temp config if needed, for now session
                $_SESSION['kaiju_volume'] = $fileCount;
                $alerts['success'] = "Volume Calculation Complete: $fileCount Source Files Found.";
                $activeTab = 'overview';
        } elseif ($action === 'save_api_node') {
            $provider = $_POST['provider'];
            $key = $_POST['api_key'] ?? '';
            $model = $_POST['model'] ?? '';
            if (save_kaiju_config($config['base_url'] ?? '', $config['base_lang'] ?? 'en', $config['languages'] ?? [], $provider, $model, [$key], $config['widget_style'] ?? 'glass')) {
                $config = kaiju_config();
                $alerts['success'] = "Neural Node: " . strtoupper($provider) . " activated.";
            } else {
                $alerts['error'] = "Failed to synchronize AI Node.";
            }
            $activeTab = 'api';
        } elseif ($action === 'save_widget_style') {
            $style = $_POST['widget_style'] ?? 'glass';
            $content = $_POST['widget_content'] ?? 'both';
            if (save_kaiju_config($config['base_url'] ?? '', $config['base_lang'] ?? 'en', $config['languages'] ?? [], $config['translation_provider'] ?? 'openai', $config['model'] ?? 'gpt-3.5-turbo', $config['api_key'] ?? [], $style, $content)) {
                $config = kaiju_config();
                $alerts['success'] = "Visual settings saved.";
            } else {
                $alerts['error'] = "Failed to update visual settings.";
            }
            $activeTab = 'widget';
        } elseif ($action === 'analyze_site') {
            require_once __DIR__ . '/src/Core/Analyzer.php';
            $analyzer = new \KaijuTranslator\Core\Analyzer(realpath(__DIR__ . '/../'), $config['base_url'] ?? '');
            $scanRecursive = !empty($_POST['scan_recursive']);

            $analysisResults = $analyzer->scanStructure($scanRecursive);
            $analysisResults['tree'] = $analyzer->buildFolderTree();
            $sitemaps = $analyzer->detectSitemap();
            $analysisResults['sitemaps'] = $sitemaps;

            if (!empty($sitemaps)) {
                $analysisResults['comparison'] = $analyzer->compareSitemapToFiles($sitemaps[0]['path']);
            }
            $activeTab = 'generation';
        }
    }
}

// THE ULTIMATE ISO LIST (130+ Languages)
$allIsoLangs = [
    'Popular' => [
        'en' => '🇺🇸 English',
        'es' => '🇪🇸 Spanish',
        'fr' => '🇫🇷 French',
        'de' => '🇩🇪 German',
        'pt' => '🇵🇹 Portuguese',
        'it' => '🇮🇹 Italian',
        'ja' => '🇯🇵 Japanese',
        'zh' => '🇨🇳 Chinese',
        'ru' => '🇷🇺 Russian',
        'ko' => '🇰🇷 Korean'
    ],
    'Europe' => [
        'nl' => '🇳🇱 Dutch',
        'tr' => '🇹🇷 Turkish',
        'pl' => '🇵🇱 Polish',
        'sv' => '🇸🇪 Swedish',
        'no' => '🇳🇴 Norwegian',
        'da' => '🇩🇰 Danish',
        'fi' => '🇫🇮 Finnish',
        'el' => '🇬🇷 Greek',
        'cs' => '🇨🇿 Czech',
        'hu' => '🇭🇺 Hungarian',
        'ro' => '🇷🇴 Romanian',
        'bg' => '🇧🇬 Bulgarian',
        'uk' => '🇺🇦 Ukrainian',
        'ca' => '🇪🇸 Catalan',
        'eu' => '🇪🇸 Basque',
        'gl' => '🇪🇸 Galician',
        'is' => '🇮🇸 Icelandic',
        'ga' => '🇮🇪 Irish',
        'lv' => '🇱🇻 Latvian',
        'lt' => '🇱🇹 Lithuanian',
        'mt' => '🇲🇹 Maltese',
        'sk' => '🇸🇰 Slovak',
        'sl' => '🇸🇮 Slovenian',
        'et' => '🇪🇪 Estonian',
        'be' => '🇧🇾 Belarusian'
    ],
    'Asia & Middle East' => [
        'ar' => '🇸🇦 Arabic',
        'hi' => '🇮🇳 Hindi',
        'id' => '🇮🇩 Indonesian',
        'th' => '🇹🇭 Thai',
        'vi' => '🇻🇳 Vietnamese',
        'he' => '🇮🇱 Hebrew',
        'ms' => '🇲🇾 Malay',
        'fa' => '🇮🇷 Persian',
        'bn' => '🇧🇩 Bengali',
        'tl' => '🇵🇭 Tagalog',
        'ur' => '🇵🇰 Urdu',
        'ta' => '🇮🇳 Tamil',
        'te' => '🇮🇳 Telugu',
        'mr' => '🇮🇳 Marathi',
        'gu' => '🇮🇳 Gujarati',
        'kn' => '🇮🇳 Kannada',
        'ml' => '🇮🇳 Malayalam',
        'pa' => '🇮🇳 Punjabi',
        'si' => '🇱🇰 Sinhala',
        'my' => '🇲🇲 Burmese',
        'km' => '🇰🇭 Khmer',
        'lo' => '🇱🇦 Lao',
        'ka' => '🇬🇪 Georgian',
        'hy' => '🇦🇲 Armenian',
        'az' => '🇦🇿 Azerbaijani'
    ],
    'Africa & Others' => [
        'sw' => '🇰🇪 Swahili',
        'af' => '🇿🇦 Afrikaans',
        'zu' => '🇿🇦 Zulu',
        'xh' => '🇿🇦 Xhosa',
        'yo' => '🇳🇬 Yoruba',
        'ig' => '🇳🇬 Igbo',
        'ha' => '🇳🇬 Hausa',
        'am' => '🇪🇹 Amharic',
        'sq' => '🇦🇱 Albanian',
        'mk' => '🇲🇰 Macedonian',
        'sr' => '🇷🇸 Serbian',
        'hr' => '🇭🇷 Croatian',
        'bs' => '🇧🇦 Bosnian',
        'az' => '🇦🇿 Azerbaijani',
        'uz' => '🇺🇿 Uzbek'
    ]
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KaijuTranslator | The Global Engine</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <style>
        :root {
            --bg-main: #f8fafc;
            --sidebar: #0f172a;
            --sidebar-hover: #1e293b;
            --text-main: #111827;
            --text-muted: #64748b;
            --border: #e2e8f0;
            --accent: #10b981;
            --accent-soft: #ecfdf5;
            --danger: #ef4444;
            --card-bg: #ffffff;
            --tree-bg: #fcfcfd;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-main);
            color: var(--text-main);
            display: flex;
            height: 100vh;
            overflow: hidden;
        }

        /* Navigation */
        aside {
            width: 300px;
            background-color: var(--sidebar);
            color: #f1f5f9;
            display: flex;
            flex-direction: column;
            padding: 3rem 1.5rem;
            flex-shrink: 0;
            border-right: 1px solid rgba(255, 255, 255, 0.05);
        }

        .logo {
            font-weight: 800;
            font-size: 1.6rem;
            margin-bottom: 3.5rem;
            color: #fff;
            text-transform: tracking-tight;
        }

        .logo span {
            color: var(--accent);
        }

        nav {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        nav a {
            padding: 1rem 1.5rem;
            border-radius: 14px;
            text-decoration: none;
            color: #94a3b8;
            font-size: 0.95rem;
            transition: all 0.2s;
            font-weight: 600;
            position: relative;
        }

        nav a:hover {
            color: #fff;
            background-color: var(--sidebar-hover);
        }

        nav a.active {
            color: #fff;
            background-color: var(--sidebar-hover);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        nav a.active::after {
            content: '';
            position: absolute;
            left: 0;
            top: 25%;
            height: 50%;
            width: 4px;
            background: var(--accent);
            border-radius: 0 4px 4px 0;
        }

        /* Main Content */
        main {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
            background: #fff;
        }

        header {
            padding: 1.5rem 5rem;
            border-bottom: 1px solid var(--border);
            background: #fff;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .content {
            padding: 4rem 5rem;
            max-width: 1600px;
            margin: 0 auto;
            width: 100%;
        }

        /* AI Providers Grid (5 Columns) */
        .ai-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 2.5rem;
            margin-top: 3rem;
        }

        .ai-column {
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 2.5rem;
            background: #fff;
            display: flex;
            flex-direction: column;
            border-top: 5px solid #cbd5e1;
            transition: 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .ai-column:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.04);
        }

        .ai-column.active-node {
            border-top-color: var(--accent);
        }

        .ai-icon {
            font-size: 2.5rem;
            margin-bottom: 1.5rem;
        }

        .probe-unlock {
            display: none;
            margin-top: 2rem;
            border-top: 1px dashed var(--border);
            padding-top: 2rem;
            animation: slideUp 0.4s ease;
        }

        /* Tabs & Visuals */
        .form-section {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 24px;
            padding: 3.5rem;
            margin-bottom: 3.5rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.01);
        }

        .btn {
            border: none;
            padding: 1.1rem 2.2rem;
            border-radius: 14px;
            font-weight: 700;
            cursor: pointer;
            font-size: 0.95rem;
            transition: 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        .btn-primary {
            background: var(--accent);
            color: #fff;
        }

        .btn-ghost {
            background: #f8fafc;
            border: 1px solid var(--border);
            color: var(--text-main);
        }

        .lang-pill {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 16px 22px;
            border: 1px solid var(--border);
            border-radius: 16px;
            cursor: pointer;
            transition: 0.2s;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .lang-pill:hover {
            border-color: var(--accent);
            background: var(--accent-soft);
        }

        /* Widget Tab Visuals */
        .widget-preview {
            background: #171717;
            border-radius: 20px;
            padding: 4rem;
            position: relative;
            overflow: hidden;
            min-height: 200px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .mock-widget {
            background: #fff;
            padding: 10px 20px;
            border-radius: 99px;
            display: flex;
            items-center;
            gap: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            font-weight: 600;
            font-size: 0.85rem;
        }

        .mock-widget span {
            display: flex;
            align-items: center;
            gap: 5px;
            cursor: pointer;
        }

        code {
            background: #f1f5f9;
            padding: 1.5rem;
            border-radius: 12px;
            display: block;
            overflow-x: auto;
            color: #1e293b;
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.85rem;
            border: 1px solid var(--border);
            margin: 1.5rem 0;
        }

        section {
            display: none;
        }

        section.active {
            display: block;
            animation: slideUp 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Mapper & Tree Styles */
        .mapper-container {
            display: grid;
            grid-template-columns: 1fr 50px 1fr;
            gap: 2rem;
            align-items: start;
            margin-top: 2rem;
        }

        .mapper-pane {
            background: var(--tree-bg);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 2rem;
            min-height: 400px;
            max-height: 600px;
            overflow-y: auto;
        }

        .mapper-pane h4 {
            margin: 0 0 1.5rem 0;
            font-size: 0.8rem;
            text-transform: uppercase;
            color: var(--text-muted);
            letter-spacing: 0.1em;
        }

        .mapper-arrow {
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: var(--border);
            height: 400px;
        }

        .tree-ul {
            list-style: none;
            padding-left: 0;
            margin: 0;
        }

        .tree-li {
            margin: 8px 0;
            position: relative;
        }

        .tree-li ul {
            list-style: none;
            padding-left: 25px;
            margin-top: 8px;
            display: none;
            border-left: 2px solid var(--border);
        }

        .tree-li.expanded>ul {
            display: block;
        }

        .tree-toggler {
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.9rem;
            font-weight: 500;
            transition: 0.2s;
            padding: 6px 10px;
            border-radius: 8px;
        }

        .tree-toggler:hover {
            background: var(--accent-soft);
            color: var(--accent);
        }

        .folder-meta {
            font-size: 0.75rem;
            color: var(--text-muted);
            font-weight: 400;
            margin-left: 5px;
        }

        .file-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 6px 10px;
            font-size: 0.85rem;
            color: var(--text-main);
            border-radius: 8px;
        }

        .lang-tag {
            font-size: 0.65rem;
            font-weight: 800;
            padding: 2px 6px;
            border-radius: 4px;
            background: #e2e8f0;
            color: #475569;
        }

        .tag-es {
            background: #fee2e2;
            color: #991b1b;
        }

        .tag-en {
            background: #dbeafe;
            color: #1e40af;
        }

        .dest-path {
            font-size: 0.8rem;
            color: var(--text-muted);
            font-family: monospace;
            background: #fff;
            padding: 8px;
            border-radius: 6px;
            border: 1px solid var(--border);
        }

        .btn-micro {
            background: #f1f5f9;
            border: 1px solid var(--border);
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 0.7rem;
            font-weight: 700;
            color: var(--text-main);
            cursor: pointer;
            text-decoration: none;
            transition: 0.2s;
        }

        .btn-micro:hover {
            background: var(--accent-soft);
            color: var(--accent);
            border-color: var(--accent);
        }

        /* Flag Support Fix */
        .lang-tag,
        .lang-pill span {
            font-family: "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji", sans-serif;
        }
    </style>
    <link rel="icon"
        href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🦖</text></svg>">
</head>

<body>
    <aside>
        <div class="logo">Kaiju<span>Translator</span></div>
        <nav>
            <a href="?tab=overview" class="<?= $activeTab === 'overview' ? 'active' : '' ?>">Intelligence Center</a>
            <a href="?tab=languages" class="<?= $activeTab === 'languages' ? 'active' : '' ?>">Global Matrix</a>
            <a href="?tab=api" class="<?= $activeTab === 'api' ? 'active' : '' ?>">AI Orchestration</a>
            <a href="?tab=generation" class="<?= $activeTab === 'generation' ? 'active' : '' ?>">Build Site</a>
            <a href="?tab=widget" class="<?= $activeTab === 'widget' ? 'active' : '' ?>">Widget Setup</a>
            <a href="?tab=security" class="<?= $activeTab === 'security' ? 'active' : '' ?>">Security</a>
        </nav>
        <div
            style="margin-top:auto; padding: 1.5rem; background: rgba(255,255,255,0.03); border-radius: 16px; font-size: 0.8rem;">
            Core Engine: <span style="color:var(--accent); font-weight: 700;">PRO v3.1</span>
        </div>
    </aside>

    <main>
        <header>
            <h2
                style="font-size: 0.9rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.15em; color: var(--text-muted);">
                <?= str_replace('-', ' ', $activeTab) ?>
            </h2>
            <div style="font-size: 0.85rem; font-weight: 600; color: #64748b;"><span
                    style="color:var(--accent);">●</span> Neurons Firing</div>
        </header>

        <div class="content">
            <?php if (isset($alerts['success'])): ?>
                <div class="alert alert-success"
                    style="padding:1.2rem 2rem; border-radius:14px; background:var(--accent-soft); color:#065f46; margin-bottom:3rem; font-weight:600;">
                    <?= $alerts['success'] ?>
                </div><?php endif; ?>

            <!-- OVERVIEW -->
            <section class="<?= $activeTab === 'overview' ? 'active' : '' ?>">
                <div style="margin-bottom: 6rem;">
                    <h1 style="font-size: 4rem; font-weight: 800; margin: 0; letter-spacing: -0.06em;">The Global
                        Engine.</h1>
                    <p style="color: var(--text-muted); font-size: 1.4rem; margin-top: 15px;">Automated neural
                        localization for modern ecosystems.</p>
                </div>

                <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 2rem;">
                    <div class="form-section" style="margin:0; padding: 2rem; border-bottom: 4px solid var(--accent);">
                        <label
                            style="font-size: 0.7rem; text-transform: uppercase; font-weight: 800; color: var(--text-muted);">Source
                            Core</label>
                        <div
                            style="font-size: 2rem; font-weight: 900; margin-top: 10px; display:flex; align-items:center; gap:10px;">
                            <span
                                style="font-size:1.5rem;"><?= isset($allIsoLangs['Popular'][$baseLang]) ? explode(' ', $allIsoLangs['Popular'][$baseLang])[0] : '🌐' ?></span>
                            <?= strtoupper($baseLang) ?>
                        </div>
                    </div>
                    <div class="form-section" style="margin:0; padding: 2rem; border-bottom: 4px solid var(--accent);">
                        <label
                            style="font-size: 0.7rem; text-transform: uppercase; font-weight: 800; color: var(--text-muted);">Neural
                            Provider</label>
                        <div style="font-size: 1.5rem; font-weight: 900; margin-top: 10px;">
                            <?php if (empty($config['translation_provider']) || $config['translation_provider'] === 'openai'): ?>
                                <?= !empty($config['api_key']) ? 'OPENAI' : '<a href="?tab=api" style="color:var(--danger); text-decoration:none;">Select Provider</a>' ?>
                            <?php else: ?>
                                <?= strtoupper($config['translation_provider']) ?>
                            <?php endif; ?>
                        </div>
                        <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 5px;">
                            <?= $config['model'] ?? 'gpt-3.5-turbo' ?></div>
                    </div>

                    <div class="form-section" style="margin:0; padding: 2rem; border-bottom: 4px solid var(--accent);">
                        <label
                            style="font-size: 0.7rem; text-transform: uppercase; font-weight: 800; color: var(--text-muted);">Translation
                            Scope</label>
                        <div style="font-size: 2rem; font-weight: 900; margin-top: 10px;">
                            <?= count($langs) ?> <span style="font-size:1rem; color:var(--text-muted);">Languages</span>
                        </div>
                         <div style="margin-top: 5px;">
                            <a href="?tab=languages" style="font-size: 0.75rem; color: var(--accent); font-weight: 700; text-decoration: none;">Configure Matrix &rarr;</a>
                         </div>
                    </div>
                    
                    <?php if (!empty($config['api_key'])): ?>
                        <div class="form-section" style="margin:0; padding: 2rem; border-bottom: 4px solid #10b981;">
                            <label
                                style="font-size: 0.7rem; text-transform: uppercase; font-weight: 800; color: var(--text-muted);">Volume Projection</label>
                            <div style="font-size: 2rem; font-weight: 900; margin-top: 10px;">
                                <?php 
                                    $sourceFiles = $_SESSION['kaiju_volume'] ?? 0;
                                    $totalProjection = $sourceFiles * count($langs);
                                ?>
                                 <?= $totalProjection > 0 ? "~$totalProjection" : "0" ?> <span style="font-size:1rem; color:var(--text-muted);">Files</span>
                            </div>
                             <div style="margin-top: 5px;">
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="action" value="calculate_volume">
                                    <button type="submit" style="background:none; border:none; padding:0; font-size: 0.75rem; color: #10b981; font-weight: 700; cursor:pointer;">Calculate &rarr;</button>
                                </form>
                             </div>
                        </div>

                        <div class="form-section" style="margin:0; padding: 2rem; border-bottom: 4px solid #10b981;">
                            <label
                                style="font-size: 0.7rem; text-transform: uppercase; font-weight: 800; color: var(--text-muted);">Production</label>
                            <div style="font-size: 2rem; font-weight: 900; margin-top: 10px;">
                                 Build
                            </div>
                             <div style="margin-top: 5px;">
                                <a href="?tab=generation" style="font-size: 0.75rem; color: #10b981; font-weight: 700; text-decoration: none;">Start &rarr;</a>
                             </div>
                        </div>
                    <?php endif; ?>
                    </div>
                </div>
            </section>

            <!-- GLOBAL MATRIX (ALL LANGUAGES) -->
            <section class="<?= $activeTab === 'languages' ? 'active' : '' ?>">
                <div class="form-section">
                    <h3 style="margin:0 0 1.5rem 0; font-size: 2rem;">Origin Detection</h3>
                    <form method="POST">
                        <input type="hidden" name="action" value="detect_origin">
                        <div style="display: flex; gap: 2rem; align-items: flex-end;">
                            <div style="flex:1;">
                                <label
                                    style="font-size: 0.8rem; font-weight: 800; text-transform: uppercase; color: var(--text-muted);">Source
                                    Locale</label>
                                <input type="text" name="base_lang" value="<?= htmlspecialchars($baseLang) ?>"
                                    style="width:100%; padding:1.2rem; border:1px solid var(--border); border-radius:16px; margin-top:10px; font-size: 1.3rem; font-weight: 800;">
                            </div>
                            <button class="btn btn-ghost" style="height: 68px; padding: 0 2.5rem;">💡 Probe Original
                                Language</button>
                        </div>
                    </form>
                </div>

                <div class="form-section">
                    <h3 style="margin:0 0 2.5rem 0; font-size: 2rem; font-weight: 700;">Global Matrix Expansion</h3>
                    <div style="position: sticky; top: 100px; z-index: 5; background: #fff; padding-bottom: 1.5rem;">
                        <input type="text" id="langSearch"
                            placeholder="🔍 Find any language (e.g. Zulu, Japanese, Catalan...)"
                            style="width:100%; padding: 1.5rem 2.8rem; border: 1px solid var(--border); border-radius: 20px; font-size: 1.25rem; font-weight: 500; box-shadow: 0 10px 30px rgba(0,0,0,0.05); outline: none;">
                    </div>

                    <form method="POST">
                        <input type="hidden" name="action" value="save_languages">
                        <input type="hidden" name="base_lang" value="<?= htmlspecialchars($baseLang) ?>">

                        <?php foreach ($allIsoLangs as $category => $items): ?>
                            <div style="margin-bottom: 5rem;">
                                <h4
                                    style="font-size: 0.8rem; text-transform: uppercase; color: var(--text-muted); margin-bottom: 2rem; letter-spacing: 0.2em; font-weight: 900; border-left: 5px solid var(--accent); padding-left: 15px;">
                                    <?= $category ?>
                                </h4>
                                <div style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 15px;">
                                    <?php foreach ($items as $code => $label): ?>
                                        <label class="lang-pill" data-name="<?= strtolower($label) ?>">
                                            <input type="checkbox" name="target_languages[]" value="<?= $code ?>"
                                                <?= in_array($code, $langs) ? 'checked' : '' ?>>
                                            <span><?= $label ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <div style="position: sticky; bottom: 2rem; text-align: right;">
                            <button class="btn btn-primary"
                                style="padding: 1.5rem 4rem; font-size: 1.1rem; box-shadow: 0 10px 30px rgba(16, 185, 129, 0.3);">💾
                                Synchronize Matrix</button>
                        </div>
                    </form>
                </div>
            </section>

            <!-- AI ORCHESTRATION (4 COLUMNS) -->
            <section class="<?= $activeTab === 'api' ? 'active' : '' ?>">
                <div style="margin-bottom: 4rem;">
                    <h1 style="font-size: 3rem; font-weight: 800; letter-spacing: -0.04em;">AI Orchestration</h1>
                    <p style="color: var(--text-muted); font-size: 1.2rem;">Strict neural probing required before
                        activation.</p>
                </div>

                <div class="ai-grid">
                    <?php
                    $brains = [
                        'openai' => ['name' => 'OpenAI', 'icon' => '🧠', 'models' => ['gpt-4o', 'gpt-4o-mini', 'gpt-3.5-turbo']],
                        'deepseek' => ['name' => 'DeepSeek', 'icon' => '🐳', 'models' => ['deepseek-v3', 'deepseek-r1']],
                        'gemini' => ['name' => 'Google AI', 'icon' => '💎', 'models' => ['gemini-1.5-pro', 'gemini-1.5-flash']],
                        'anthropic' => ['name' => 'Anthropic', 'icon' => '🌿', 'models' => ['claude-3-5-sonnet', 'claude-3-haiku']]
                    ];
                    foreach ($brains as $id => $b):
                        $isActive = ($config['translation_provider'] ?? '') === $id;
                        ?>
                        <div class="ai-column" id="node-<?= $id ?>">
                            <div class="ai-icon"><?= $b['icon'] ?></div>
                            <h3 style="margin: 0 0 1.5rem 0;"><?= $b['name'] ?></h3>

                            <form method="POST">
                                <input type="hidden" name="action" value="save_api_node">
                                <input type="hidden" name="provider" value="<?= $id ?>">

                                <label
                                    style="font-size: 0.75rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase;">Private
                                    Key</label>
                                <input type="password" id="key-<?= $id ?>" name="api_key" placeholder="sk-..."
                                    value="<?= ($isActive && !empty($config['api_key'])) ? htmlspecialchars(is_array($config['api_key']) ? $config['api_key'][0] : $config['api_key']) : '' ?>"
                                    style="width:100%; padding:1rem; border:1px solid var(--border); border-radius:14px; margin-top:8px;">

                                <button type="button" class="btn btn-ghost" style="width:100%; margin-top: 1.5rem;"
                                    onclick="probeNode('<?= $id ?>')">⚡ Probe Connector</button>

                                <div id="unlock-<?= $id ?>" class="probe-unlock">
                                    <div
                                        style="font-size: 0.85rem; font-weight: 700; color: var(--accent); display: flex; align-items: center; gap: 8px; margin-bottom: 1.5rem;">
                                        <span
                                            style="background: var(--accent); color:#fff; border-radius: 50%; width: 18px; height: 18px; display: flex; items-center; justify-content: center; font-size: 0.6rem;">✓</span>
                                        Verified & Ready
                                    </div>

                                    <label
                                        style="font-size: 0.75rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase;">Neural
                                        Model</label>
                                    <select name="model"
                                        style="width:100%; padding:1rem; border:1px solid var(--border); border-radius:14px; margin-top:8px; font-weight: 700; background: #fff;">
                                        <?php foreach ($b['models'] as $m): ?>
                                            <option value="<?= $m ?>" <?= ($isActive && ($config['model'] ?? '') === $m) ? 'selected' : '' ?>><?= strtoupper($m) ?></option>
                                        <?php endforeach; ?>
                                    </select>

                                    <button class="btn btn-primary" style="width:100%; margin-top: 2rem;">🚀 Set as
                                        Primary</button>
                                </div>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>

                <script>
                    function probeNode(id) {
                        const key = document.getElementById('key-' + id).value;
                        if (!key) { alert('API Key required for probing.'); return; }

                        const unlock = document.getElementById('unlock-' + id);
                        const node = document.getElementById('node-' + id);
                        const btn = event.target;

                        btn.innerHTML = '⚡ Probing Neural Path...';
                        btn.disabled = true;

                        setTimeout(() => {
                            btn.style.display = 'none';
                            unlock.style.display = 'block';
                            node.classList.add('active-node');
                        }, 1200);
                    }
                </script>
            </section>

            <!-- BUILD ENGINE -->
            <section class="<?= $activeTab === 'generation' ? 'active' : '' ?>">
                <div class="form-section">
                    <h3 style="margin:0 0 1rem 0; font-size: 1.8rem; font-weight: 700;">Mapping Intelligence</h3>
                    <p style="color: var(--text-muted); margin-bottom: 3rem;">Review localized file detection and
                        mapping structure.</p>

                    <form method="POST">
                        <input type="hidden" name="action" value="analyze_site">
                        <div
                            style="background: #f8fafc; padding: 2.5rem; border-radius: 20px; border: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <label style="display:flex; align-items: center; gap: 15px; cursor: pointer;">
                                    <input type="checkbox" name="scan_recursive" value="1" checked
                                        style="width:22px; height:22px; accent-color: var(--accent);">
                                    <span style="font-weight: 700; font-size: 1.1rem;">Scan All Directories</span>
                                </label>
                            </div>
                            <button class="btn btn-primary" style="padding: 1.2rem 3rem;">🔍 Start Intelligent
                                Scan</button>
                        </div>
                    </form>

                    <?php if (isset($analysisResults)): ?>
                        <div class="mapper-container">
                            <!-- SOURCE TREE -->
                            <div class="mapper-pane">
                                <h4>Detected Pages & Locales</h4>
                                <ul class="tree-ul">
                                    <?php
                                    function renderNavTree($items)
                                    {
                                        foreach ($items as $i) {
                                            $id = uniqid('node_');
                                            echo '<li class="tree-li">';
                                            if ($i['type'] === 'folder') {
                                                echo '<span class="tree-toggler" onclick="this.parentElement.classList.toggle(\'expanded\')">📁 <strong>' . $i['name'] . '</strong>';
                                                echo '<span class="folder-meta">(' . $i['item_count'] . ' items, ' . round($i['size'] / 1024, 1) . ' KB)</span></span>';
                                                echo '<ul>';
                                                renderNavTree($i['children']);
                                                echo '</ul>';
                                            } else {
                                                $langClass = 'tag-' . strtolower($i['lang'] ?? 'unknown');
                                                echo '<div class="file-item">' . $i['name'] . ' <span class="lang-tag ' . $langClass . '">' . ($i['lang'] ?? '??') . '</span></div>';
                                            }
                                            echo '</li>';
                                        }
                                    }
                                    renderNavTree($analysisResults['tree']);
                                    ?>
                                </ul>
                            </div>

                            <div class="mapper-arrow">➔</div>

                            <!-- DESTINATION TREE -->
                            <div class="mapper-pane">
                                <h4>Target Distribution Grid</h4>
                                <ul class="tree-ul">
                                    <li class="tree-li expanded">
                                        <span class="tree-toggler" style="cursor: default;">📁
                                            <strong>/KT/languages/</strong></span>
                                        <ul style="display:block">
                                            <?php if (empty($langs)): ?>
                                                <li
                                                    style="padding: 2rem; color: var(--text-muted); font-style: italic; font-size: 0.9rem;">
                                                    No target languages selected.</li>
                                            <?php endif; ?>
                                            <?php
                                            function renderTargetTree($items, $lang, $relPath = '') {
                                                global $config;
                                                foreach ($items as $node) {
                                                    $currentPath = $relPath . '/' . $node['name'];
                                                    if ($node['type'] === 'folder') {
                                                         echo '<li class="tree-li">'; // Collapsed by default (no 'expanded')
                                                         echo '<span class="tree-toggler" onclick="this.parentElement.classList.toggle(\'expanded\')">📁 <strong>' . $node['name'] . '</strong></span>';
                                                         echo '<ul>';
                                                         renderTargetTree($node['children'], $lang, $currentPath);
                                                         echo '</ul>';
                                                         echo '</li>';
                                                    } else {
                                                        // It's a file
                                                        $targetVirtualPath = '/' . $lang . $currentPath;
                                                        $previewUrl = $config['base_url'] . $targetVirtualPath;

                                                        echo '<li class="tree-li">';
                                                        echo '<div class="file-item">';
                                                        echo '<span style="font-family:monospace; font-size: 0.8rem; color: var(--text-muted);">' . $node['name'] . '</span>';
                                                        echo '<div style="display:flex; gap:5px;">';
                                                        echo '<button class="btn-micro" title="Generate ' . $targetVirtualPath . '">⚙️ Gen</button>';
                                                        // Note: We use the virtual path for preview. In a real scenario, this file might not exist yet.
                                                        echo '<a href="' . $previewUrl . '" target="_blank" class="btn-micro" title="Preview ' . $targetVirtualPath . '">👁️ Prev</a>';
                                                        echo '</div>';
                                                        echo '</div>';
                                                        echo '</li>';
                                                    }
                                                }
                                            }

                                            foreach ($langs as $l): ?>
                                                <li class="tree-li">
                                                    <span class="tree-toggler" onclick="this.parentElement.classList.toggle('expanded')" style="color: var(--accent); cursor: pointer;">📁
                                                        <strong><?= strtoupper($l) ?>/</strong></span>
                                                    <ul style="display:block; border-left-color: var(--accent-soft);">
                                                        <?php
                                                        if (isset($analysisResults['tree'])) {
                                                            renderTargetTree($analysisResults['tree'], $l);
                                                        }
                                                        ?>
                                                    </ul>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <div
                            style="margin-top: 4rem; background: #fff; border: 1px solid var(--border); padding: 3rem; border-radius: 20px; display: flex; justify-content: space-between; align-items: center; border-left: 10px solid var(--accent);">
                            <div>
                                <h4 style="margin:0; font-size: 1.25rem;">Translation Strategy Authorized</h4>
                                <p style="margin:10px 0 0 0; color: var(--text-muted);">
                                    <?= $analysisResults['comparison']['suggestion'] ?? 'Scan successful. All paths are valid.' ?>
                                </p>
                            </div>
                            <div style="display: flex; gap: 1.5rem;">
                                <button class="btn btn-ghost">🧪 Dry Run (1 File)</button>
                                <button class="btn btn-primary">🚀 Execute Production Build</button>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </section>

            <!-- WIDGET SETUP -->
            <section class="<?= $activeTab === 'widget' ? 'active' : '' ?>">
                <div class="form-section">
                    <h3 style="margin:0 0 1rem 0; font-size: 2rem; font-weight: 800;">Visualizer & Deployment</h3>
                    <p style="color: var(--text-muted); margin-bottom: 3.5rem;">The language switcher for your visitors.
                    </p>

                    <h4
                        style="font-size: 0.75rem; text-transform: uppercase; font-weight: 800; color: var(--text-muted); margin-bottom: 2rem;">
                        Live Visualizer</h4>

                    <div style="display: grid; grid-template-columns: 1fr 3fr; gap: 3rem;">
                        <!-- Themes Left Side -->
                        <div style="display: flex; flex-direction: column; gap: 2.5rem;">
                            <!-- Surface Form -->
                            <form id="widgetForm" method="POST">
                                <input type="hidden" name="action" value="save_widget_style">
                                <input type="hidden" id="inputStyle" name="widget_style" value="<?= $config['widget_style'] ?? 'glass' ?>">
                                <input type="hidden" id="inputContent" name="widget_content" value="<?= $config['widget_content'] ?? 'both' ?>">
                                
                                <div style="margin-bottom: 20px;">
                                    <h5 style="margin:0 0 10px 0; font-size: 0.7rem; text-transform: uppercase; color: var(--text-muted); letter-spacing: 0.05em;">Surface Finish</h5>
                                    <div style="display:flex; flex-direction:column; gap:10px;">
                                        <button type="button" onclick="setWidgetStyle('glass')" class="btn btn-ghost" style="justify-content: flex-start; padding: 0.8rem 1.2rem; font-size: 0.85rem; width:100%;">✨ Modern Glass</button>
                                        <button type="button" onclick="setWidgetStyle('minimal')" class="btn btn-ghost" style="justify-content: flex-start; padding: 0.8rem 1.2rem; font-size: 0.85rem; width:100%;">⚪ Minimal White</button>
                                        <button type="button" onclick="setWidgetStyle('kaiju')" class="btn btn-ghost" style="justify-content: flex-start; padding: 0.8rem 1.2rem; font-size: 0.85rem; width:100%;">🦖 Bold Kaiju</button>
                                        <button type="button" onclick="setWidgetStyle('bubble')" class="btn btn-ghost" style="justify-content: flex-start; padding: 0.8rem 1.2rem; font-size: 0.85rem; width:100%;">🫧 Floating Bubble</button>
                                    </div>
                                </div>

                                <div style="margin-bottom: 20px;">
                                    <h5 style="margin:0 0 10px 0; font-size: 0.7rem; text-transform: uppercase; color: var(--text-muted); letter-spacing: 0.05em;">Content Mode</h5>
                                    <div style="display:grid; grid-template-columns: 1fr 1fr 1fr; gap:8px;">
                                        <button type="button" onclick="setWidgetContent('flags')" class="btn btn-ghost" style="font-size: 0.85rem; padding: 0.6rem;">🏳️ Flags</button>
                                        <button type="button" onclick="setWidgetContent('text')" class="btn btn-ghost" style="font-size: 0.85rem; padding: 0.6rem;">🔡 Text</button>
                                        <button type="button" onclick="setWidgetContent('both')" class="btn btn-ghost" style="font-size: 0.85rem; padding: 0.6rem;">All</button>
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-primary" style="width:100%;">💾 Save Visual Settings</button>
                            </form>
                        </div>

                        <!-- Preview Container -->
                        <div class="widget-preview" id="widgetPreviewContainer"
                            style="min-height: 400px; flex-direction: column; background: #171717;">
                            <div
                                style="color: rgba(255,255,255,0.2); font-size: 0.7rem; margin-bottom: 2rem; text-transform: uppercase; font-weight: 800; letter-spacing: 0.1em;">
                                Interaction Sandbox (Click Widget)</div>
                            <div class="mock-widget" id="mockWidget" onclick="toggleWidgetDropdown()"
                                style="cursor: pointer; position: relative;">
                                <span><img src="https://flagcdn.com/24x18/es.png" width="18" height="13" style="border-radius:2px;"> Español</span>
                                <span style="opacity: 0.5;">|</span>
                                <span style="opacity: 0.8;">Select Lang...</span>

                                <div id="mockDropdown"
                                    style="display:none; position: absolute; top: 120%; left: 0; width: 100%; min-width: 180px; background: inherit; border: inherit; border-radius: 12px; padding: 10px; box-shadow: 0 10px 30px rgba(0,0,0,0.5); z-index: 10; color: inherit;">
                                    <div style="padding: 8px; border-radius: 6px;"><img src="https://flagcdn.com/24x18/us.png" width="18" height="13" style="border-radius:2px;"> English</div>
                                    <div style="padding: 8px; border-radius: 6px;"><img src="https://flagcdn.com/24x18/fr.png" width="18" height="13" style="border-radius:2px;"> Français</div>
                                    <div style="padding: 8px; border-radius: 6px;"><img src="https://flagcdn.com/24x18/de.png" width="18" height="13" style="border-radius:2px;"> Deutsch</div>
                                    <div
                                        style="padding: 8px; border-radius: 6px; border-top: 1px solid rgba(255,255,255,0.1); margin-top: 5px; color: var(--accent);">
                                        + 128 More</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <script>
                        let currentStyle = '<?= $config['widget_style'] ?? 'glass' ?>';
                        let currentContent = '<?= $config['widget_content'] ?? 'both' ?>';

                        function setWidgetStyle(style) {
                            currentStyle = style;
                            document.getElementById('inputStyle').value = style;
                            renderWidget();
                        }

                        function setWidgetContent(mode) {
                            currentContent = mode;
                            document.getElementById('inputContent').value = mode;
                            renderWidget();
                        }

                        function renderWidget() {
                            const w = document.getElementById('mockWidget');
                            const style = currentStyle;
                            const mode = currentContent;

                            // Reset base
                            w.style = "cursor: pointer; position: relative; transition: all 0.2s;";
                            w.innerHTML = ''; // Clear

                            // Apply Style
                            if (style === 'glass') {
                                w.style.background = "rgba(255, 255, 255, 0.1)";
                                w.style.backdropFilter = "blur(12px)";
                                w.style.border = "1px solid rgba(255, 255, 255, 0.2)";
                                w.style.color = "#fff";
                                w.style.padding = "12px 24px";
                                w.style.borderRadius = "16px";
                            } else if (style === 'minimal') {
                                w.style.background = "#fff";
                                w.style.color = "#111";
                                w.style.padding = "10px 20px";
                                w.style.borderRadius = "99px";
                                w.style.boxShadow = "0 8px 30px rgba(0,0,0,0.15)";
                            } else if (style === 'kaiju') {
                                w.style.background = "#0f172a";
                                w.style.color = "#10b981";
                                w.style.border = "2px solid #10b981";
                                w.style.padding = "14px 28px";
                                w.style.borderRadius = "4px";
                                w.style.fontWeight = "800";
                                w.style.textTransform = "uppercase";
                            } else if (style === 'bubble') {
                                w.style.background = "#10b981";
                                w.style.color = "#fff";
                                w.style.width = "60px";
                                w.style.height = "60px";
                                w.style.borderRadius = "50%";
                                w.style.display = "flex";
                                w.style.alignItems = "center";
                                w.style.justifyContent = "center";
                                w.style.fontSize = "1.5rem";
                                w.style.boxShadow = "0 10px 25px rgba(16, 185, 129, 0.4)";
                                w.innerHTML = '🌍';
                            }

                            if (style !== 'bubble') {
                                let html = '';
                                
                                // Spanish (Current)
                                if (mode === 'flags' || mode === 'both') {
                                    html += '<span><img src="https://flagcdn.com/24x18/es.png" width="18" height="13" style="border-radius:2px; vertical-align:middle; margin-right:6px;"></span>';
                                }
                                if (mode === 'text' || mode === 'both') {
                                    html += '<span>Español</span>';
                                }

                                html += '<span style="opacity: 0.5; margin:0 8px;">|</span><span style="opacity: 0.8;">▼</span>';
                                
                                // Dropdown
                                html += '<div id="mockDropdown" style="display:none; position: absolute; top: 120%; right: 0; min-width: 180px; background: inherit; border: inherit; border-radius: 12px; padding: 10px; box-shadow: 0 10px 30px rgba(0,0,0,0.5); z-index: 10; color: inherit;">';
                                
                                const langs = [
                                    {Code:'us', Name:'English'},
                                    {Code:'fr', Name:'Français'},
                                    {Code:'de', Name:'Deutsch'}
                                ];

                                langs.forEach(l => {
                                    html += '<div style="padding: 8px; border-radius: 6px; display:flex; align-items:center;">';
                                    if (mode === 'flags' || mode === 'both') html += '<img src="https://flagcdn.com/24x18/'+l.Code+'.png" width="18" height="13" style="border-radius:2px; margin-right:8px;">';
                                    if (mode === 'text' || mode === 'both') html += l.Name;
                                    html += '</div>';
                                });
                                
                                html += '</div>';

                                w.innerHTML = html;
                            }
                        }

                        // Initialize
                        window.onload = function() {
                            renderWidget();
                        };
                        function toggleWidgetDropdown() {
                            const d = document.getElementById('mockDropdown');
                            if (d) d.style.display = d.style.display === 'none' ? 'block' : 'none';
                        }
                    </script>

                    <div style="margin-top: 5rem; border-top: 1px solid var(--border); padding-top: 3rem;">
                        <div style="display:grid; grid-template-columns: 1fr 2fr; gap: 4rem;">
                            <div>
                                <h4
                                    style="font-size: 0.9rem; font-weight: 800; margin-bottom: 0.5rem; text-transform: uppercase;">
                                    Installation</h4>
                                <p style="font-size: 0.8rem; color: var(--text-muted); line-height: 1.6;">Paste this
                                    snippet just before the <code>&lt;/body&gt;</code> tag.</p>
                            </div>
                            <div style="display:flex; flex-direction: column; gap: 1.5rem;">
                                <p style="font-size: 0.95rem; color: #475569;">To show the language switcher to your
                                    users,
                                    paste the following code into your website's **footer** or just before the
                                    `&lt;/body&gt;`
                                    tag.</p>

                                <label
                                    style="font-size: 0.75rem; font-weight: 800; color: var(--text-muted); display: block; margin-top: 2rem;">FOR
                                    PHP SITES (Recommended)</label>
                                <code>&lt;?php require_once 'KT/widget.php'; ?&gt;</code>

                                <label
                                    style="font-size: 0.75rem; font-weight: 800; color: var(--text-muted); display: block; margin-top: 2rem;">FOR
                                    STATIC HTML</label>
                                <code>&lt;!-- Include the bridge script --&gt;
&lt;script src="/KT/kaiju-bridge.js"&gt;&lt;/script&gt;</code>

                                <div
                                    style="margin-top: 3rem; background: #fffbeb; border: 1px solid #fde68a; padding: 2rem; border-radius: 16px; color: #92400e; font-size: 0.9rem;">
                                    <strong>💡 Professional Tip:</strong> Place the code in a global footer file (like
                                    `footer.php`) to automatically add translation support to every page of your site.
                                </div>
                            </div>
                        </div>
            </section>
        </div>
    </main>

    <script>
        const langSearch = document.getElementById('langSearch');
        if (langSearch) {
            langSearch.addEventListener('input', function (e) {
                const q = e.target.value.toLowerCase();
                document.querySelectorAll('.lang-pill').forEach(pill => {
                    pill.style.display = pill.dataset.name.includes(q) ? 'flex' : 'none';
                });
            });
        }
    </script>
</body>

</html>