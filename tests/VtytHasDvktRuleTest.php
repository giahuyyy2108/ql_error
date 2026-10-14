<?php

require_once dirname(__DIR__) . '/web_src/common/XmlRulesValidator.php';

function validateVtytRows(array $rows)
{
    $decoded = array(
        'DANHSACHHOSO' => array(
            'HOSO' => array(array(
                'FILEHOSO' => array(array(
                    'LOAIHOSO' => 'XML3',
                    'NOIDUNGFILE' => array(
                        'DSACH_CHI_TIET_DVKT' => array('CHI_TIET_DVKT' => $rows)
                    )
                ))
            ))
        )
    );
    $rules = array(array(
        'id' => 1000,
        'file_type' => 'XML3',
        'field_name' => 'GOI_VTYT',
        'display_name' => 'Gói vật tư y tế',
        'rule_type' => 'VTYT_HAS_DVKT',
        'rule_value' => '',
        'error_message' => 'VTYT có GOI_VTYT nhưng thiếu DVKT tương ứng.',
        'is_active' => 1
    ));
    $results = XmlRulesValidator::validate($decoded, $rules);
    return $results[0];
}

function assertVtytSame($expected, $actual, $message)
{
    if ($expected !== $actual) {
        throw new RuntimeException($message . ': expected ' . var_export($expected, true)
            . ', got ' . var_export($actual, true));
    }
}

$result = validateVtytRows(array(
    array('MA_DICH_VU' => 'DV01', 'MA_VAT_TU' => '', 'GOI_VTYT' => 'G1'),
    array('MA_DICH_VU' => 'DV01', 'MA_VAT_TU' => 'VT01', 'GOI_VTYT' => 'G1')
));
assertVtytSame(0, $result['error_count'], 'VTYT có DVKT cùng mã phải hợp lệ');

$result = validateVtytRows(array(
    array('MA_DICH_VU' => 'DV01', 'MA_VAT_TU' => '', 'GOI_VTYT' => 'G1'),
    array('MA_DICH_VU' => 'DV02', 'MA_VAT_TU' => 'VT01', 'GOI_VTYT' => 'G1')
));
assertVtytSame(1, $result['error_count'], 'VTYT khác mã DVKT phải báo lỗi');
assertVtytSame('DV02', $result['errors'][0]['service_code'], 'Lỗi phải ghi nhận MA_DICH_VU của VTYT');
assertVtytSame('MA_DICH_VU', $result['errors'][0]['field_name'], 'Lỗi phải gắn vào MA_DICH_VU');
assertVtytSame(
    'DSACH_CHI_TIET_DVKT.CHI_TIET_DVKT[1].MA_DICH_VU',
    $result['errors'][0]['path'],
    'Lỗi phải hiển thị tại MA_DICH_VU của dòng VTYT'
);

$result = validateVtytRows(array(
    array('MA_DICH_VU' => 'DV02', 'MA_VAT_TU' => 'VT01', 'GOI_VTYT' => '')
));
assertVtytSame(0, $result['error_count'], 'VTYT không có GOI_VTYT phải được bỏ qua');

$result = validateVtytRows(array(
    array('MA_DICH_VU' => 'DV02', 'MA_VAT_TU' => 'VT01', 'GOI_VTYT' => 'G1'),
    array('MA_DICH_VU' => 'DV02', 'MA_VAT_TU' => 'VT02', 'GOI_VTYT' => 'G2')
));
assertVtytSame(2, $result['error_count'], 'Dòng VTYT không được tự khớp với dòng VTYT khác');

echo "VtytHasDvktRuleTest: OK\n";
