<?php

require_once 'web_src/bean/ApiValidationConfigPeer.php';
require_once 'web_src/common/ApiValidationService.php';

class apiValidationAction
{
    public static $listRole = 'apiValidation,save,update,delete,test';

    private $request;
    private $peer;

    public function __construct()
    {
        $this->request = new Request();
        $this->peer = new ApiValidationConfigPeer();
        $this->request->setTitle('Cấu hình API kiểm tra XML');
    }

    public function index()
    {
        if (empty($_SESSION['api_validation_csrf'])) {
            $_SESSION['api_validation_csrf'] = bin2hex(random_bytes(32));
        }
        $this->request->setAttribute('apiValidationCsrf', $_SESSION['api_validation_csrf']);
        $this->request->setAttribute(
            'script',
            '<script src="' . _DEFAULT_URL_ . 'js/apiValidation.js?' . _DEFAULT_VERSION_JS_CSS_ . '"></script>'
        );
        $this->request->setModel('www/apiValidation/index.php');
        return true;
    }

    public function getData()
    {
        try {
            return $this->json(array('data' => $this->peer->getList()));
        } catch (RuntimeException $exception) {
            return $this->json(array('data' => array(), 'error' => $exception->getMessage()));
        }
    }

    public function save()
    {
        if (!$this->validCsrf()) return $this->csrfError();
        try {
            $id = $this->peer->insert($this->readItem());
            return $this->json(array('success' => true, 'id' => $id, 'message' => 'Thêm cấu hình API thành công.'));
        } catch (RuntimeException $exception) {
            return $this->json(array('success' => false, 'message' => $exception->getMessage()));
        }
    }

    public function update()
    {
        if (!$this->validCsrf()) return $this->csrfError();
        $id = (int) $this->request->getParameter('id', false);
        if ($id <= 0) return $this->json(array('success' => false, 'message' => 'Mã cấu hình API không hợp lệ.'));
        try {
            $this->peer->update($id, $this->readItem());
            return $this->json(array('success' => true, 'message' => 'Cập nhật cấu hình API thành công.'));
        } catch (RuntimeException $exception) {
            return $this->json(array('success' => false, 'message' => $exception->getMessage()));
        }
    }

    public function delete()
    {
        if (!$this->validCsrf()) return $this->csrfError();
        $id = (int) $this->request->getParameter('id', false);
        if ($id <= 0) return $this->json(array('success' => false, 'message' => 'Mã cấu hình API không hợp lệ.'));
        try {
            if (!$this->peer->delete($id)) {
                return $this->json(array('success' => false, 'message' => 'Cấu hình API không tồn tại.'));
            }
            return $this->json(array('success' => true, 'message' => 'Xóa cấu hình API thành công.'));
        } catch (RuntimeException $exception) {
            return $this->json(array('success' => false, 'message' => $exception->getMessage()));
        }
    }

    public function test()
    {
        if (!$this->validCsrf()) return $this->csrfError();
        $id = (int) $this->request->getParameter('id', false);
        if ($id <= 0) return $this->json(array('success' => false, 'message' => 'Mã cấu hình API không hợp lệ.'));

        try {
            $payload = array(
                'path' => $this->readJsonObject('path_parameters'),
                'query' => $this->readJsonObject('query_parameters'),
                'body' => $this->readJsonObject('body_parameters'),
                'auto' => array()
            );
            $service = new ApiValidationService($this->peer);
            $result = $service->call($id, $payload);
            return $this->json(array(
                'success' => true,
                'message' => 'Gọi API thành công.',
                'response' => $result['response']
            ));
        } catch (RuntimeException $exception) {
            return $this->json(array('success' => false, 'message' => $exception->getMessage()));
        }
    }

    private function readItem()
    {
        $headers = trim($this->request->getParameter('headers', false));
        if ($headers === '') $headers = '{}';
        $decodedHeaders = json_decode($headers, true);
        if (!is_array($decodedHeaders) || array_values($decodedHeaders) === $decodedHeaders && !empty($decodedHeaders)) {
            throw new RuntimeException('Headers phải là JSON object hợp lệ.');
        }
        $headers = json_encode($decodedHeaders, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $item = array(
            'name' => trim($this->request->getParameter('name', false)),
            'endpoint' => trim($this->request->getParameter('endpoint', false)),
            'method' => strtoupper(trim($this->request->getParameter('method', false))),
            'headers' => $headers,
            'timeout_seconds' => (int) $this->request->getParameter('timeout_seconds', false),
            'response_field' => trim($this->request->getParameter('response_field', false)),
            'is_active' => $this->request->getParameter('is_active', false) === '1' ? 1 : 0
        );
        if ($item['name'] === '' || $item['endpoint'] === '' || $item['response_field'] === '') {
            throw new RuntimeException('Vui lòng nhập tên, endpoint và trường response.');
        }
        $urlForValidation = preg_replace('/\{[A-Za-z_][A-Za-z0-9_]*\}/', 'parameter', $item['endpoint']);
        $url = filter_var($urlForValidation, FILTER_VALIDATE_URL);
        $scheme = $url ? strtolower(parse_url($url, PHP_URL_SCHEME)) : '';
        if (!$url || !in_array($scheme, array('http', 'https'), true)) {
            throw new RuntimeException('Endpoint phải là URL HTTP hoặc HTTPS hợp lệ.');
        }
        if (!in_array($item['method'], array('GET', 'POST', 'PUT', 'PATCH'), true)) {
            throw new RuntimeException('Phương thức API không được hỗ trợ.');
        }
        if ($item['timeout_seconds'] < 1 || $item['timeout_seconds'] > 30) {
            throw new RuntimeException('Timeout phải nằm trong khoảng 1 đến 30 giây.');
        }
        return $item;
    }

    private function validCsrf()
    {
        $token = $this->request->getParameter('csrf_token', false);
        return !empty($_SESSION['api_validation_csrf']) && is_string($token)
            && hash_equals($_SESSION['api_validation_csrf'], $token);
    }

    private function readJsonObject($parameter)
    {
        $value = trim($this->request->getParameter($parameter, false));
        if ($value === '') return array();
        $decoded = json_decode($value, true);
        if (!is_array($decoded) || (array_values($decoded) === $decoded && !empty($decoded))) {
            throw new RuntimeException($parameter . ' phải là JSON object hợp lệ.');
        }
        return $decoded;
    }

    private function csrfError()
    {
        return $this->json(array('success' => false, 'message' => 'Phiên làm việc không hợp lệ.'));
    }

    private function json($data)
    {
        return $this->request->json_response(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}
