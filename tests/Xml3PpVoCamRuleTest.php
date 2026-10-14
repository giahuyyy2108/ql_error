<?php

require_once dirname(__DIR__) . '/web_src/common/XmlRulesValidator.php';

$decoded = array('DANHSACHHOSO' => array('HOSO' => array(array('FILEHOSO' => array(array(
    'LOAIHOSO' => 'XML3',
    'NOIDUNGFILE' => array('DSACH_CHI_TIET_DVKT' => array('CHI_TIET_DVKT' => array(
        array('MA_DICH_VU' => 'DV01', 'TEN_DICH_VU' => 'Dịch vụ thiếu', 'MA_NHOM' => '8', 'PP_VO_CAM' => ''),
        array('MA_DICH_VU' => 'DV02', 'TEN_DICH_VU' => 'Dịch vụ đủ', 'MA_NHOM' => '8', 'PP_VO_CAM' => 'Gây mê'),
        array('MA_DICH_VU' => 'DV03', 'TEN_DICH_VU' => 'Dịch vụ nhóm khác', 'MA_NHOM' => '7', 'PP_VO_CAM' => '')
    )))
))))));

$rules = array(array(
    'id' => 10001,
    'file_type' => 'XML3',
    'field_name' => 'MA_NHOM',
    'display_name' => 'Phương pháp vô cảm của dịch vụ nhóm 8',
    'rule_type' => 'CONDITIONAL_FIELD',
    'rule_value' => '{"when_value":"8","when_operator":"=","other_field":"PP_VO_CAM","operator":"!=","expected":"","data_type":"string","other_data_type":"string"}',
    'error_message' => 'Thiếu phương pháp vô cảm ở dịch vụ {TEN_DICH_VU}',
    'is_active' => 1
));

$result = XmlRulesValidator::validate($decoded, $rules);
if ($result[0]['error_count'] !== 1) {
    throw new RuntimeException('Phải chỉ báo lỗi dòng MA_NHOM = 8 bị thiếu PP_VO_CAM.');
}
if ($result[0]['errors'][0]['message'] !== 'Thiếu phương pháp vô cảm ở dịch vụ Dịch vụ thiếu') {
    throw new RuntimeException('Thông báo lỗi không nội suy đúng TEN_DICH_VU.');
}
if ($result[0]['errors'][0]['path'] !== 'DSACH_CHI_TIET_DVKT.CHI_TIET_DVKT[0].MA_NHOM') {
    throw new RuntimeException('Lỗi không được gắn đúng dòng dịch vụ bị thiếu PP_VO_CAM.');
}

echo "Xml3PpVoCamRuleTest: OK\n";
