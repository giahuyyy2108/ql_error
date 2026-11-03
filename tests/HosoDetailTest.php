<?php

// Chạy: C:\xampp\php\php.exe tests/HosoDetailTest.php (không cần CSDL).
require_once dirname(__DIR__) . '/web_src/admin/hosoAction.php';

class HosoDetailFakePeer extends FilePeer
{
    public function __construct() {}

    public function getCurrentUserKhoa($username)
    {
        return $username === 'detail_test' ? 'K01' : null;
    }

    public function getByIdForKhoa($id, $khoa)
    {
        if ($khoa !== 'K01' || !in_array($id, array(1, 3, 4, 5), true)) return false;
        if ($id === 3) return array(
            'id' => 3, 'ten' => 'old.xml', 'ma_bn' => 'BN03', 'ho_ten' => 'Bệnh nhân cũ',
            'id_khoa' => 'K01', 'thongtin_bn' => '{invalid', 'file_path' => 'khong-co.xml',
            'validation_result' => 'invalid'
        );
        if ($id === 4) return array(
            'id' => 4, 'ten' => 'fallback.xml', 'ma_bn' => 'BN04', 'ho_ten' => 'Bệnh nhân thiếu thông tin',
            'id_khoa' => 'K01', 'thongtin_bn' => '{}', 'file_path' => 'khong-co.xml',
            'validation_result' => json_encode(array(array('file_type' => 'XML1', 'valid_fields' => array(
                array('field_name' => 'MA_THE_BHYT', 'value' => 'DN-FALLBACK'),
                array('field_name' => 'MA_SO_BHXH', 'value' => '000000012345'),
                array('field_name' => 'DIA_CHI', 'value' => 'Địa chỉ dự phòng'),
                array('field_name' => 'NGAY_SINH', 'value' => '19881231'),
                array('field_name' => 'GIOI_TINH', 'value' => '2')
            ))))
        );
        if ($id === 5) return array(
            'id' => 5, 'ten' => 'legacy-card.xml', 'ma_bn' => 'BN05', 'ho_ten' => 'BN cũ',
            'id_khoa' => 'K01', 'thongtin_bn' => '{"MA_THE_BHYT":"DN-LEGACY"}',
            'file_path' => 'khong-co.xml', 'validation_result' => '[]'
        );
        return array(
            'id' => 1, 'ten' => 'sample.xml', 'ma_bn' => 'BN01', 'ho_ten' => 'Nguyễn Văn A',
            'id_khoa' => 'K01', 'file_path' => 'file-khong-ton-tai.xml',
            'thongtin_bn' => json_encode(array(
                'maBn' => 'BN-khac', 'hoTen' => 'Tên từ BHYT', 'maThe' => 'DN123',
                'ngaySinh' => '19901231', 'gioiTinh' => '1', 'diaChi' => 'Hà Nội',
                'maSoBHXH' => 'khong-tra-ve', 'extra' => array('token' => 'private')
            ), JSON_UNESCAPED_UNICODE),
            'validation_result' => json_encode(array(array(
                'hoso_index' => 0, 'file_index' => 0, 'file_type' => 'XML1',
                'errors' => array(array('field_name' => 'MA_BN', 'message' => 'Thông báo kiểm tra'),
                    array('field_name' => 'NGAY_RA', 'value' => '202601021015', 'message' => 'Sai ngày ra')),
                'valid_fields' => array(array('field_name' => 'NGAY_VAO', 'value' => '202601011000'))
            )), JSON_UNESCAPED_UNICODE)
        );
    }

    public function getKhoaByCode($maKhoa)
    {
        return $maKhoa === 'K01' ? array('ma' => 'K01', 'ten' => 'Khoa Nội') : null;
    }
}

function detailAssert($condition, $message)
{
    if (!$condition) throw new RuntimeException($message);
}

function detailResponse($action)
{
    ob_start();
    $action->getContent();
    $response = json_decode(ob_get_clean(), true);
    detailAssert(is_array($response), 'getContent không trả về JSON.');
    return $response;
}

$action = (new ReflectionClass('hosoAction'))->newInstanceWithoutConstructor();
foreach (array('request' => new Request(), 'filePeer' => new HosoDetailFakePeer()) as $property => $value) {
    $reflection = new ReflectionProperty('hosoAction', $property);
    $reflection->setAccessible(true);
    $reflection->setValue($action, $value);
}

