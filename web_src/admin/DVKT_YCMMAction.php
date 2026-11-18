<?php

require_once 'web_src/bean/DvktYcmmPeer.php';

class DVKT_YCMMAction
{
    public static $listRole = 'DVKT_YCMM,save,update,delete,toggleStatus';
    private $request;
    private $peer;

    public function __construct()
    {
        $this->request = new Request();
        $this->peer = new DvktYcmmPeer();
        $this->request->setTitle('Danh mục DVKT yêu cầu có mã máy');
    }

    public function index()
    {
        if (empty($_SESSION['dvkt_ycmm_csrf'])) $_SESSION['dvkt_ycmm_csrf'] = bin2hex(random_bytes(32));
        $this->request->setAttribute('dvktYcmmCsrf', $_SESSION['dvkt_ycmm_csrf']);
        $this->request->setAttribute('script', '<script src="' . _DEFAULT_URL_ . 'js/dvkt_ycmm.js?'
            . _DEFAULT_VERSION_JS_CSS_ . '&amp;v=' . filemtime(__DIR__ . '/../../js/dvkt_ycmm.js') . '"></script>');
        $this->request->setModel('www/DVKT_YCMM/index.php');
        return true;
    }

    public function getData()
    {
        try { return $this->json(array('data' => $this->peer->getList())); }
        catch (Throwable $exception) { return $this->json(array('data' => array(), 'error' => $exception->getMessage())); }
    }

    public function getOne()
    {
        if (!$this->validCsrf()) return $this->json(array('success'=>false,'message'=>'Phiên làm việc không hợp lệ.'));
        $row = $this->peer->getOne($this->id());
        return $this->json($row ? array('success'=>true,'row'=>$row)
            : array('success'=>false,'message'=>'Bản ghi không tồn tại.'));
    }

    public function save()
    {
        return $this->write('save', function () {
            $id = $this->peer->insert($this->readItem());
            return array('success'=>true,'id'=>$id,'message'=>'Thêm DVKT yêu cầu mã máy thành công.');
        });
    }

    public function update()
    {
        return $this->write('update', function () {
            $this->peer->update($this->id(), $this->readItem());
            return array('success'=>true,'message'=>'Cập nhật DVKT yêu cầu mã máy thành công.');
        });
    }

    public function delete()
    {
        return $this->write('delete', function () {
            if (!$this->peer->delete($this->id())) return array('success'=>false,'message'=>'Bản ghi không tồn tại.');
            return array('success'=>true,'message'=>'Xóa DVKT yêu cầu mã máy thành công.');
        });
    }

    public function toggleStatus()
    {
        return $this->write('toggleStatus', function () {
            $active = $this->request->getParameter('hieu_luc', false) === '1' ? 1 : 0;
            $this->peer->setActive($this->id(), $active);
            return array('success'=>true,'hieu_luc'=>$active,
                'message'=>$active ? 'Đã bật hiệu lực.' : 'Đã tắt hiệu lực.');
        });
    }

    private function readItem()
    {
        $stt = trim((string) $this->request->getParameter('stt', false));
        $code = trim((string) $this->request->getParameter('ma_dvkt', false));
        $name = trim((string) $this->request->getParameter('ten_dvkt', false));
        $from = trim((string) $this->request->getParameter('ngay_hieu_luc', false));
        $to = trim((string) $this->request->getParameter('ngay_het_hieu_luc', false));
        if (!preg_match('/^[1-9]\d*$/D', $stt)) throw new InvalidArgumentException('STT phải là số nguyên dương.');
        if ($code === '' || strlen($code) > 50) throw new InvalidArgumentException('Mã DVKT không hợp lệ.');
        if ($name === '') throw new InvalidArgumentException('Vui lòng nhập tên DVKT.');
        $from = $this->dateOrNull($from, 'Ngày hiệu lực');
        $to = $this->dateOrNull($to, 'Ngày hết hiệu lực');
        if ($from !== null && $to !== null && $from > $to) {
            throw new InvalidArgumentException('Ngày hết hiệu lực không được trước ngày hiệu lực.');
        }
        return array('stt'=>(int)$stt, 'ma_dvkt'=>$code, 'ten_dvkt'=>$name,
            'hieu_luc'=>$this->request->getParameter('hieu_luc', false)==='1' ? 1 : 0,
            'ngay_hieu_luc'=>$from, 'ngay_het_hieu_luc'=>$to,
            'tra_ket_qua_sau'=>$this->request->getParameter('tra_ket_qua_sau', false)==='1' ? 1 : 0);
    }

    private function dateOrNull($value, $label)
    {
        if ($value === '') return null;
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        if (!$date || $date->format('Y-m-d') !== $value) throw new InvalidArgumentException($label . ' không hợp lệ.');
        return $value;
    }

    private function id()
    {
        $id = (int) $this->request->getParameter('id', false);
        if ($id <= 0) throw new InvalidArgumentException('Bản ghi không hợp lệ.');
        return $id;
    }

    private function write($operation, $callback)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return $this->json(array('success'=>false,'message'=>'Yêu cầu phải dùng POST.'));
        if (!$this->validCsrf()) return $this->json(array('success'=>false,'message'=>'Phiên làm việc không hợp lệ.'));
        try { return $this->json($callback()); }
        catch (InvalidArgumentException $exception) { return $this->json(array('success'=>false,'message'=>$exception->getMessage())); }
        catch (Throwable $exception) { return $this->json(array('success'=>false,'message'=>'Không thể xử lý danh mục.')); }
    }

    private function validCsrf()
    {
        $token = $this->request->getParameter('csrf_token', false);
        return !empty($_SESSION['dvkt_ycmm_csrf']) && is_string($token)
            && hash_equals($_SESSION['dvkt_ycmm_csrf'], $token);
    }

    private function json($data)
    {
        return $this->request->json_response(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}
