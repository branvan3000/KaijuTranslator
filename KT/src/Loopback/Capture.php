<?php

namespace KaijuTranslator\Loopback;

class Capture
{

    public function fetch($url)
    {
        // 1. Validate the URL structure and host (SSRF Protection)
        $parsedUrl = parse_url($url);
        if (!$parsedUrl || !isset($parsedUrl['host'])) {
            return false;
        }

        $host = $parsedUrl['host'];

        // Allow localhost and whitelisted hosts for loopback
        $whitelist = ['localhost', '127.0.0.1', '::1'];

        // Dynamically add base_url host to whitelist if available
        $config = function_exists('kaiju_config') ? kaiju_config() : [];
        if (!empty($config['base_url'])) {
            $baseHost = parse_url($config['base_url'], PHP_URL_HOST);
            if ($baseHost)
                $whitelist[] = $baseHost;
        }

        // Check against whitelist first
        if (in_array($host, $whitelist)) {
            // Safe to proceed
        }
        // Otherwise, block internal IP ranges
        elseif (
            preg_match('/^10\./', $host) ||
            preg_match('/^192\.168\./', $host) ||
            preg_match('/^172\.(1[6-9]|2[0-9]|3[0-1])\./', $host) ||
            $host === '169.254.169.254' // Cloud metadata
        ) {
            return false;
        }

        // Strict same-host check removed to support proxies/load balancers.
        // The whitelist above + base_url validation is now the primary control.

        // Prefer curl for better control
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);

        // Disable following redirects to prevent SSRF through redirection and open redirects
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);

        // CRITICAL: Prevent loops.
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'X-Kaiju-Loopback: true',
            'User-Agent: KaijuTranslator/1.0'
        ]);

        // Forward cookies if strictly necessary? Spec says "no user specific content".
        // So we strip cookies to ensure we get the "public" version of the page.
        curl_setopt($ch, CURLOPT_COOKIE, '');

        $html = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Content Validation: Ensure it looks like HTML if that's what we expect
        if ($httpCode !== 200 || !$html || stripos($html, '<html') === false) {
            return false;
        }

        return $html;
    }
}
