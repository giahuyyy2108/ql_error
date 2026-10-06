<?php

require_once 'web_src/bean/TableLookupSourcePeer.php';

class tablelookupAction
{
    public static $listRole = 'tablelookup,save,update,delete,toggleStatus';
    private $request;
    private $peer;

    public function __construct()
    {
        $this->request = new Request();
        $this->peer = new TableLookupSourcePeer();
        $this->request->setTitle('Nguồn đối chiếu TABLE_EXISTS');
    }

    public function index()
    {
        if (empty($_SESSION['table_lookup_csrf'])) $_SESSION['table_lookup_csrf'] = bin2hex(random_bytes(32));
        $this->request->setAttribute('tableLookupCsrf', $_SESSION['table_lookup_csrf']);
        $this->request->setAttribute('databaseSchema', $this->peer->getDatabaseSchema());
        $this->request->setAttribute('script', '<script src="' . _DEFAULT_URL_ . 'js/tablelookup.js?' . _DEFAULT_VERSION_JS_CSS_ . '"></script>');
        $this->request->setModel('www/tablelookup/index.php');
        return true;
    }

    public function getData()
    {
        try { return $this->json(array('data' => $this->peer->getList())); }
        catch (RuntimeException $exception) { return $this->json(array('data' => array(), 'error' => $exception->getMessage())); }
    }

    public function save()
    {
        if (!$this->validCsrf()) return $this->json(array('success'=>false,'message'=>'Phiên làm việc không hợp lệ.'));
        try {
            $id = $this->peer->insert($this->readItem());
            return $this->json(array('success'=>true,'id'=>$id,'message'=>'Thêm nguồn đối chiếu thành công.'));
        } catch (RuntimeException $exception) { return $this->json(array('success'=>false,'message'=>$exception->getMessage())); }
    }

    public function update()
    {
        if (!$this->validCsrf()) return $this->json(array('success'=>false,'message'=>'Phiên làm việc không hợp lệ.'));
        $id = (int) $this->request->getParameter('id', false);
        if ($id <= 0) return $this->json(array('success'=>false,'message'=>'Nguồn đối chiếu không hợp lệ.'));
        try {
            $this->peer->update($id, $this->readItem());
            return $this->json(array('success'=>true,'message'=>'Cập nhật nguồn đối chiếu thành công.'));
        } catch (RuntimeException $exception) { return $this->json(array('success'=>false,'message'=>$exception->getMessage())); }
    }

    public function delete()
    {
        if (!$this->validCsrf()) return $this->json(array('success'=>false,'message'=>'Phiên làm việc không hợp lệ.'));
        $id = (int) $this->request->getParameter('id', false);
        try {
            if ($id <= 0 || !$this->peer->delete($id)) return $this->json(array('success'=>false,'message'=>'Nguồn đối chiếu không tồn tại.'));
            return $this->json(array('success'=>true,'message'=>'Xóa nguồn đối chiếu thành công.'));
        } catch (RuntimeException $exception) { return $this->json(array('success'=>false,'message'=>$exception->getMessage())); }
    }

    public function toggleStatus()
    {
        if (!$this->validCsrf()) return $this->json(array('success'=>false,'message'=>'Phiên làm việc không hợp lệ.'));
        $id = (int) $this->request->getParameter('id', false);
        $isActive = $this->request->getParameter('is_active', false) === '1' ? 1 : 0;
        if ($id <= 0) return $this->json(array('success'=>false,'message'=>'Nguồn đối chiếu không hợp lệ.'));
        try {
            $this->peer->setActive($id, $isActive);
            return $this->json(array(
                'success'=>true,
                'is_active'=>$isActive,
                'message'=>$isActive ? 'Đã bật nguồn đối chiếu.' : 'Đã tắt nguồn đối chiếu.'
            ));
        } catch (RuntimeException $exception) { return $this->json(array('success'=>false,'message'=>$exception->getMessage())); }
    }

    private function readItem()
    {
        $item = array(
            'source_key' => strtolower(trim($this->request->getParameter('source_key', false))),
            'display_name' => trim($this->request->getParameter('display_name', false)),
            'table_name' => trim($this->request->getParameter('table_name', false)),
            'allowed_columns' => $this->readColumns($this->request->getParameter('allowed_columns', false)),
            'condition_columns' => $this->readColumns($this->request->getParameter('condition_columns', false)),
            'is_active' => $this->request->getParameter('is_active', false) === '1' ? 1 : 0
        );
        if (!preg_match('/^[a-z][a-z0-9_]{0,63}$/', $item['source_key'])) throw new RuntimeException('Mã nguồn chỉ gồm chữ thường, số và dấu gạch dưới.');
        if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]{0,63}$/', $item['table_name'])) throw new RuntimeException('Tên bảng không hợp lệ.');
        if ($item['display_name'] === '' || strlen($item['display_name']) > 150) throw new RuntimeException('Vui lòng nhập tên hiển thị hợp lệ.');
        if (!$item['allowed_columns']) throw new RuntimeException('Phải chọn ít nhất một cột đối chiếu.');
        return $item;
    }

    private function readColumns($value)
    {
        if (!is_array($value)) $value = $value === '' || $value === null ? array() : explode(',', $value);
        $columns = array();
        foreach ($value as $column) {
            $column = trim((string) $column);
            if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]{0,63}$/', $column)) throw new RuntimeException('Tên cột không hợp lệ.');
            $columns[$column] = true;
        }
        return array_keys($columns);
    }

    private function validCsrf()
    {
        $token = $this->request->getParameter('csrf_token', false);
        return !empty($_SESSION['table_lookup_csrf']) && is_string($token) && hash_equals($_SESSION['table_lookup_csrf'], $token);
    }

    private function json($data)
    {
        return $this->request->json_response(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}
