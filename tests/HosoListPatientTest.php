<?php

// Chạy: C:\xampp\php\php.exe tests/HosoListPatientTest.php (không cần CSDL).
require_once dirname(__DIR__) . '/web_src/bean/FilePeer.php';

function checkHosoListPatient($condition, $message)
{
    if (!$condition) throw new RuntimeException($message);
}

$peer = (new ReflectionClass('FilePeer'))->newInstanceWithoutConstructor();
$summaryMethod = new ReflectionMethod('FilePeer', 'fileSummaryFromValidation');
$summaryMethod->setAccessible(true);

$validation = json_encode(array(
    array('file_type' => 'XML1', 'valid_fields' => array(
        array('field_name' => 'NGAY_SINH', 'value' => '19881231'),
        array('field_name' => 'GIOI_TINH', 'value' => '2')
    )),
    array('file_type' => 'XML2', 'valid_fields' => array(
        array('field_name' => 'NGAY_SINH', 'value' => '20000101'),
        array('field_name' => 'GIOI_TINH', 'value' => '1')
    ))
), JSON_UNESCAPED_UNICODE);

$stored = json_encode(array('maThe' => 'DN123', 'maSoBHXH' => '001234567890',
    'ngaySinh' => '19901231', 'gioiTinh' => '1', 'diaChi' => 'Địa chỉ đã lưu'));
$fromPatient = $summaryMethod->invoke($peer, $validation, $stored);
checkHosoListPatient($fromPatient['ngay_sinh'] === '19901231', 'Ngày sinh đã lưu phải được ưu tiên.');
checkHosoListPatient($fromPatient['gioi_tinh'] === '1', 'Giới tính đã lưu phải được ưu tiên.');
checkHosoListPatient($fromPatient['ma_the_bhyt'] === 'DN123', 'Mất mã thẻ BHYT đã lưu.');
checkHosoListPatient($fromPatient['ma_so_bhxh'] === '001234567890', 'Mất mã số BHXH đã lưu.');
checkHosoListPatient($fromPatient['dia_chi'] === 'Địa chỉ đã lưu', 'Mất địa chỉ đã lưu.');

$insuranceValidation = json_encode(array(
    array('file_type' => 'XML1', 'valid_fields' => array(
        array('field_name' => 'MA_THE_BHYT', 'value' => 'DN456'),
        array('field_name' => 'MA_SO_BHXH', 'value' => '000098765432'),
        array('field_name' => 'DIA_CHI', 'value' => 'Địa chỉ XML1')
    )),
    array('file_type' => 'XML2', 'valid_fields' => array(
        array('field_name' => 'MA_SO_BHXH', 'value' => '999999999999'),
        array('field_name' => 'DIA_CHI', 'value' => 'Địa chỉ XML2')
    ))
));
$fromXml1 = $summaryMethod->invoke($peer, $insuranceValidation, '{}');
checkHosoListPatient($fromXml1['ma_the_bhyt'] === 'DN456', 'Không lấy mã thẻ BHYT từ XML1.');
checkHosoListPatient($fromXml1['ma_so_bhxh'] === '000098765432', 'Không lấy mã số BHXH từ XML1.');
checkHosoListPatient($fromXml1['dia_chi'] === 'Địa chỉ XML1', 'Không lấy địa chỉ từ XML1.');

$fromValidation = $summaryMethod->invoke($peer, $validation, '{}');
checkHosoListPatient($fromValidation['ngay_sinh'] === '19881231', 'Không lấy ngày sinh dự phòng từ XML1.');
checkHosoListPatient($fromValidation['gioi_tinh'] === '2', 'Không lấy giới tính dự phòng từ XML1.');

$onlyOtherXml = json_encode(array(array('file_type' => 'XML2', 'valid_fields' => array(
    array('field_name' => 'NGAY_SINH', 'value' => '20000101'),
    array('field_name' => 'GIOI_TINH', 'value' => '1')
))));
$missing = $summaryMethod->invoke($peer, $onlyOtherXml, '{invalid');
checkHosoListPatient($missing['ngay_sinh'] === '' && $missing['gioi_tinh'] === '',
    'Không được lấy thông tin bệnh nhân từ loại XML khác.');
$otherInsurance = $summaryMethod->invoke($peer, json_encode(array(array(
    'file_type' => 'XML2', 'valid_fields' => array(
        array('field_name' => 'MA_THE_BHYT', 'value' => 'DN-SAI'),
        array('field_name' => 'MA_SO_BHXH', 'value' => '123456789012'),
        array('field_name' => 'DIA_CHI', 'value' => 'Địa chỉ sai')
    )
))), '{}');
checkHosoListPatient($otherInsurance['ma_the_bhyt'] === '' && $otherInsurance['ma_so_bhxh'] === '',
    'Không được lấy mã bảo hiểm từ XML khác XML1.');
checkHosoListPatient($otherInsurance['dia_chi'] === '', 'Không được lấy địa chỉ từ XML khác XML1.');

$badTypes = $summaryMethod->invoke($peer, null, '{"ngaySinh":[],"gioiTinh":{},"maSoBHXH":[],"diaChi":{}}');
checkHosoListPatient($badTypes['ngay_sinh'] === '' && $badTypes['gioi_tinh'] === '',
    'Dữ liệu bệnh nhân không phải scalar phải được bỏ qua.');
checkHosoListPatient($badTypes['ma_so_bhxh'] === '', 'Mã BHXH không phải scalar phải được bỏ qua.');
checkHosoListPatient($badTypes['dia_chi'] === '', 'Địa chỉ không phải scalar phải được bỏ qua.');

echo "PASS HosoListPatientTest (BHYT, BHXH, địa chỉ và thông tin bệnh nhân từ dữ liệu đã lưu và XML1)\n";
