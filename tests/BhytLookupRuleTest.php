<?php

require_once dirname(__DIR__) . '/web_src/common/XmlRulesValidator.php';

class FakeBhytLookupService
{
    private $response;
    public function __construct(array $response) { $this->response = $response; }
    public function traCuuThongTin($maThe, $hoTen, $ngaySinh) { return $this->response; }
}

function validateBhytLookup(array $response)
{
    $decoded = array('DANHSACHHOSO' => array('HOSO' => array(array('FILEHOSO' => array(array(
        'LOAIHOSO' => 'XML1',
        'NOIDUNGFILE' => array('TONG_HOP' => array(
            'MA_THE_BHYT' => 'DN4-010123456789',
            'HO_TEN' => 'Nguyễn  Văn A',
            'NGAY_SINH' => '198505200000'
        ))
    ))))));
    $rules = array(array(
        'id' => 10002, 'file_type' => 'XML1', 'field_name' => 'MA_THE_BHYT',
        'display_name' => 'Thông tin hành chính BHYT', 'rule_type' => 'BHYT_LOOKUP',
        'rule_value' => '{}', 'error_message' => 'Thông tin hành chính không khớp.', 'is_active' => 1
    ));
    return XmlRulesValidator::validate(
        $decoded, $rules, null, null, new FakeBhytLookupService($response)
    )[0];
}

$valid = validateBhytLookup(array(
    'maKetQua' => '000', 'maThe' => 'DN4010123456789',
    'hoTen' => 'NGUYỄN VĂN A', 'ngaySinh' => '20/05/1985'
));
if ($valid['error_count'] !== 0) throw new RuntimeException('Ba trường trùng phải hợp lệ: ' . json_encode($valid['errors'], JSON_UNESCAPED_UNICODE));

$yearOnly = validateBhytLookup(array(
    'maKetQua' => '061', 'maThe' => 'DN4010123456789',
    'hoTen' => 'Nguyễn Văn A', 'ngaySinh' => '1985'
));
if ($yearOnly['error_count'] !== 0) throw new RuntimeException('BHXH chỉ có năm sinh vẫn phải đối chiếu được.');

$invalid = validateBhytLookup(array(
    'maKetQua' => '000', 'maThe' => 'DN4010123456789',
    'hoTen' => 'Nguyễn Văn B', 'ngaySinh' => '20/05/1985'
));
if ($invalid['error_count'] !== 1 || $invalid['errors'][0]['field_name'] !== 'HO_TEN'
    || strpos($invalid['errors'][0]['message'], 'HO_TEN') !== 0) {
    throw new RuntimeException('Sai họ tên phải tạo đúng một lỗi đối chiếu.');
}

echo "BhytLookupRuleTest: OK\n";
