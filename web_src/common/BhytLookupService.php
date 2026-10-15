<?php

class BhytLookupService
{
    const CACHE_TTL = 300;
    const PERSISTENT_CACHE_TTL = 900;
    const MAX_CACHE_ENTRIES = 2000;

    private $client = null;
    private $responseCache = array();

    public function traCuuThongTin($maThe, $hoTen, $ngaySinh)
    {
        $cacheKey = sha1(
            strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $maThe)) . "\0"
            . $this->normalizeText($hoTen) . "\0"
            . preg_replace('/\D/', '', (string) $ngaySinh)
        );
        if (isset($this->responseCache[$cacheKey])) {
            $cached = $this->responseCache[$cacheKey];
            if ($cached['expires_at'] >= time()) return $cached['response'];
            unset($this->responseCache[$cacheKey]);
        }
        $persistentResponse = $this->readPersistentCache($cacheKey);
        if (is_array($persistentResponse)) {
            $this->rememberResponse($cacheKey, $persistentResponse);
            return $persistentResponse;
        }
        if ($this->client === null) {
            require_once __DIR__ . '/BHYT.php';
            $this->client = new BHXH();
        }
        $response = $this->client->traCuuThongTin($maThe, $hoTen, $ngaySinh);
        $this->writePersistentCache($cacheKey, $response);
        $this->rememberResponse($cacheKey, $response);
        return $response;
    }

    private function rememberResponse($cacheKey, array $response)
    {
        if (count($this->responseCache) >= self::MAX_CACHE_ENTRIES) {
            reset($this->responseCache);
            unset($this->responseCache[key($this->responseCache)]);
        }
        $this->responseCache[$cacheKey] = array(
            'response' => $response,
            'expires_at' => time() + self::CACHE_TTL
        );
    }

    private function readPersistentCache($cacheKey)
    {
        global $connect;
        if (!($connect instanceof mysqli)) return null;
        try {
            $statement = $connect->prepare(
                'SELECT response_json FROM bhyt_response_cache
                 WHERE cache_key = ? AND expires_at > NOW() LIMIT 1'
            );
            if (!$statement) return null;
            $statement->bind_param('s', $cacheKey);
            $statement->execute();
            $result = $statement->get_result();
            $row = $result ? $result->fetch_assoc() : null;
            $statement->close();
            if (!$row) return null;
            $response = json_decode($row['response_json'], true);
            return is_array($response) ? $response : null;
        } catch (Throwable $ignored) {
            return null;
        }
    }

    private function writePersistentCache($cacheKey, array $response)
    {
        global $connect;
        if (!($connect instanceof mysqli)) return;
        $json = json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) return;
        $expiresAt = date('Y-m-d H:i:s', time() + self::PERSISTENT_CACHE_TTL);
        try {
            $statement = $connect->prepare(
                'INSERT INTO bhyt_response_cache (cache_key, response_json, expires_at)
                 VALUES (?, ?, ?)
                 ON DUPLICATE KEY UPDATE response_json = VALUES(response_json),
                                         expires_at = VALUES(expires_at)'
            );
            if (!$statement) return;
            $statement->bind_param('sss', $cacheKey, $json, $expiresAt);
            $statement->execute();
            $statement->close();
            if (random_int(1, 100) === 1) {
                $connect->query('DELETE FROM bhyt_response_cache WHERE expires_at <= NOW()');
            }
        } catch (Throwable $ignored) {
            // Cache failures must never block XML imports.
        }
    }

    private function normalizeText($value)
    {
        $value = trim(preg_replace('/\s+/u', ' ', (string) $value));
        return function_exists('mb_strtoupper')
            ? mb_strtoupper($value, 'UTF-8') : strtoupper($value);
    }
}
