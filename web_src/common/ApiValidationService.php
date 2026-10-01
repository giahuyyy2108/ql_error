<?php

class ApiValidationService
{
    private $configPeer;

    public function __construct(ApiValidationConfigPeer $configPeer)
    {
        $this->configPeer = $configPeer;
    }

    public function call($configId, array $payload)
    {
        $config = $this->configPeer->getActiveById((int) $configId);
        if ($config === false) {
            throw new RuntimeException('Không tìm thấy cấu hình API đang hoạt động.');
        }

        $headers = array('Accept: application/json');
        $configuredHeaders = $config['headers'] ? json_decode($config['headers'], true) : array();
        if (!is_array($configuredHeaders)) {
            throw new RuntimeException('Headers API không phải JSON hợp lệ.');
        }
        foreach ($configuredHeaders as $name => $value) {
            if (!preg_match('/^[A-Za-z0-9-]+$/', $name)) {
                continue;
            }
            if (is_string($value) && strpos($value, 'env:') === 0) {
                $value = getenv(substr($value, 4));
            }
            $headers[] = $name . ': ' . (string) $value;
        }

        $method = strtoupper($config['method']);
        if (!in_array($method, array('GET', 'POST', 'PUT', 'PATCH'), true)) {
            throw new RuntimeException('Phương thức API không được hỗ trợ.');
        }
        $structured = isset($payload['path']) || isset($payload['query']) || isset($payload['body']);
        $pathParameters = $structured && isset($payload['path']) ? $payload['path'] : array();
        $queryParameters = $structured && isset($payload['query']) ? $payload['query'] : array();
        $bodyParameters = $structured && isset($payload['body']) ? $payload['body'] : array();
        $autoParameters = $structured && isset($payload['auto']) ? $payload['auto'] : ($structured ? array() : $payload);
        if ($method === 'GET') $queryParameters = array_merge($queryParameters, $autoParameters);
        else $bodyParameters = array_merge($bodyParameters, $autoParameters);

        $url = $config['endpoint'];
        foreach ($pathParameters as $name => $value) {
            $placeholder = '{' . $name . '}';
            if (strpos($url, $placeholder) === false) {
                throw new RuntimeException('Endpoint thiếu path parameter ' . $placeholder . '.');
            }
            $url = str_replace($placeholder, rawurlencode((string) $value), $url);
        }
        if (preg_match('/\{[A-Za-z_][A-Za-z0-9_]*\}/', $url, $match)) {
            throw new RuntimeException('Chưa truyền path parameter ' . $match[0] . '.');
        }
        $validUrl = filter_var($url, FILTER_VALIDATE_URL);
        $scheme = $validUrl ? strtolower(parse_url($validUrl, PHP_URL_SCHEME)) : '';
        if (!$validUrl || !in_array($scheme, array('http', 'https'), true)) {
            throw new RuntimeException('Endpoint API không hợp lệ.');
        }
        if (!empty($queryParameters)) {
            $url .= (strpos($url, '?') === false ? '?' : '&') . http_build_query($queryParameters);
        }

        $curl = curl_init();
        if (!empty($bodyParameters)) {
            $body = json_encode($bodyParameters, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $headers[] = 'Content-Type: application/json';
            curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        } elseif ($method !== 'GET') {
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        }

        $timeout = max(1, min(30, (int) $config['timeout_seconds']));
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => min(5, $timeout),
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2
        ));
        if (defined('CURLOPT_PROTOCOLS')) {
            curl_setopt($curl, CURLOPT_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS);
        }

        $body = curl_exec($curl);
        $curlError = curl_error($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($body === false || $curlError !== '') {
            throw new RuntimeException('Không thể kết nối API: ' . $curlError);
        }
        if ($status < 200 || $status >= 300) {
            throw new RuntimeException('API trả về HTTP ' . $status . '.');
        }

        $response = json_decode($body, true);
        if (!is_array($response)) {
            throw new RuntimeException('Phản hồi API không phải JSON hợp lệ.');
        }

        return array('config' => $config, 'response' => $response);
    }
}
