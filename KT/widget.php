<?php
// Include this file in your website's header or footer
if (!defined('KAIJU_START')) {
    require_once __DIR__ . '/bootstrap.php';
}

$config = kaiju_config();
$langs = $config['languages'] ?? [];
$baseLang = $config['base_lang'] ?? 'en';
$style = $config['widget_style'] ?? 'glass';
$content = $config['widget_content'] ?? 'both';

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
$displayLang = isset($allLangs[$currentLang]) ? $allLangs[$currentLang] : strtoupper($currentLang);

// Helper for flags
function kt_get_flag($code)
{
    // Map code to flag emoji
    // Map code to flagcdn code
    $map = [
        'en' => 'us',
        'es' => 'es',
        'fr' => 'fr',
        'de' => 'de',
        'it' => 'it',
        'pt' => 'pt',
        'ru' => 'ru',
        'zh' => 'cn',
        'ja' => 'jp',
        'ko' => 'kr',
        'nl' => 'nl',
        'tr' => 'tr',
        'pl' => 'pl',
        'sv' => 'se',
        'no' => 'no',
        'da' => 'dk',
        'fi' => 'fi',
        'el' => 'gr',
        'cs' => 'cz',
        'hu' => 'hu',
        'ro' => 'ro',
        'bg' => 'bg',
        'uk' => 'ua',
        'ca' => 'es-ct',
        'eu' => 'es-pv',
        'gl' => 'es-ga',
        'is' => 'is',
        'ga' => 'ie',
        'lv' => 'lv',
        'lt' => 'lt',
        'mt' => 'mt',
        'sk' => 'sk',
        'sl' => 'si',
        'et' => 'ee',
        'be' => 'by',
        'ar' => 'sa',
        'hi' => 'in',
        'id' => 'id',
        'th' => 'th',
        'vi' => 'vn',
        'he' => 'il',
        'ms' => 'my',
        'fa' => 'ir',
        'bn' => 'bd',
        'tl' => 'ph',
        'ur' => 'pk',
        'ta' => 'in',
        'te' => 'in',
        'mr' => 'in',
        'gu' => 'in',
        'kn' => 'in',
        'ml' => 'in',
        'pa' => 'in',
        'si' => 'lk',
        'my' => 'mm',
        'km' => 'kh',
        'lo' => 'la',
        'ka' => 'ge',
        'hy' => 'am',
        'az' => 'az',
        'sw' => 'ke',
        'af' => 'za',
        'zu' => 'za',
        'xh' => 'za',
        'yo' => 'ng',
        'ig' => 'ng',
        'ha' => 'ng',
        'am' => 'et',
        'sq' => 'al',
        'mk' => 'mk',
        'sr' => 'rs',
        'hr' => 'hr',
        'bs' => 'ba',
        'uz' => 'uz'
    ];
    $c = $map[$code] ?? 'un'; // 'un' is generic or use a globe icon
    if ($c === 'un')
        return '🌐';
    return '<img src="https://flagcdn.com/24x18/' . $c . '.png" width="18" height="13" alt="' . $code . '" style="vertical-align:middle; border-radius:3px;">';
}

ob_start();
?>
<div id="kt-widget-root" style="position: fixed; bottom: 25px; right: 25px; z-index: 9999; font-family: sans-serif;">
    <style>
        #kt-widget-root * {
            box-sizing: border-box;
        }

        .kt-trigger {
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            user-select: none;
        }

        /* THEMES */
        .kt-glass {
            background: rgba(15, 23, 42, 0.9);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #fff;
            padding: 12px 24px;
            border-radius: 16px;
            font-size: 14px;
            font-weight: 500;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .kt-minimal {
            background: #ffffff;
            color: #111;
            padding: 10px 20px;
            border-radius: 99px;
            font-size: 14px;
            font-weight: 600;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
            border: 1px solid #f3f4f6;
        }

        .kt-kaiju {
            background: #0f172a;
            color: #10b981;
            border: 2px solid #10b981;
            padding: 12px 24px;
            border-radius: 4px;
            font-weight: 800;
            text-transform: uppercase;
            font-size: 14px;
            letter-spacing: 0.05em;
        }

        .kt-bubble {
            background: #10b981;
            color: #fff;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            font-size: 24px;
            box-shadow: 0 10px 25px rgba(16, 185, 129, 0.4);
        }

        /* DROPDOWN */
        .kt-dropdown {
            display: none;
            position: absolute;
            bottom: 110%;
            right: 0;
            width: 200px;
            background: white;
            border-radius: 12px;
            padding: 8px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            max-height: 300px;
            overflow-y: auto;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .kt-glass .kt-dropdown {
            background: rgba(15, 23, 42, 0.95);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .kt-kaiju .kt-dropdown {
            background: #0f172a;
            border: 2px solid #10b981;
            color: #10b981;
            border-radius: 4px;
        }

        .kt-option {
            padding: 10px 12px;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            color: inherit;
            font-size: 14px;
            transition: background 0.2s;
        }

        .kt-option:hover {
            background: rgba(0, 0, 0, 0.05);
        }

        .kt-glass .kt-option:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .kt-kaiju .kt-option:hover {
            background: rgba(16, 185, 129, 0.1);
        }

        .kt-show {
            display: block;
            animation: ktSlideUp 0.2s ease;
        }

        @keyframes ktSlideUp {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>

    <!-- Trigger -->
    <div class="kt-trigger kt-<?php echo $style; ?>"
        onclick="document.getElementById('kt-dd').classList.toggle('kt-show')">
        <?php if ($style === 'bubble'): ?>
            🌍
        <?php else: ?>
            <?php if ($content === 'flags' || $content === 'both'): ?>
                <span style="margin-right:8px;"><?php echo kt_get_flag($currentLang); ?></span>
            <?php endif; ?>

            <?php if ($content === 'text' || $content === 'both'): ?>
                <?php echo $displayLang; ?>
            <?php endif; ?>

            <span style="opacity:0.5; margin: 0 8px;">|</span>
            <span style="opacity:0.8; font-size:0.9em;">▼</span>
        <?php endif; ?>
    </div>

    <!-- Dropdown -->
    <div id="kt-dd" class="kt-dropdown">
        <?php foreach ($langs as $lang):
            $url = $router->getLocalizedUrl($lang, $sourcePath);
            $name = isset($allLangs[$lang]) ? $allLangs[$lang] : strtoupper($lang);
            $flag = kt_get_flag($lang);
            ?>
            <a href="<?php echo htmlspecialchars($url); ?>" class="kt-option">
                <?php if ($content === 'flags' || $content === 'both'): ?>
                    <span><?php echo $flag; ?></span>
                <?php endif; ?>

                <?php if ($content === 'text' || $content === 'both'): ?>
                    <?php echo $name; ?>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>
    </div>
</div>
<?php
echo ob_get_clean();