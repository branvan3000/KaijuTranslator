<?php

namespace KaijuTranslator\Core;

class Translator
{
    protected $apiKey;
    protected $provider;
    protected $model;

    public function __construct($provider, $apiKey, $model = null)
    {
        $this->provider = $provider;
        $this->apiKey = $apiKey;
        $this->model = $model;
    }

    public function translateHtml($html, $sourceLang, $targetLang)
    {
        if (empty($this->apiKey)) {
            return "<!-- KT: Mock Mode (No API Key) -->\n" . $html;
        }

        switch (strtolower($this->provider)) {
            case 'openai':
            case 'gpt4':
                return $this->callOpenAI($html, $sourceLang, $targetLang);
            case 'gemini':
                return $this->callGemini($html, $sourceLang, $targetLang);
            case 'deepseek':
            default:
                return $this->callDeepSeek($html, $sourceLang, $targetLang);
        }
    }

    protected function callOpenAI($content, $source, $target)
    {
        $url = "https://api.openai.com/v1/chat/completions";
        $model = $this->model ?: 'gpt-4o-mini';

        $data = [
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => "Translate the following HTML from $source to $target. Preserve all HTML tags and structure exactly. Only translate the visible text."],
                ['role' => 'user', 'content' => $content]
            ],
            'temperature' => 0.3
        ];

        return $this->makeRequest($url, $data, [
            "Authorization: Bearer {$this->apiKey}",
            "Content-Type: application/json"
        ]);
    }

    protected function callDeepSeek($content, $source, $target)
    {
        $url = "https://api.deepseek.com/v1/chat/completions";
        $model = $this->model ?: 'deepseek-chat';

        $data = [
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => "You are a professional translator. Translate HTML from $source to $target. Keep all tags/attributes. Return only the translated HTML."],
                ['role' => 'user', 'content' => $content]
            ]
        ];

        return $this->makeRequest($url, $data, [
            "Authorization: Bearer {$this->apiKey}",
            "Content-Type: application/json"
        ]);
    }

    protected function callGemini($content, $source, $target)
    {
        $model = $this->model ?: 'gemini-1.5-flash';
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$this->apiKey}";

        $data = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => "Translate this HTML from $source to $target. Keep HTML structure intact:\n\n" . $content]
                    ]
                ]
            ]
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);

        $response = curl_exec($ch);
        $err = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($err) {
            return "<!-- KT Gemini Network Error: $err -->" . $content;
        }

        if ($httpCode !== 200) {
            $snippet = substr($response, 0, 200);
            return "<!-- KT Gemini API Error: HTTP $httpCode - $snippet -->" . $content;
        }

        $res = json_decode($response, true);
        if (!$res) {
            return "<!-- KT Gemini Error: Invalid JSON response -->" . $content;
        }

        if (isset($res['error'])) {
            return "<!-- KT Gemini API Error Message: " . ($res['error']['message'] ?? 'Unknown error') . " -->" . $content;
        }

        $translated = $res['candidates'][0]['content']['parts'][0]['text'] ?? null;
        if (!$translated) {
            return "<!-- KT Gemini Error: Invalid response structure -->" . $content;
        }

        return $translated;
    }

    protected function makeRequest($url, $data, $headers)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);
        $err = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $originalContent = $data['messages'][1]['content'] ?? '';

        if ($err) {
            return "<!-- KT Network Error: $err -->" . $originalContent;
        }

        if ($httpCode !== 200) {
            $snippet = substr($response, 0, 200);
            return "<!-- KT API Error: HTTP $httpCode - $snippet -->" . $originalContent;
        }

        $res = json_decode($response, true);
        if (!$res) {
            return "<!-- KT Error: Invalid JSON response from API -->" . $originalContent;
        }

        if (isset($res['choices'][0]['message']['content'])) {
            return $res['choices'][0]['message']['content'];
        }

        if (isset($res['error']['message'])) {
            return "<!-- KT API Error Message: {$res['error']['message']} -->" . $originalContent;
        }

        return "<!-- KT Error: Unexpected API response structure -->" . $originalContent;
    }
}
