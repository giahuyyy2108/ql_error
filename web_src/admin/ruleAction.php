<?php

require_once 'web_src/bean/RulePeer.php';

class ruleAction
{
    public static $listRole = 'rule,save,update,delete';

    private $request;
    private $rulePeer;

    public function __construct()
    {
        $this->request = new Request();
        $this->rulePeer = new RulePeer();
        $this->request->setTitle('Danh mục loại rule');
    }

    public function index()
    {
        if (empty($_SESSION['rule_catalog_csrf'])) {
            $_SESSION['rule_catalog_csrf'] = bin2hex(random_bytes(32));
        }
        $this->request->setAttribute('ruleCatalogCsrf', $_SESSION['rule_catalog_csrf']);
        $this->request->setAttribute(
            'script',
            '<script src="' . _DEFAULT_URL_ . 'js/rule.js?' . _DEFAULT_VERSION_JS_CSS_ . '"></script>'
        );
        $this->request->setModel('www/rule/index.php');
        return true;
    }

    public function getData()
    {
        try {
            return $this->json(array('data' => $this->rulePeer->getList()));
        } catch (RuntimeException $exception) {
            return $this->json(array('data' => array(), 'error' => $exception->getMessage()));
        }
    }

    public function save()
    {
        if (!$this->validCsrf()) {
            return $this->json(array('success' => false, 'message' => 'Phiên làm việc không hợp lệ.'));
        }
        try {
            $id = $this->rulePeer->insert($this->readItem());
            return $this->json(array('success' => true, 'id' => $id, 'message' => 'Thêm loại rule thành công.'));
        } catch (RuntimeException $exception) {
            return $this->json(array('success' => false, 'message' => $exception->getMessage()));
        }
    }

    public function update()
    {
        if (!$this->validCsrf()) {
            return $this->json(array('success' => false, 'message' => 'Phiên làm việc không hợp lệ.'));
        }
        $id = (int) $this->request->getParameter('id', false);
        if ($id <= 0) {
            return $this->json(array('success' => false, 'message' => 'Mã loại rule không hợp lệ.'));
        }
        try {
            $this->rulePeer->update($id, $this->readItem());
            return $this->json(array('success' => true, 'message' => 'Cập nhật loại rule thành công.'));
        } catch (RuntimeException $exception) {
            return $this->json(array('success' => false, 'message' => $exception->getMessage()));
        }
    }

    public function delete()
    {
        if (!$this->validCsrf()) {
            return $this->json(array('success' => false, 'message' => 'Phiên làm việc không hợp lệ.'));
        }
        $id = (int) $this->request->getParameter('id', false);
        if ($id <= 0) {
            return $this->json(array('success' => false, 'message' => 'Mã loại rule không hợp lệ.'));
        }
        try {
            if (!$this->rulePeer->delete($id)) {
                return $this->json(array('success' => false, 'message' => 'Loại rule không tồn tại.'));
            }
            return $this->json(array('success' => true, 'message' => 'Xóa loại rule thành công.'));
        } catch (RuntimeException $exception) {
            return $this->json(array('success' => false, 'message' => $exception->getMessage()));
        }
    }

    private function readItem()
    {
        $item = array(
            'code' => strtoupper(trim($this->request->getParameter('code', false))),
            'display_name' => trim($this->request->getParameter('display_name', false)),
            'description' => trim($this->request->getParameter('description', false)),
            'value_hint' => trim($this->request->getParameter('value_hint', false)),
            'requires_value' => $this->request->getParameter('requires_value', false) === '1' ? 1 : 0,
            'is_active' => $this->request->getParameter('is_active', false) === '1' ? 1 : 0
        );

        if ($item['code'] === '' || $item['display_name'] === '' || $item['description'] === '') {
            throw new RuntimeException('Vui lòng nhập đầy đủ mã, tên và mô tả rule.');
        }
        if (!preg_match('/^[A-Z][A-Z0-9_]*$/', $item['code'])) {
            throw new RuntimeException('Mã rule chỉ gồm chữ in hoa, số và dấu gạch dưới.');
        }
        if (strlen($item['code']) > 50 || strlen($item['display_name']) > 100
            || strlen($item['description']) > 500 || strlen($item['value_hint']) > 255) {
            throw new RuntimeException('Dữ liệu vượt quá độ dài cho phép.');
        }
        return $item;
    }

    private function validCsrf()
    {
        $token = $this->request->getParameter('csrf_token', false);
        return !empty($_SESSION['rule_catalog_csrf']) && is_string($token)
            && hash_equals($_SESSION['rule_catalog_csrf'], $token);
    }

    private function json($data)
    {
        return $this->request->json_response(
            json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
    }
}

