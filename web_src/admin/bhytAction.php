<?php

require_once 'web_src/bean/BhytPeer.php';

class bhytAction
{
    public static $listRole = 'bhyt,save,delete';

    private $request;
    private $peer;

    public function __construct()
    {
        $this->request = new Request();
        $this->peer = new BhytPeer();
        $this->request->setTitle('Danh mục nhóm BHYT');
    }

    public function index()
    {
        if (empty($_SESSION['bhyt_csrf'])) {
            $_SESSION['bhyt_csrf'] = bin2hex(random_bytes(32));
        }
        $this->request->setAttribute('bhytCsrf', $_SESSION['bhyt_csrf']);
        $this->request->setAttribute(
            'script',
            '<script src="' . _DEFAULT_URL_ . 'js/bhyt.js?' . _DEFAULT_VERSION_JS_CSS_ . '"></script>'
        );
        $this->request->setModel('www/bhyt/index.php');
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
        if (!$this->validCsrf()) {
            return $this->json(array('success' => false, 'message' => 'Phiên làm việc không hợp lệ.'));
        }
        $originalId = strtoupper(trim($this->request->getParameter('original_id', false)));
        try {
            $item = $this->readItem();
            if ($originalId === '') $this->peer->insert($item);
            else $this->peer->update($originalId, $item);
            return $this->json(array(
                'success' => true,
                'message' => $originalId === '' ? 'Thêm nhóm BHYT thành công.' : 'Cập nhật nhóm BHYT thành công.'
            ));
        } catch (RuntimeException $exception) {
            return $this->json(array('success' => false, 'message' => $exception->getMessage()));
        }
    }

    public function delete()
    {
        if (!$this->validCsrf()) {
            return $this->json(array('success' => false, 'message' => 'Phiên làm việc không hợp lệ.'));
        }
        $id = strtoupper(trim($this->request->getParameter('id', false)));
        if ($id === '') return $this->json(array('success' => false, 'message' => 'Mã nhóm BHYT không hợp lệ.'));
        try {
            if (!$this->peer->delete($id)) {
                return $this->json(array('success' => false, 'message' => 'Nhóm BHYT không tồn tại.'));
            }
            return $this->json(array('success' => true, 'message' => 'Xóa nhóm BHYT thành công.'));
        } catch (RuntimeException $exception) {
            return $this->json(array('success' => false, 'message' => $exception->getMessage()));
        }
    }

    private function readItem()
    {
        $item = array(
            'id' => strtoupper(trim($this->request->getParameter('id', false))),
            'ten' => trim($this->request->getParameter('ten', false)),
            'mota' => trim($this->request->getParameter('mota', false)),
            'dien' => (int) $this->request->getParameter('dien', false)
        );
        if (!preg_match('/^[A-Z0-9]{2,10}$/', $item['id'])) {
            throw new RuntimeException('Mã nhóm phải gồm 2–10 chữ in hoa hoặc chữ số.');
        }
        if ($item['ten'] === '' || $item['mota'] === '') {
            throw new RuntimeException('Vui lòng nhập tên và mô tả nhóm BHYT.');
        }
        if (strlen($item['ten']) > 255 || !in_array($item['dien'], array(1, 2, 3, 4, 5), true)) {
            throw new RuntimeException('Tên hoặc diện BHYT không hợp lệ.');
        }
        return $item;
    }

    private function validCsrf()
    {
        $token = $this->request->getParameter('csrf_token', false);
        return !empty($_SESSION['bhyt_csrf']) && is_string($token)
            && hash_equals($_SESSION['bhyt_csrf'], $token);
    }

    private function json($data)
    {
        return $this->request->json_response(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}

