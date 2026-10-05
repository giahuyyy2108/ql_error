<?php

require_once 'web_src/bean/HeThongPeer.php';

class hethongAction
{
    public static $listRole = 'hethong';

    private $request;
    private $peer;

    public function __construct()
    {
        $this->request = new Request();
        $this->peer = new HeThongPeer();
        $this->request->setTitle('Cấu hình hệ thống');
    }

    public function index()
    {
        if (empty($_SESSION['hethong_csrf'])) {
            $_SESSION['hethong_csrf'] = bin2hex(random_bytes(32));
        }
        $this->request->setAttribute('hethongCsrf', $_SESSION['hethong_csrf']);
        $this->request->setAttribute(
            'script',
            '<script src="' . _DEFAULT_URL_ . 'js/hethong.js?v='
            . filemtime(dirname(__DIR__, 2) . '/js/hethong.js') . '"></script>'
        );
        $this->request->setModel('www/hethong/index.php');
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

    public function updateStatus()
    {
        if (!$this->validCsrf()) {
            return $this->json(array('success' => false, 'message' => 'Phiên làm việc không hợp lệ.'));
        }
        $id = (int) $this->request->getParameter('id', false);
        $status = $this->request->getParameter('trangthai', false);
        if ($id <= 0 || !in_array((string) $status, array('0', '1'), true)) {
            return $this->json(array('success' => false, 'message' => 'Trạng thái không hợp lệ.'));
        }

        try {
            if (!$this->peer->updateStatus($id, (int) $status)) {
                return $this->json(array('success' => false, 'message' => 'Cấu hình không tồn tại.'));
            }
            return $this->json(array(
                'success' => true,
                'trangthai' => (int) $status,
                'message' => (int) $status === 1 ? 'Đã bật cấu hình.' : 'Đã tắt cấu hình.'
            ));
        } catch (RuntimeException $exception) {
            return $this->json(array('success' => false, 'message' => $exception->getMessage()));
        }
    }

    private function validCsrf()
    {
        $token = $this->request->getParameter('csrf_token', false);
        return !empty($_SESSION['hethong_csrf']) && is_string($token)
            && hash_equals($_SESSION['hethong_csrf'], $token);
    }

    private function json($data)
    {
        return $this->request->json_response(
            json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
    }
}
