<?php

require_once dirname(__DIR__) . '/web_src/common/XmlRulesValidator.php';

$decoded = array(
    'DANHSACHHOSO' => array(
        'HOSO' => array(array(
            'FILEHOSO' => array(
                array(
                    'LOAIHOSO' => 'XML3',
                    'NOIDUNGFILE' => array(
                        'DSACH_CHI_TIET_DVKT' => array(
                            'CHI_TIET_DVKT' => array(
                                array(
                                    'MA_DICH_VU' => 'DV01',
                                    'TEN_DICH_VU' => 'Dich vu DV01',
                                    'MA_NHOM' => '2'
                                )
                            )
                        )
                    )
                ),
                array(
                    'LOAIHOSO' => 'XML4',
                    'NOIDUNGFILE' => array(
                        'DSACH_CHI_TIET_CLS' => array(
                            'CHI_TIET_CLS' => array(
                                array('MA_DICH_VU' => 'DV01', 'KET_LUAN' => '')
                            )
                        )
                    )
                )
            )
        ))
    )
);

$rules = array(array(
    'id' => 99,
    'file_type' => 'XML4',
    'field_name' => 'KET_LUAN',
    'display_name' => 'KET_LUAN',
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
    'error_message' => 'KET_LUAN required for {TEN_DICH_VU}.',
    'is_active' => 1
));

$results = XmlRulesValidator::validate($decoded, $rules);
$result = $results[1];

if ($result['error_count'] !== 1) {
    throw new RuntimeException('Expected exactly one XML4 error.');
}
if ($result['errors'][0]['message'] !== 'KET_LUAN required for Dich vu DV01.') {
    throw new RuntimeException('TEN_DICH_VU was not interpolated into the error message.');
}
if (!isset($result['errors'][0]['service_name'])
    || $result['errors'][0]['service_name'] !== 'Dich vu DV01') {
    throw new RuntimeException('service_name was not attached to the error.');
}

echo "Xml4KetLuanServiceNameRuleTest: OK\n";
