<?php

require_once dirname(__DIR__) . '/web_src/common/XmlRulesValidator.php';

$decoded = array('DANHSACHHOSO' => array('HOSO' => array(array('FILEHOSO' => array(array(
    'LOAIHOSO' => 'XML3',
    'NOIDUNGFILE' => array('ROOT' => array('ROWS' => array('ROW' => array(array(
        'STT' => '9080',
        'MA_VAT_TU' => 'VT01',
        'TEN_VAT_TU' => 'Vật tư thử',
        'DON_GIA_BV' => '100'
    )))))
))))));
$rules = array(array(
    'id' => 9999,
    'file_type' => 'XML3',
    'field_name' => 'DON_GIA_BV',
    'display_name' => 'Đơn giá',
    'rule_type' => 'REGEX',
    'rule_value' => '/^200$/',
    'error_message' => 'Dòng {STT}: {TEN_VAT_TU} ({MA_VAT_TU}), giá trị {value}',
    'is_active' => 1
));

$result = XmlRulesValidator::validate($decoded, $rules);
$actual = $result[0]['errors'][0]['message'];
$expected = 'Dòng 9080: Vật tư thử (VT01), giá trị 100';
if ($actual !== $expected) {
    throw new RuntimeException('Thay parameter sai: ' . $actual);
}

echo "Rule message parameter test passed.\n";
