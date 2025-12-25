<?php

namespace KaijuTranslator\Processing;

class HtmlInjector
{

    public function injectSeo($html, $lang, $translationsMap, $currentPath, $config = [])
    {
        // translationsMap is array of [lang => url] for hreflang

        $headEnd = strpos($html, '</head>');
        if ($headEnd === false) {
            return $html;
        }

        $tags = '';
        if ($config['seo']['hreflang_enabled'] ?? true) {
            foreach ($translationsMap as $l => $url) {
                $tags .= '<link rel="alternate" hreflang="' . htmlspecialchars($l) . '" href="' . htmlspecialchars($url) . '" />' . "\n";
            }
        }

        // Add canonical link if strategy is set to 'self'
        $strategy = $config['seo']['canonical_strategy'] ?? 'self';
        if ($strategy === 'self' && isset($translationsMap[$lang])) {
            $tags .= '<link rel="canonical" href="' . htmlspecialchars($translationsMap[$lang]) . '" />' . "\n";
        }

        return substr_replace($html, $tags, $headEnd, 0);
    }
}
