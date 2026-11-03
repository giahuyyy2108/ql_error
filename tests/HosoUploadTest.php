<?php

// Chạy: C:\xampp\php\php.exe tests/HosoUploadTest.php (không cần kết nối DB).
require_once dirname(__DIR__) . '/web_src/admin/hosoAction.php';

class HosoUploadFakePeer extends FilePeer
{
    public $progressCalls = array();

    public function __construct() {}

    public function getCurrentUserKhoa($username)
    {
        return $username === 'hoso_test' ? 'K01' : null;
    }

    public function countQueuedUploadFilesForKhoa(array $names, $khoa)
    {
        $this->progressCalls[] = array($names, $khoa);
        return 1;
    }
}

function assertHoso($condition, $message)
{
    if (!$condition) throw new RuntimeException($message);
}

function hosoResult($action, $method)
{
    ob_start();
    $action->$method();
    $json = ob_get_clean();
    $response = json_decode($json, true);
    assertHoso(is_array($response), 'Phản hồi không phải JSON: ' . $json);
    return $response;
}

$action = (new ReflectionClass('hosoAction'))->newInstanceWithoutConstructor();
$peer = new HosoUploadFakePeer();
foreach (array('request' => new Request(), 'filePeer' => $peer) as $property => $value) {
    $reflection = new ReflectionProperty('hosoAction', $property);
    $reflection->setAccessible(true);
    $reflection->setValue($action, $value);
}

$_SESSION['sUserName'] = 'hoso_test';
$_SESSION['hoso_csrf'] = 'correct-token';
$_FILES = array();
$_POST = array('csrf_token' => 'correct-token', 'files' => '["a.xml"]', 'id_khoa' => 'OTHER');
$response = hosoResult($action, 'uploadProgress');
assertHoso($response['success'] && $response['remaining'] === 1, 'Tiến độ không hợp lệ.');
assertHoso($peer->progressCalls[0] === array(array('a.xml'), 'K01'), 'Đã sử dụng khoa do client gửi.');

$_POST['csrf_token'] = 'invalid';
assertHoso(hosoResult($action, 'uploadProgress')['success'] === false, 'Không chặn CSRF.');
assertHoso(hosoResult($action, 'upload')['success'] === false, 'Upload không kiểm tra CSRF.');

$_POST['csrf_token'] = 'correct-token';
$_POST['files'] = '{"not":"a list"}';
assertHoso(hosoResult($action, 'uploadProgress')['success'] === false, 'Không chặn danh sách lỗi.');
assertHoso(hosoResult($action, 'upload')['success'] === false, 'Chấp nhận upload thiếu file.');

$_FILES['xmlFile'] = array('error' => UPLOAD_ERR_OK, 'size' => 5242881, 'name' => 'a.xml', 'tmp_name' => 'fake');
assertHoso(hosoResult($action, 'upload')['success'] === false, 'Không chặn file quá 5 MB.');
$_FILES['xmlFile'] = array('error' => UPLOAD_ERR_OK, 'size' => 1, 'name' => 'a.txt', 'tmp_name' => 'fake');
assertHoso(hosoResult($action, 'upload')['success'] === false, 'Không kiểm tra đuôi XML.');
$_FILES['xmlFile'] = array('error' => UPLOAD_ERR_OK, 'size' => 1, 'name' => 'a.xml', 'tmp_name' => 'fake');
assertHoso(hosoResult($action, 'upload')['success'] === false, 'Chấp nhận file không được PHP upload.');

$xml1 = '<XML1><MA_KHOA>K01</MA_KHOA></XML1>';
$sample = '<DANHSACHHOSO><HOSO><FILEHOSO><LOAIHOSO>XML1</LOAIHOSO><NOIDUNGFILE>'
    . base64_encode($xml1) . '</NOIDUNGFILE></FILEHOSO></HOSO></DANHSACHHOSO>';
$samplePath = tempnam(sys_get_temp_dir(), 'hoso_');
assertHoso($samplePath !== false, 'Không tạo được file XML kiểm thử.');
try {
    file_put_contents($samplePath, $sample);
    $decoded = XmlFileDecoder::decodeDanhSachHoSoPathWithMetadata($samplePath);
    $xml1Values = XmlFileDecoder::extractValuesFromFileType($decoded['content'], 'XML1', array('MA_KHOA'));
    assertHoso($xml1Values['MA_KHOA'] === 'K01', 'Không đọc đúng MA_KHOA từ XML1.');
} finally {
    @unlink($samplePath);
}

echo "PASS HosoUploadTest (CSRF, phân quyền khoa, tệp và kích thước)\n";
