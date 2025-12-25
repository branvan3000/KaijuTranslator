<?php
// Include this file in your website's header or footer
// Example: <?php include __DIR__ . '/KT/widget.php'; ?>

if (!defined('KAIJU_START')) {
require_once __DIR__ . '/bootstrap.php';
}

$config = kaiju_config();
$langs = $config['languages'];
$baseLang = $config['base_lang'];

// Determine current lang
$currentLang = defined('KT_LANG') ? KT_LANG : $baseLang;

// Determine source path
if (class_exists('KaijuTranslator\Core\Router')) {
$router = new KaijuTranslator\Core\Router($config);
$sourcePath = $router->resolveSourceUrl($currentLang);
} else {
$sourcePath = $_SERVER['REQUEST_URI'];
}

$allLangs = include __DIR__ . '/languages.php';

// Generate CSRF token if not exists (for dashboard/other POST actions)
if (session_status() === PHP_SESSION_NONE) {
session_start();
}
if (empty($_SESSION['kt_csrf_token'])) {
$_SESSION['kt_csrf_token'] = bin2hex(random_bytes(32));
}

ob_start();
?>
<div class="kaiju-widget" style="position: fixed; bottom: 25px; right: 25px; z-index: 9999;">
    <style>
        .kaiju-widget select {
            background: rgba(15, 23, 42, 0.8);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 12px 18px;
            border-radius: 14px;
            backdrop-filter: blur(12px);
            font-family: sans-serif;
            font-size: 14px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
            outline: none;
            cursor: pointer;
            transition: all 0.3s;
            border-left: 4px solid #38bdf8;
        }
    </style>
    <select onchange="window.location.href=this.value">
        <?php foreach ($langs as $lang):
            $url = $router->getLocalizedUrl($lang, $sourcePath);
            $selected = ($lang === $currentLang) ? 'selected' : '';
            $langName = isset($allLangs[$lang]) ? $allLangs[$lang] : strtoupper($lang);
            ?>
            <option value="<?php echo htmlspecialchars($url); ?>" <?php echo $selected; ?>><?php echo $langName; ?></option>
        <?php endforeach; ?>
    </select>
</div>
<?php
echo ob_get_clean();