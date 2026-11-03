<?php

// Chạy: C:\xampp\php\php.exe tests/HosoExportActionTest.php (không cần CSDL).
require_once dirname(__DIR__) . '/web_src/admin/hosoAction.php';

class ExportRequestStub
{
    public $params;
    public function __construct(array $params) { $this->params = $params; }
    public function getParameter($name, $unused = false) { return isset($this->params[$name]) ? $this->params[$name] : null; }
    public function json_response($json) { return $json; }
}

class ExportPeerStub
{
    public $calls = array();
    public function getCurrentUserKhoa($username) { return $username === 'nhanvien' ? 'K01' : null; }
    public function getHosoExportErrorsByKhoa($date, $khoa, $period)
    {
        $this->calls[] = array('errors', $date, $khoa, $period);
        return array(array('error' => 'Sai mã', 'total' => 2));
    }
    public function getListByKhoa($date, $khoa, $search, $period)
    {
        $this->calls[] = array('files', $date, $khoa, $search, $period);
        return array(array('ma_bn' => '00012'));
    }
}

function checkExportAction($condition, $message)
{
    if (!$condition) throw new RuntimeException($message);
}

$_SESSION['sUserName'] = 'nhanvien';
$_SESSION['hoso_csrf'] = 'test-csrf-token';
$peer = new ExportPeerStub();
$request = new ExportRequestStub(array(
    'date' => '2026-11', 'period' => 'month', 'csrf_token' => 'test-csrf-token',
    'id_khoa' => 'KHOA_KHAC', 'search' => 'khong-ap-dung'
));
$action = (new ReflectionClass('hosoAction'))->newInstanceWithoutConstructor();
foreach (array('request' => $request, 'filePeer' => $peer) as $name => $value) {
    $property = new ReflectionProperty('hosoAction', $name);
    $property->setAccessible(true);
    $property->setValue($action, $value);
}

$result = json_decode($action->getExportData(), true);
checkExportAction($result['success'] === true && count($result['data']) === 1
    && count($result['errors']) === 1, 'Tháng phải trả về lỗi và hồ sơ để tính tổng theo ngày.');
checkExportAction($peer->calls === array(
    array('errors', '2026-11', 'K01', 'month'),
    array('files', '2026-11', 'K01', '', 'month')
), 'Xuất tháng phải lấy dữ liệu đúng khoa và tháng, bỏ qua từ khóa từ client.');

$request->params['period'] = 'day';
$request->params['date'] = '2026-11-02';
$result = json_decode($action->getExportData(), true);
checkExportAction(count($result['data']) === 1 && count($result['errors']) === 1,
    'Xuất ngày phải gồm hồ sơ và lỗi chi tiết.');
checkExportAction($peer->calls[2] === array('errors', '2026-11-02', 'K01', 'day')
    && $peer->calls[3] === array('files', '2026-11-02', 'K01', '', 'day'),
    'Xuất ngày phải truy vấn đúng khoa và ngày, bỏ qua tìm kiếm.');

$request->params['period'] = 'year';
$result = json_decode($action->getExportData(), true);
checkExportAction($result['success'] === false && count($peer->calls) === 4,
    'Phải từ chối period không hỗ trợ.');

$request->params['csrf_token'] = 'sai-token';
$result = json_decode($action->getExportData(), true);
checkExportAction($result['success'] === false && count($peer->calls) === 4,
    'Thiếu CSRF nhưng vẫn truy vấn xuất Excel.');

echo "PASS HosoExportActionTest (theo ngày/tháng, đúng khoa, CSRF)\n";
