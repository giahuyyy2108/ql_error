
<?php

class BHXH
{
    private $config;
    private $tokenFile;
    private $token = null;
    private $expireTime = 0;

    const TOKEN_RENEWAL_BUFFER = 60;
    const CONNECT_TIMEOUT = 5;
    const REQUEST_TIMEOUT = 30;

    const LOG_FILE = __DIR__ . '/bhxh_log.txt';

    
    public function __construct(array $config = [])
    {
        // Đọc cấu hình từ file JSON
        $configFile = __DIR__ . '/bhyt_config.json';

        if (!is_file($configFile)) {
            throw new RuntimeException(
                'Không tìm thấy file bhyt_config.json'
            );
        }

        $json = file_get_contents($configFile);
        $fileConfig = json_decode($json, true);

        if (!is_array($fileConfig)) {
            throw new RuntimeException(
                'File bhyt_config.json không hợp lệ'
            );
        }

        // Chuyển tên cấu hình JSON sang tên class sử dụng
        $defaultConfig = [
            'username' => $fileConfig['bhyt_username'] ?? '',
            'password' => $fileConfig['bhyt_password'] ?? '',
            'api_base' => $fileConfig['bhyt_api_base'] ?? '',
            'maCSKCB'  => $fileConfig['maCSKCB'] ?? '',
            'cccdCb'   => $fileConfig['cccdCb'] ?? '',
            'hoTenCb'  => $fileConfig['hoTenCb'] ?? '',
            'token_default_ttl' => 3600
        ];

        // Cho phép ghi đè cấu hình khi khởi tạo
        $this->config = array_merge($defaultConfig, $config);

        $this->tokenFile = __DIR__ . '/bhyt_token.json';

        foreach (
            ['username', 'password', 'api_base', 'maCSKCB']
            as $key
        ) {
            if (empty($this->config[$key])) {
                throw new RuntimeException(
                    "Thiếu cấu hình BHXH: {$key}"
                );
            }
        }

        $this->loadToken();
    }


    // =========================
    // LOG
    // =========================

    private function writeLog($message, $type = 'INFO')
    {
        $line = sprintf(
            "[%s] [%s] [PID:%d] %s\n",
            date('Y-m-d H:i:s'),
            $type,
            getmypid(),
            $message
        );

        error_log($line, 3, self::LOG_FILE);
    }

    // =========================
    // TOKEN STORAGE
    // =========================

    private function loadToken()
    {
        // Xóa trạng thái cũ trước khi đọc.
        $this->token = null;
        $this->expireTime = 0;

        if (!is_file($this->tokenFile)) {
            return false;
        }

        $raw = @file_get_contents($this->tokenFile);

        if ($raw === false) {
            return false;
        }

        $data = json_decode($raw, true);

        if (
            !is_array($data) ||
            empty($data['access_token']) ||
            empty($data['expires_at'])
        ) {
            return false;
        }

        // Không tái sử dụng token của tài khoản khác.
        if (
            ($data['username'] ?? '') !==
            $this->config['username']
        ) {
            return false;
        }

        if (
            ($data['maCSKCB'] ?? '') !==
            $this->config['maCSKCB']
        ) {
            return false;
        }

        $this->token = $data;
        $this->expireTime = (int)$data['expires_at'];

        return true;
    }

    private function saveToken(array $data)
    {
        if (empty($data['access_token'])) {
            throw new RuntimeException(
                'API không trả access_token.'
            );
        }

        $expiresAt = isset($data['expires_at'])
            ? (int)$data['expires_at']
            : time() + (int)(
                $data['expires_in'] ??
                $this->config['token_default_ttl']
            );

        $tokenData = [
            'access_token' => $data['access_token'],
            'id_token'     => $data['id_token'] ?? null,
            'username'     => $this->config['username'],
            'maCSKCB'      => $this->config['maCSKCB'],
            'expires_at'   => $expiresAt,
        ];

        $json = json_encode(
            $tokenData,
            JSON_UNESCAPED_UNICODE |
            JSON_PRETTY_PRINT |
            JSON_THROW_ON_ERROR
        );

        $directory = dirname($this->tokenFile);

        $tmp = tempnam($directory, 'bhyt_');

        if ($tmp === false) {
            throw new RuntimeException(
                'Không tạo được file token tạm.'
            );
        }

        try {
            @chmod($tmp, 0600);

            if (file_put_contents($tmp, $json) === false) {
                throw new RuntimeException(
                    'Không ghi được token.'
                );
            }

            if (!rename($tmp, $this->tokenFile)) {
                throw new RuntimeException(
                    'Không thay thế được file token.'
                );
            }
        } finally {
            if (file_exists($tmp)) {
                @unlink($tmp);
            }
        }

        $this->token = $tokenData;
        $this->expireTime = $expiresAt;

        $this->writeLog(
            'Đã lưu token mới, hết hạn: ' .
            date('Y-m-d H:i:s', $expiresAt)
        );
    }

    private function isTokenValid()
    {
        return (
            is_array($this->token) &&
            !empty($this->token['access_token']) &&
            $this->expireTime >
                time() + self::TOKEN_RENEWAL_BUFFER
        );
    }

