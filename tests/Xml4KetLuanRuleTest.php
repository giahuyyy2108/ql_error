<?php

require_once dirname(__DIR__) . '/web_src/common/XmlRulesValidator.php';

function validateKetLuanRule(array $xml3Rows, array $xml4Rows)
{
    $decoded = array(
        'DANHSACHHOSO' => array(
            'HOSO' => array(array(
                'FILEHOSO' => array(
                    array(
                        'LOAIHOSO' => 'XML3',
                        'NOIDUNGFILE' => array('DSACH_CHI_TIET_DVKT' => array('CHI_TIET_DVKT' => $xml3Rows))
                    ),
                    array(
                        'LOAIHOSO' => 'XML4',
                        'NOIDUNGFILE' => array('DSACH_CHI_TIET_CLS' => array('CHI_TIET_CLS' => $xml4Rows))
                    )
                )
            ))
        )
    );
    $rules = array(array(
        'id' => 999,
        'file_type' => 'XML4',
        'field_name' => 'KET_LUAN',
        'display_name' => 'Kết luận dịch vụ kỹ thuật nhóm 2',
        'rule_type' => 'CROSS_FILE_REQUIRED_BY_KEY',
        'rule_value' => json_encode(array(
            'source_key' => 'MA_DICH_VU',
            'when_match' => array(
                'file_type' => 'XML3',
                'target_key' => 'MA_DICH_VU',
                'field' => 'MA_NHOM',
                'operator' => '=',
                'value' => '2',
                'data_type' => 'string'
            ),
            'error_when_source_missing' => false
        )),
        'error_message' => 'KẾT LUẬN không được để trống đối với dịch vụ có MÃ NHÓM = 2.',
        'is_active' => 1
    ));
    $results = XmlRulesValidator::validate($decoded, $rules);
    return $results[1];
}

function assertSameValue($expected, $actual, $message)
{
    if ($expected !== $actual) {
        throw new RuntimeException($message . ': expected ' . var_export($expected, true)
            . ', got ' . var_export($actual, true));
    }
}

$result = validateKetLuanRule(
    array(
        array('MA_DICH_VU' => 'DV01', 'MA_NHOM' => '2'),
        array('MA_DICH_VU' => 'DV02', 'MA_NHOM' => '1')
    ),
    array(
        array('MA_DICH_VU' => 'DV01', 'KET_LUAN' => '   '),
        array('MA_DICH_VU' => 'DV02', 'KET_LUAN' => '')
    )
);
assertSameValue('XML4', $result['file_type'], 'Lỗi phải thuộc XML4');
assertSameValue(1, $result['error_count'], 'Chỉ DV01 phải báo lỗi');
assertSameValue('DV01', $result['errors'][0]['key_value'], 'Lỗi phải gắn đúng MA_DICH_VU');
assertSameValue('KET_LUAN', $result['errors'][0]['field_name'], 'Lỗi phải gắn vào KET_LUAN');

$result = validateKetLuanRule(
    array(array('MA_DICH_VU' => 'DV01', 'MA_NHOM' => '2')),
    array(array('MA_DICH_VU' => 'DV01', 'KET_LUAN' => 'Bình thường'))
);
assertSameValue(0, $result['error_count'], 'KET_LUAN có dữ liệu phải hợp lệ');

$result = validateKetLuanRule(
    array(array('MA_DICH_VU' => 'DV03', 'MA_NHOM' => '2')),
    array()
);
assertSameValue(0, $result['error_count'], 'Thiếu MA_DICH_VU trong XML4 phải được bỏ qua');

echo "Xml4KetLuanRuleTest: OK\n";
