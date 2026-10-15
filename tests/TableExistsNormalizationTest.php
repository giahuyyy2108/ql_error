<?php

require_once dirname(__DIR__) . '/web_src/common/XmlRulesValidator.php';

class NormalizationLookupStub
{
    public $values = array();

    public function preload(array $values, array $config)
    {
        foreach ($values as $value) $this->values[] = (string) $value;
    }

    public function lookup($value, array $config)
    {
        $this->values[] = (string) $value;
        return in_array((string) $value, array('79', 'B02.2'), true)
            ? array('matched_column' => $config['column'], 'return_column' => '', 'return_value' => null)
            : false;
    }
}

$decoded = array('DANHSACHHOSO' => array('HOSO' => array(array('FILEHOSO' => array(array(
    'LOAIHOSO' => 'XML1',
    'NOIDUNGFILE' => array('TONG_HOP' => array(
        'SO_CCCD' => '079167012805',
        'MA_BENH_CHINH' => 'B02.2†'
    ))
))))));
$rules = array(
    array(
        'id' => 12, 'file_type' => 'XML1', 'field_name' => 'SO_CCCD',
        'display_name' => 'CCCD', 'rule_type' => 'TABLE_EXISTS',
        'rule_value' => '{"table":"ma_tinhthanh","column":"ma_cu","value_substring":{"start":0,"length":3},"value_transform":"integer"}',
        'error_message' => 'Sai mã tỉnh', 'is_active' => 1
    ),
    array(
        'id' => 15, 'file_type' => 'XML1', 'field_name' => 'MA_BENH_CHINH',
        'display_name' => 'Mã bệnh chính', 'rule_type' => 'TABLE_EXISTS',
        'rule_value' => '{"table":"icd10","column":"code","remove_suffix":["*","†"]}',
        'error_message' => 'Sai ICD', 'is_active' => 1
    )
);
$lookup = new NormalizationLookupStub();
$result = XmlRulesValidator::validate($decoded, $rules, null, $lookup);
if ($result[0]['error_count'] !== 0
    || !in_array('79', $lookup->values, true)
    || !in_array('B02.2', $lookup->values, true)) {
    throw new RuntimeException('TABLE_EXISTS chưa chuẩn hóa đúng CCCD hoặc hậu tố ICD.');
}

echo "TableExistsNormalizationTest: OK\n";
