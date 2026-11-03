<?php

require_once dirname(__DIR__) . '/web_src/common/XmlFileImportService.php';

class FakeBhytCardFilePeer
{
    public $savedArguments;
    public $existing = false;

    public function resolveRootKhoaCode($code)
    {
        return $code;
    }

    public function getByMaLk($maLk)
    {
        return $this->existing ? array('id' => 17, 'file_path' => 'previous.xml') : false;
    }

    public function insert(...$arguments)
    {
        $this->savedArguments = $arguments;
        return 18;
    }

    public function replaceImportedFile(...$arguments)
    {
        $this->savedArguments = $arguments;
    }
}

$decoded = array('content' => array('DANHSACHHOSO' => array('HOSO' => array(
    array('FILEHOSO' => array(
        array('LOAIHOSO' => 'XML1', 'NOIDUNGFILE' => array('TONG_HOP' => array(
            'MA_LK' => 'LK-TEST', 'MA_BN' => 'BN-TEST', 'HO_TEN' => 'Test Patient',
            'MA_THE_BHYT' => 'DN4010123456789', 'NGAY_SINH' => '19900101',
            'GIOI_TINH' => '2', 'DIA_CHI' => 'XML Address', 'MA_DKBD' => '79025',
            'MA_KHOA' => 'K01'
        )))
    ))
))), 'ngay_lap' => '20261030');

$lookupCases = array(
    'no lookup' => null,
    'lookup returns null' => function () { return null; },
    'lookup throws' => function () { throw new RuntimeException('BHYT unavailable'); },
    'lookup rejected' => function () {
        return array('maKetQua' => '205', 'diaChi' => 'Should Not Use');
    },
    'lookup succeeds' => function () {
        return array('maKetQua' => '000', 'maThe' => 'DIFFERENT-CARD',
            'gioiTinh' => 'Nam', 'diaChi' => 'API Address');
    },
    'nested lookup succeeds' => function () {
        return array('data' => array('maKetQua' => '000', 'gioiTinh' => 'Nam'));
    }
);

foreach ($lookupCases as $case => $lookup) {
    foreach (array(false, true) as $reimport) {
        $peer = new FakeBhytCardFilePeer();
        $peer->existing = $reimport;
        $importer = new XmlFileImportService($peer, 5242880, null, $lookup);
        $result = $importer->importContent('sample.xml', null, 200, 'sample.xml', $decoded);
        $patientInfo = $peer->savedArguments[9];
        if (!is_array($patientInfo) || !isset($patientInfo['maThe'])
            || $patientInfo['maThe'] !== 'DN4010123456789') {
            throw new RuntimeException('Card was not preserved for ' . $case . ', reimport=' . (int) $reimport);
        }
        foreach (array('maBn' => 'BN-TEST', 'hoTen' => 'Test Patient',
            'ngaySinh' => '19900101', 'maDKBD' => '79025') as $key => $expected) {
            if (!isset($patientInfo[$key]) || $patientInfo[$key] !== $expected) {
                throw new RuntimeException('Patient information missing: ' . $key . ' in ' . $case);
            }
        }
        if ($result['status'] !== ($reimport ? 'reimported' : 'imported')) {
            throw new RuntimeException('Unexpected status for ' . $case);
        }
        if (strpos($case, 'succeeds') !== false && $patientInfo['gioiTinh'] !== 'Nam') {
            throw new RuntimeException('Other BHYT lookup details were lost');
        }
        $expectedAddress = $case === 'lookup succeeds' ? 'API Address' : 'XML Address';
        if ($patientInfo['diaChi'] !== $expectedAddress) {
            throw new RuntimeException('Wrong address in ' . $case);
        }
        $expectedStatus = $lookup === null ? 'not_checked'
            : ($case === 'lookup rejected' ? 'rejected'
                : ($case === 'lookup returns null' ? 'empty'
                    : ($case === 'lookup throws' ? 'failed' : 'success')));
        if ($patientInfo['bhytLookupStatus'] !== $expectedStatus) {
            throw new RuntimeException('Wrong BHYT status in ' . $case);
        }
    }
}

// Kiểm tra cả luồng XMLReader thực tế (XML1 nằm trong NOIDUNGFILE Base64).
$innerXml = '<TONG_HOP><MA_LK>LK-PATH</MA_LK><MA_BN>BN-PATH</MA_BN>'
    . '<HO_TEN>Sample Patient</HO_TEN><MA_THE_BHYT>DN4010123456789</MA_THE_BHYT>'
    . '<NGAY_SINH>19900101</NGAY_SINH><DIA_CHI>Path Address</DIA_CHI></TONG_HOP>';
$outerXml = '<GIAMDINHHS><THONGTINHOSO><NGAYLAP>20261030</NGAYLAP></THONGTINHOSO>'
    . '<DANHSACHHOSO><HOSO><FILEHOSO><LOAIHOSO>XML1</LOAIHOSO>'
    . '<NOIDUNGFILE>' . base64_encode($innerXml) . '</NOIDUNGFILE>'
    . '</FILEHOSO></HOSO></DANHSACHHOSO></GIAMDINHHS>';
$temporaryPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'bhyt-import-' . bin2hex(random_bytes(6)) . '.xml';
file_put_contents($temporaryPath, $outerXml);
try {
    $peer = new FakeBhytCardFilePeer();
    $importer = new XmlFileImportService($peer);
    $importer->importPath($temporaryPath);
    if ($peer->savedArguments[9]['hoTen'] !== 'Sample Patient'
        || $peer->savedArguments[9]['diaChi'] !== 'Path Address') {
        throw new RuntimeException('Real XML import did not preserve patient information');
    }
} finally {
    @unlink($temporaryPath);
}

echo "XmlImportBhytCardTest: OK\n";