    // =========================
    // HTTP CLIENT
    // =========================

    private function sendRequest($url, array $body)
    {
        $json = json_encode(
            $body,
            JSON_UNESCAPED_UNICODE |
            JSON_THROW_ON_ERROR
        );

        $ch = curl_init($url);

        if ($ch === false) {
            throw new RuntimeException(
                'Không khởi tạo được cURL.'
            );
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $json,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json; charset=utf-8',
                'Accept: application/json',
            ],
            CURLOPT_CONNECTTIMEOUT => self::CONNECT_TIMEOUT,
            CURLOPT_TIMEOUT => self::REQUEST_TIMEOUT,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);

        $response = curl_exec($ch);
        $status = (int)curl_getinfo(
            $ch,
            CURLINFO_HTTP_CODE
        );

        $error = curl_error($ch);
        $errno = curl_errno($ch);

        curl_close($ch);

        if ($response === false) {
            $this->writeLog(
                "cURL lỗi {$errno}: {$error}",
                'ERROR'
            );

            throw new RuntimeException(
                'Không kết nối được API BHXH.'
            );
        }

        $decoded = json_decode($response, true);

        if (
            json_last_error() !== JSON_ERROR_NONE ||
            !is_array($decoded)
        ) {
            // An expired BHXH session may redirect to a non-JSON login page.
            // Preserve the status so the caller can refresh the token once.
            if (in_array($status, [301, 302, 307, 308, 401, 403], true)) {
                return [
                    'status' => $status,
                    'data' => [],
                ];
            }

            $this->writeLog(
                "API trả JSON không hợp lệ. HTTP {$status}",
                'ERROR'
            );

            throw new RuntimeException(
                'API BHXH trả dữ liệu không hợp lệ.'
            );
        }

