<?php

// Chạy: C:\xampp\php\php.exe tests/HosoExportErrorsTest.php (không cần CSDL).
require_once dirname(__DIR__) . '/web_src/bean/FilePeer.php';

function assertExportErrors($condition, $message)
{
    if (!$condition) throw new RuntimeException($message);
}

$validation = json_encode(array(
    array('file_type' => 'XML1', 'errors' => array(
        array('field_name' => 'MA_THE', 'display_name' => 'Mã thẻ', 'message' => 'Sai mã',
            'value' => '000234', 'huongxuly' => 'Sửa mã thẻ', 'rule_id' => 20),
        array('field_name' => 'MA_THE', 'display_name' => 'Mã thẻ', 'message' => 'Sai mã',
            'value' => '=FORMULA', 'rule_id' => 20),
        array('field_name' => 'MA_THE', 'message' => 'Nhắc nhập mã', 'severity' => 'warning')
    )),
    array('file_type' => 'XML2', 'errors' => array(
        array('field_name' => 'MA_THE', 'display_name' => 'Mã thẻ', 'message' => 'Sai mã', 'rule_id' => 20)
    ))
), JSON_UNESCAPED_UNICODE);

$files = array(
    array('id' => 12, 'ma_lk' => '0001', 'ma_bn' => '0012', 'update_at' => '2026-11-01 11:20:00',
        'validation_result' => $validation),
    array('id' => 13, 'ma_lk' => '0002', 'ma_bn' => '0013', 'update_at' => '2026-11-02 20:00:00',
        'validation_result' => $validation),
    array('id' => 14, 'ma_lk' => '0003', 'ma_bn' => '0014', 'update_at' => '2026-11-02 20:00:00',
        'validation_result' => '{bad-json')
);

$day = FilePeer::buildHosoExportErrors($files, 'day');
assertExportErrors(count($day) === 6, 'Ngày phải có 6 dòng lỗi, không tính cảnh báo và JSON hỏng.');
assertExportErrors($day[0]['ma_lk'] === '0001' && $day[0]['value'] === '000234'
    && $day[0]['huongxuly'] === 'Sửa mã thẻ', 'Sai chi tiết lỗi xuất ngày.');
assertExportErrors($day[1]['value'] === '=FORMULA' && $day[0]['update_date'] === '2026-11-01',
    'Mất giá trị gốc hoặc ngày cập nhật.');

$month = FilePeer::buildHosoExportErrors($files, 'month');
assertExportErrors(count($month) === 2, 'Cần tách cùng nội dung lỗi nhưng khác loại XML.');
assertExportErrors($month[0]['total'] === 2 && $month[0]['file_count'] === 2,
    'Mỗi lỗi chỉ được tính một lần trên mỗi hồ sơ.');
assertExportErrors($month[0]['days'] === array('2026-11-01' => 1, '2026-11-02' => 1),
    'Số hồ sơ lỗi theo ngày không được tính trùng lỗi trong cùng hồ sơ.');
assertExportErrors($month[1]['xml'] === 'XML2' && $month[1]['total'] === 2,
    'Mất dữ liệu lỗi XML2.');
try {
    FilePeer::buildHosoExportErrors($files, 'year');
    throw new RuntimeException('Không từ chối period sai.');
} catch (RuntimeException $e) {
    assertExportErrors($e->getMessage() === 'Hình thức xuất Excel không hợp lệ.', 'Sai lỗi period.');
}

echo "PASS HosoExportErrorsTest (chi tiết ngày, tần suất theo ngày của tháng, không tính warning)\n";
