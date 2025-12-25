<?php

namespace KaijuTranslator\Core;

class Cache
{
    protected $cachePath;

    public function __construct($path)
    {
        $this->cachePath = rtrim($path, '/');
        if (!is_dir($this->cachePath)) {
            @mkdir($this->cachePath, 0777, true);
        }
    }

    public function get($key)
    {
        $file = $this->getFilePath($key);
        if (!file_exists($file)) {
            return null;
        }

        $fp = fopen($file, 'rb');
        if (!$fp) {
            return null;
        }

        flock($fp, LOCK_SH);
        $content = stream_get_contents($fp);
        flock($fp, LOCK_UN);
        fclose($fp);

        return $content !== false ? $content : null;
    }

    public function set($key, $content)
    {
        $file = $this->getFilePath($key);
        // Use LOCK_EX to prevent concurrent writes
        $result = @file_put_contents($file, $content, LOCK_EX);
        if ($result === false) {
            error_log("KaijuTranslator: Failed to write cache to $file");
            return false;
        }
        return $result;
    }

    protected function getFilePath($key)
    {
        // Sanitize key locally, usually it's a hash
        return $this->cachePath . '/' . $key . '.html';
    }

    public function generateKey($url, $lang, $contentHash = '')
    {
        // Using a basic salt to make the key less predictable
        $salt = 'kaiju_v1_';
        return hash('sha256', $salt . $url . '|' . $lang . '|' . $contentHash);
    }
}
