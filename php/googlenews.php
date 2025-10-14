<?php
class GoogleDecoder {
    private $proxy;

    public function __construct($proxy = null) {
        $this->proxy = $proxy;
    }

    private function fetchUrl($url, $method = 'GET', $postData = null, $headers = []) {
        $opts = [
            'http' => [
                'method' => $method,
                'ignore_errors' => true,
                'header' => "",
                'content' => $postData ? $postData : null,
                'timeout' => 15,
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false
            ]
        ];

        if ($this->proxy) {
            $opts['http']['proxy'] = $this->proxy;
            $opts['http']['request_fulluri'] = true;
        }

        // Add headers
        if (!empty($headers)) {
            $opts['http']['header'] = implode("\r\n", array_map(function($k, $v) {
                return "$k: $v";
            }, array_keys($headers), $headers));
        }

        $context = stream_context_create($opts);
        $response = @file_get_contents($url, false, $context);
        if ($response === false) {
            return null;
        }
        return $response;
    }

    public function getBase64Str($sourceUrl) {
        try {
            $parts = parse_url($sourceUrl);
            if (!isset($parts['host']) || strpos($parts['host'], 'news.google.com') === false) {
                return ['status' => false, 'message' => 'Invalid Google News URL format.'];
            }

            $path = explode('/', trim($parts['path'], '/'));
            if (count($path) > 1 && in_array($path[count($path) - 2], ['articles', 'read'])) {
                return ['status' => true, 'base64_str' => end($path)];
            }

            return ['status' => false, 'message' => 'Invalid Google News URL format.'];
        } catch (Exception $e) {
            return ['status' => false, 'message' => 'Error in getBase64Str: ' . $e->getMessage()];
        }
    }

    public function getDecodingParams($base64Str) {
        $urls = [
            "https://news.google.com/articles/$base64Str",
            "https://news.google.com/rss/articles/$base64Str"
        ];

        foreach ($urls as $url) {
            $html = $this->fetchUrl($url);
            if (!$html) continue;

            // Try to find data-n-a-sg and data-n-a-ts in the HTML
            if (preg_match('/data-n-a-sg="([^"]+)"/', $html, $sgMatch) &&
                preg_match('/data-n-a-ts="([^"]+)"/', $html, $tsMatch)) {
                return [
                    'status' => true,
                    'signature' => $sgMatch[1],
                    'timestamp' => $tsMatch[1],
                    'base64_str' => $base64Str
                ];
            }
        }

        return ['status' => false, 'message' => 'Failed to fetch decoding params from Google News.'];
    }

    public function decodeUrl($signature, $timestamp, $base64Str) {
        try {
            $url = "https://news.google.com/_/DotsSplashUi/data/batchexecute";
            $payload = [
                "Fbv4je",
                "[\"garturlreq\",[[\"X\",\"X\",[\"X\",\"X\"],null,null,1,1,\"US:en\",null,1,null,null,null,null,null,0,1],\"X\",\"X\",1,[1,1,1],1,1,null,0,0,null,0],\"$base64Str\",$timestamp,\"$signature\"]"
            ];
            $fReq = "f.req=" . urlencode(json_encode([[$payload]]));

            $headers = [
                "Content-Type" => "application/x-www-form-urlencoded;charset=UTF-8",
                "User-Agent" => "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/129.0.0.0 Safari/537.36"
            ];

            $response = $this->fetchUrl($url, 'POST', $fReq, $headers);
            if (!$response) {
                return ['status' => false, 'message' => 'Failed to connect to Google News.'];
            }

            $parts = explode("\n\n", $response, 2);
            if (count($parts) < 2) {
                return ['status' => false, 'message' => 'Invalid response format.'];
            }

            $jsonData = json_decode($parts[1], true);
            if (!is_array($jsonData)) {
                return ['status' => false, 'message' => 'Failed to parse JSON.'];
            }

            // Extract the decoded URL
            $parsedData = $jsonData[0][2] ?? null;
            if (!$parsedData) {
                return ['status' => false, 'message' => 'No decoded data found.'];
            }

            $decoded = json_decode($parsedData, true);
            if (isset($decoded[1])) {
                return ['status' => true, 'decoded_url' => $decoded[1]];
            }

            return ['status' => false, 'message' => 'Failed to extract decoded URL.'];
        } catch (Exception $e) {
            return ['status' => false, 'message' => 'Error in decodeUrl: ' . $e->getMessage()];
        }
    }

    public function decodeGoogleNewsUrl($sourceUrl) {
        $base64 = $this->getBase64Str($sourceUrl);
        if (!$base64['status']) return $base64;

        $params = $this->getDecodingParams($base64['base64_str']);
        if (!$params['status']) return $params;

        return $this->decodeUrl($params['signature'], $params['timestamp'], $params['base64_str']);
    }
}
?>