        return [
            'status' => $status,
            'data' => $decoded,
        ];
    }

    // =========================
    // GET TOKEN
    // =========================

    public function getToken()
    {
        // Hàm này chủ động yêu cầu token mới.
        // Mọi lần refresh đều đi qua lock.
        return $this->ensureValidToken(true);
    }

    private function requestNewToken()
    {
        $url = rtrim(
            $this->config['api_base'],
            '/'
        ) . '/api/token/take';

        $body = [
            'username' => $this->config['username'],
            'password' => $this->config['password'],
            'maCSKCB'  => $this->config['maCSKCB'],
        ];

        $result = $this->sendRequest($url, $body);

        if ($result['status'] !== 200) {
            $this->writeLog(
                'Lấy token thất bại. HTTP ' .
                $result['status'],
                'ERROR'
            );

            throw new RuntimeException(
                'Không thể lấy token BHXH.'
            );
        }

        $api = $result['data']['APIKey'] ?? null;

        if (
            !is_array($api) ||
            empty($api['access_token'])
        ) {
            throw new RuntimeException(
                'Phản hồi token không hợp lệ.'
            );
        }

        $this->saveToken($api);

        return $this->token;
    }

    // =========================
    // LOCK + TOKEN RENEWAL
    // =========================

    private function ensureValidToken(
        $forceRefresh = false,
        $rejectedToken = null
    ) {
        if (!$forceRefresh && $this->isTokenValid()) {
            return $this->token;
        }

        $lockFile = $this->tokenFile . '.lock';

        $fh = fopen($lockFile, 'c');

        if ($fh === false) {
            throw new RuntimeException(
                'Không mở được file khóa token.'
            );
        }

        $locked = false;

        try {
            // Only one worker refreshes an expired token. Other workers wait
            // here, then reload and reuse the newly saved token.
            $locked = flock($fh, LOCK_EX);

            if (!$locked) {
                throw new RuntimeException(
                    'Timeout khi chờ khóa token.'
                );
            }

            // Đọc lại sau khi đã lấy lock.
            $this->loadToken();

            // Nếu request khác đã refresh token,
            // dùng token mới thay vì refresh tiếp.
            if ($rejectedToken !== null) {
                if (
                    $this->isTokenValid() &&
                    !hash_equals(
                        (string)$rejectedToken,
                        (string)$this->token['access_token']
                    )
                ) {
                    return $this->token;
                }
            } elseif (
                !$forceRefresh &&
                $this->isTokenValid()
            ) {
                return $this->token;
            }

            $this->writeLog(
                'Đang yêu cầu token BHXH mới.'
            );

            return $this->requestNewToken();

        } finally {
            if ($locked) {
                flock($fh, LOCK_UN);
            }

            fclose($fh);
        }
    }

    // =========================
    // TRA CỨU API
    // =========================

    private function callTraCuuApi(
        array $body,
        array $token
    ) {
        $query = http_build_query([
            'token' => $token['access_token'],
            'id_token' => $token['id_token'] ?? '',
            'username' => $token['username'],
            'password' => $this->config['password'],
            'maCSKCB' => $this->config['maCSKCB'],
        ], '', '&', PHP_QUERY_RFC3986);

        $url = rtrim(
            $this->config['api_base'],
            '/'
        ) . '/api/egw/KQNhanLichSuKCB2024?' . $query;

        // Không ghi URL này vào log vì chứa token.
        return $this->sendRequest($url, $body);
    }

    private function isAuthError(array $result)
    {
        $data = $result['data'];

        return (
            in_array(
                $result['status'],
                [301, 302, 307, 308, 401, 403],
                true
            ) ||
            (string)($data['maKetQua'] ?? '') === '401'
        );
    }

    public function traCuuThongTin($maThe, $hoTen, $ngaySinh)
    {
        $maThe = preg_replace('/[^A-Z0-9]/', '', strtoupper(trim((string) $maThe)));
        $hoTen = trim((string) $hoTen);
        $ngaySinh = trim((string) $ngaySinh);
        $ngaySinhDigits = preg_replace('/\D/', '', $ngaySinh);
        if (strlen($ngaySinhDigits) >= 8) {
            // XML1 có thể dùng yyyyMMddHHmm hoặc yyyyMMddHHmmss, trong khi
            // API tra cứu BHXH chỉ nhận phần ngày sinh yyyyMMdd.
            $ngaySinh = substr($ngaySinhDigits, 0, 8);
        }
        if ($maThe === '' || $hoTen === '' || $ngaySinh === '') {
            throw new InvalidArgumentException('Thiếu mã thẻ, họ tên hoặc ngày sinh.');
        }

        $postData = array(
            'maThe' => $maThe,
            'hoTen' => $hoTen,
            'ngaySinh' => $ngaySinh,
            'hoTenCb' => $this->config['hoTenCb'],
            'cccdCb' => $this->config['cccdCb'],
        );
        $token = $this->ensureValidToken();
        for ($attempt = 0; $attempt < 2; $attempt++) {
            $result = $this->callTraCuuApi($postData, $token);
            if ($this->isAuthError($result)) {
                if ($attempt === 1) throw new RuntimeException('Token BHXH bị từ chối sau khi làm mới.');
                $token = $this->ensureValidToken(true, $token['access_token']);
                continue;
            }
            if ($result['status'] !== 200) {
                throw new RuntimeException('API tra cứu trả HTTP ' . $result['status']);
            }
            if ((string) ($result['data']['maKetQua'] ?? '') === '205' && strlen($ngaySinh) === 8) {
                // Một số hồ sơ BHXH chỉ lưu năm sinh. Nếu ngày đầy đủ bị từ
                // chối, thử lại bằng năm để vẫn đối chiếu được hành chính.
                return $this->traCuuThongTin($maThe, $hoTen, substr($ngaySinh, 0, 4));
            }
            return $result['data'];
        }
        throw new RuntimeException('Không thể hoàn tất tra cứu BHYT.');
    }

    // =========================
    // TRA CỨU THẺ BHYT
    // =========================

    public function traCuuThe()
    {
        header(
            'Content-Type: application/json; charset=utf-8'
        );

        try {
            $raw = file_get_contents('php://input');

            $data = json_decode($raw, true);

            if (!is_array($data)) {
                $this->jsonResponse([
                    'error' => 'InvalidInput',
                    'message' => 'Dữ liệu JSON không hợp lệ.'
                ], 400);
                return;
            }

            $maThe = strtoupper(
                trim((string)($data['sothe'] ?? ''))
            );

            $maThe = preg_replace(
                '/[^A-Z0-9]/',
                '',
                $maThe
            );

            $hoTen = trim(
                (string)($data['hoten'] ?? '')
            );

            $ngaySinh = trim(
                (string)($data['ngaysinh'] ?? '')
            );

            if (
                empty($maThe) ||
                empty($hoTen) ||
                empty($ngaySinh)
            ) {
                $this->jsonResponse([
                    'error' => 'MissingInput',
                    'message' =>
                        'Thiếu mã thẻ, họ tên hoặc ngày sinh.'
                ], 400);
                return;
            }

            if (strlen($maThe) > 15) {
                $this->jsonResponse([
                    'error' => 'InvalidCard',
                    'message' => 'Mã thẻ vượt quá 15 ký tự.'
                ], 400);
                return;
            }

            $this->writeLog('Bắt đầu tra cứu BHYT.');
            $result = $this->traCuuThongTin($maThe, $hoTen, $ngaySinh);
            $this->writeLog('Tra cứu BHYT hoàn tất.');
            $this->jsonResponse($result, 200);
            return;

        } catch (Throwable $e) {

            $this->writeLog(
                'Lỗi tra cứu: ' . $e->getMessage(),
                'ERROR'
            );

            $this->jsonResponse([
                'error' => 'BHXHError',
                'message' =>
                    'Không thể hoàn tất tra cứu BHYT.'
            ], 502);
        }
    }

    // =========================
    // JSON RESPONSE
    // =========================

    private function jsonResponse(
        array $data,
        $status = 200
    ) {
        http_response_code($status);

        echo json_encode(
            $data,
            JSON_UNESCAPED_UNICODE |
            JSON_INVALID_UTF8_SUBSTITUTE
        );
    }
}
