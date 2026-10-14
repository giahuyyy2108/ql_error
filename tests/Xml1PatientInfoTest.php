<?php

require_once dirname(__DIR__) . '/web_src/common/XmlFileDecoder.php';

$decoded = array(
    'DANHSACHHOSO' => array(
        'HOSO' => array(array(
            'FILEHOSO' => array(
                array(
                    'LOAIHOSO' => 'XML2',
                    'NOIDUNGFILE' => array('ROOT' => array(
                        'MA_THE_BHYT' => 'SAI',
                        'HO_TEN' => 'Tên XML2',
                        'NGAY_SINH' => '19000101'
                    ))
                ),
                array(
                    'LOAIHOSO' => 'XML1',
                    'NOIDUNGFILE' => array('TONG_HOP' => array(
                        'MA_THE_BHYT' => 'DN4010123456789',
                        'HO_TEN' => 'Nguyễn Văn A',
                        'NGAY_SINH' => '19850520'
                    ))
                )
            )
        ))
    )
);

$actual = XmlFileDecoder::extractValuesFromFileType(
    $decoded,
    'XML1',
    array('MA_THE_BHYT', 'HO_TEN', 'NGAY_SINH')
);
$expected = array(
    'MA_THE_BHYT' => 'DN4010123456789',
    'HO_TEN' => 'Nguyễn Văn A',
    'NGAY_SINH' => '19850520'
);
if ($actual !== $expected) {
    throw new RuntimeException('Lấy thông tin bệnh nhân XML1 không đúng: ' . json_encode($actual));
}

echo "Xml1PatientInfoTest: OK\n";
