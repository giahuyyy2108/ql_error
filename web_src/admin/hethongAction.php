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

    public function getPath()
    {
        try {
            $item = $this->peer->getInputPath();
            return $this->json(array(
                'success' => true,
                'data' => $item
            ));
        } catch (RuntimeException $exception) {
            return $this->json(array(
                'success' => false,
                'data' => null,
                'message' => $exception->getMessage()
            ));
        }
    }

    public function updatePath()
    {
        if (!$this->validCsrf()) {
            return $this->json(array('success' => false, 'message' => 'Phiên làm việc không hợp lệ.'));
        }

        $path = trim((string) $this->request->getParameter('path', false));
        if ($path === '') {
            return $this->json(array('success' => false, 'message' => 'Đường dẫn đầu vào XML không được để trống.'));
        }
        if (strlen($path) > 255) {
            return $this->json(array('success' => false, 'message' => 'Đường dẫn đầu vào XML tối đa 255 ký tự.'));
        }

        try {
            if (!$this->peer->updateInputPath($path)) {
                return $this->json(array('success' => false, 'message' => 'Không tìm thấy cấu hình Đầu vào trong bảng path.'));
            }
            return $this->json(array(
                'success' => true,
                'path' => $path,
                'message' => 'Đã lưu đường dẫn đầu vào XML.'
            ));
        } catch (RuntimeException $exception) {
            return $this->json(array('success' => false, 'message' => $exception->getMessage()));
        }
    }

    public function getPortalPath()
    {
        try {
            return $this->json(array(
                'success' => true,
                'data' => $this->peer->getPortalPath()
            ));
        } catch (RuntimeException $exception) {
            return $this->json(array(
                'success' => false,
                'data' => null,
                'message' => $exception->getMessage()
            ));
        }
    }

    public function updatePortalPath()
    {
        if (!$this->validCsrf()) {
            return $this->json(array('success' => false, 'message' => 'Phiên làm việc không hợp lệ.'));
        }

        $path = trim((string) $this->request->getParameter('path', false));
        if ($path === '') {
            return $this->json(array('success' => false, 'message' => 'Đường dẫn Đẩy cổng không được để trống.'));
        }
        if (strlen($path) > 255 || strpos($path, "\0") !== false) {
            return $this->json(array('success' => false, 'message' => 'Đường dẫn Đẩy cổng không hợp lệ hoặc vượt quá 255 ký tự.'));
        }

        try {
            $this->peer->updatePortalPath($path);
            return $this->json(array(
                'success' => true,
                'path' => $path,
                'message' => 'Đã lưu đường dẫn Đẩy cổng.'
            ));
        } catch (RuntimeException $exception) {
            return $this->json(array('success' => false, 'message' => $exception->getMessage()));
        }
    }

    public function getKhoaOutputPaths()
    {
        try {
            return $this->json(array(
                'success' => true,
                'data' => $this->peer->getKhoaOutputPaths()
            ));
        } catch (RuntimeException $exception) {
            return $this->json(array(
                'success' => false,
                'data' => array(),
                'message' => $exception->getMessage()
            ));
        }
    }

    public function updateKhoaOutputPath()
    {
        if (!$this->validCsrf()) {
            return $this->json(array('success' => false, 'message' => 'Phiên làm việc không hợp lệ.'));
        }

        $maKhoa = trim((string) $this->request->getParameter('ma_khoa', false));
        $outputPath = trim((string) $this->request->getParameter('output_path', false));
        if ($maKhoa === '') {
            return $this->json(array('success' => false, 'message' => 'Mã khoa không hợp lệ.'));
        }
        if (strlen($outputPath) > 500 || strpos($outputPath, "\0") !== false) {
            return $this->json(array('success' => false, 'message' => 'Đường dẫn đầu ra không hợp lệ.'));
        }

        try {
            if (!$this->peer->updateKhoaOutputPath($maKhoa, $outputPath)) {
                return $this->json(array('success' => false, 'message' => 'Không tìm thấy khoa gốc.'));
            }
            return $this->json(array(
                'success' => true,
                'output_path' => $outputPath,
                'message' => $outputPath === ''
                    ? 'Đã dùng lại đường dẫn đầu ra chung.'
                    : 'Đã lưu đường dẫn đầu ra của khoa.'
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