$_SESSION['sUserName'] = 'detail_test';
$_POST = array('id' => 1);
$result = detailResponse($action);
detailAssert($result['success'] === true, 'Không đọc được kết quả kiểm tra hồ sơ thuộc khoa.');
detailAssert($result['ma_bn'] === 'BN01', 'Mã bệnh nhân không đúng.');
detailAssert($result['name'] === 'sample.xml', 'Tên hồ sơ không đúng.');
detailAssert(!array_key_exists('decoded', $result), 'API vẫn trả về nội dung XML.');
detailAssert(count($result['validation'][0]['errors']) === 2, 'Mất kết quả kiểm tra.');
detailAssert($result['validation'][0]['errors'][0]['message'] === 'Thông báo kiểm tra', 'Nội dung lỗi không đúng.');
detailAssert($result['thong_tin_bn']['maBn'] === 'BN01', 'Mã BN phải lấy từ cột hồ sơ.');
detailAssert($result['thong_tin_bn']['hoTen'] === 'Nguyễn Văn A', 'Họ tên phải lấy từ cột hồ sơ.');
detailAssert($result['thong_tin_bn']['maThe'] === 'DN123', 'Thiếu mã thẻ BHYT.');
detailAssert($result['thong_tin_bn']['ngaySinh'] === '19901231', 'Thiếu ngày sinh.');
detailAssert($result['thong_tin_bn']['gioiTinh'] === '1', 'Thiếu giới tính.');
detailAssert($result['thong_tin_bn']['diaChi'] === 'Hà Nội', 'Thiếu địa chỉ.');
detailAssert($result['thong_tin_bn']['ngayVao'] === '202601011000', 'Thiếu ngày vào từ kết quả kiểm tra.');
detailAssert($result['thong_tin_bn']['ngayRa'] === '202601021015', 'Thiếu ngày ra từ kết quả kiểm tra.');
detailAssert($result['khoa_quan_ly']['ten'] === 'Khoa Nội', 'Thiếu khoa quản lý.');
detailAssert($result['thong_tin_bn']['maSoBHXH'] === 'khong-tra-ve', 'Thiếu mã số BHXH đã lưu.');
detailAssert(!isset($result['thong_tin_bn']['extra']), 'API trả dư metadata bệnh nhân.');

$_POST['id'] = 3;
$old = detailResponse($action);
detailAssert($old['success'] === true && $old['thong_tin_bn']['hoTen'] === 'Bệnh nhân cũ', 'Không hiển thị được bệnh nhân thiếu JSON hợp lệ.');
detailAssert($old['validation'] === array(), 'Validation bị hỏng phải trả về mảng rỗng.');

$_POST['id'] = 4;
$fallback = detailResponse($action);
detailAssert($fallback['thong_tin_bn']['maThe'] === 'DN-FALLBACK', 'Không lấy mã thẻ BHYT dự phòng từ XML1.');
detailAssert($fallback['thong_tin_bn']['maSoBHXH'] === '000000012345', 'Không lấy mã BHXH dự phòng từ XML1.');
detailAssert($fallback['thong_tin_bn']['diaChi'] === 'Địa chỉ dự phòng', 'Không lấy địa chỉ dự phòng từ XML1.');
detailAssert($fallback['thong_tin_bn']['ngaySinh'] === '19881231', 'Không lấy ngày sinh dự phòng từ XML1.');
detailAssert($fallback['thong_tin_bn']['gioiTinh'] === '2', 'Không lấy giới tính dự phòng từ XML1.');

$_POST['id'] = 5;
$legacy = detailResponse($action);
detailAssert($legacy['thong_tin_bn']['maThe'] === 'DN-LEGACY', 'Không lấy mã thẻ từ hồ sơ cũ.');

$_POST['id'] = 2;
detailAssert(detailResponse($action)['success'] === false, 'Cho phép xem hồ sơ ngoài phạm vi khoa.');
$_POST['id'] = 1;
$_SESSION['sUserName'] = 'other_user';
detailAssert(detailResponse($action)['success'] === false, 'Cho phép tài khoản không thuộc khoa xem hồ sơ.');

echo "PASS HosoDetailTest (chỉ trả kết quả kiểm tra, không cần file XML, phân quyền khoa)\n";
