<?php

require_once dirname(__DIR__) . '/web_src/common/XmlRulesValidator.php';

function validateCrossFileAnyMatch($diagnoses, $drugCode)
{
    $decoded = array(
        'DANHSACHHOSO' => array(
            'HOSO' => array(array(
                'FILEHOSO' => array(
                    array('LOAIHOSO' => 'XML1', 'NOIDUNGFILE' => $diagnoses),
                    array('LOAIHOSO' => 'XML2', 'NOIDUNGFILE' => array(
                        'DSACH_CHI_TIET_THUOC' => array(
                            'CHI_TIET_THUOC' => array(array('MA_THUOC' => $drugCode))
                        )
                    ))
                )
            ))
        )
    );
    $rules = array(array(
        'id' => 106,
        'file_type' => 'XML2',
        'field_name' => 'MA_THUOC',
        'display_name' => 'Mã thuốc chống hủy xương',
        'rule_type' => 'CROSS_FILE_ANY_MATCH',
        'rule_value' => json_encode(array(
            'when_values' => array('40.65', '40.66', '40.68', '40.74', '40.76'),
            'target_file_type' => 'XML1',
            'target_fields' => array('MA_BENH_CHINH', 'MA_BENH_KT'),
            'match_type' => 'PREFIX',
            'expected_values' => array('M80', 'M81', 'M82', 'M85'),
            'overrides' => array(array(
                'when_values' => array('40.76'),
                'expected_values' => array('M80', 'M81', 'M82', 'M85', 'C79.5')
            ))
        )),
        'error_message' => 'Thuốc không khớp chỉ định {MA_THUOC}.',
        'is_active' => 1
    ));
    $results = XmlRulesValidator::validate($decoded, $rules);
    return $results[1];
}

function assertCrossFileAnyMatch($expected, $actual, $message)
{
    if ($expected !== $actual) {
        throw new RuntimeException($message . ': expected ' . var_export($expected, true)
            . ', got ' . var_export($actual, true));
    }
}

$result = validateCrossFileAnyMatch(array('MA_BENH_CHINH' => 'M81.0'), '40.65');
assertCrossFileAnyMatch(0, $result['error_count'], 'M81 phải hợp lệ cho thuốc chống hủy xương');

$result = validateCrossFileAnyMatch(array('MA_BENH_CHINH' => 'A09.9', 'MA_BENH_KT' => 'M85.8'), '40.68');
assertCrossFileAnyMatch(0, $result['error_count'], 'Mã bệnh kèm theo M85 phải hợp lệ');

$result = validateCrossFileAnyMatch(array('MA_BENH_CHINH' => 'C79.5'), '40.76');
assertCrossFileAnyMatch(0, $result['error_count'], 'C79.5 chỉ hợp lệ cho Zoledronic acid');

$result = validateCrossFileAnyMatch(array('MA_BENH_CHINH' => 'C79.5'), '40.65');
assertCrossFileAnyMatch(1, $result['error_count'], 'C79.5 không hợp lệ cho thuốc khác');

$result = validateCrossFileAnyMatch(array('MA_BENH_CHINH' => 'A09.9'), '40.65');
assertCrossFileAnyMatch(1, $result['error_count'], 'Không có chẩn đoán phù hợp phải báo lỗi');

$result = validateCrossFileAnyMatch(array('MA_BENH_CHINH' => 'A09.9'), '40.77');
assertCrossFileAnyMatch(0, $result['error_count'], 'Thuốc ngoài danh sách phải được bỏ qua');

echo "CrossFileAnyMatchRuleTest: OK\n";
