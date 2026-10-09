<?php

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Script này chỉ được chạy từ dòng lệnh.\n");
    exit(1);
}

date_default_timezone_set('Asia/Saigon');
$_SERVER['HTTPS'] = 'off';
$_SERVER['SERVER_PORT'] = 80;
$_SERVER['HTTP_HOST'] = 'localhost';
$connect = '';

require dirname(__DIR__) . '/conf/config.php';
require dirname(__DIR__) . '/web_src/common/mysql.php';

const API_V1 = 'https://provinces.open-api.vn/api/v1/?depth=3';
const API_V2 = 'https://provinces.open-api.vn/api/v2/?depth=2';
const CONVERSION_URL = 'https://raw.githubusercontent.com/sunshine-tech/VietnamProvinces/main/vietnam_provinces/_ward_conversion_2025.py';
const SOURCE_URL = 'https://provinces.open-api.vn/';

function downloadText($url)
{
    $context = stream_context_create(array(
        'http' => array(
            'timeout' => 120,
            'ignore_errors' => false,
            'header' => "User-Agent: ql-error-province-importer/1.0\r\n"
        ),
        'ssl' => array('verify_peer' => true, 'verify_peer_name' => true)
    ));
    $content = @file_get_contents($url, false, $context);
    if ($content === false || $content === '') {
        throw new RuntimeException('Không thể tải dữ liệu từ ' . $url);
    }
    return $content;
}

function downloadJson($url)
{
    $data = json_decode(downloadText($url), true);
    if (!is_array($data)) {
        throw new RuntimeException('API không trả về JSON hợp lệ: ' . $url);
    }
    return $data;
}

function buildLegacyLookup(array $provinces)
{
    $lookup = array();
    foreach ($provinces as $province) {
        foreach (isset($province['districts']) && is_array($province['districts']) ? $province['districts'] : array() as $district) {
            foreach (isset($district['wards']) && is_array($district['wards']) ? $district['wards'] : array() as $ward) {
                $lookup[(int) $ward['code']] = array(
                    'province_code' => (int) $province['code'],
                    'province_name' => trim((string) $province['name']),
                    'district_code' => (int) $district['code'],
                    'district_name' => trim((string) $district['name']),
                    'district_type' => trim((string) $district['division_type']),
                    'ward_code' => (int) $ward['code'],
                    'ward_name' => trim((string) $ward['name']),
                    'ward_type' => trim((string) $ward['division_type'])
                );
            }
        }
    }
    return $lookup;
}

function buildCurrentLookup(array $provinces)
{
    $lookup = array();
    foreach ($provinces as $province) {
        foreach (isset($province['wards']) && is_array($province['wards']) ? $province['wards'] : array() as $ward) {
            $lookup[(int) $ward['code']] = array(
                'province_code' => (int) $province['code'],
                'province_name' => trim((string) $province['name']),
                'ward_code' => (int) $ward['code'],
                'ward_name' => trim((string) $ward['name']),
                'ward_type' => trim((string) $ward['division_type'])
            );
        }
    }
    return $lookup;
}

function buildProvinceNameLookup(array $provinces)
{
    $lookup = array();
    foreach ($provinces as $province) {
        $lookup[(int) $province['code']] = trim((string) $province['name']);
    }
    return $lookup;
}

function buildLegacyDistrictLookup(array $provinces)
{
    $lookup = array();
    foreach ($provinces as $province) {
        foreach (isset($province['districts']) && is_array($province['districts']) ? $province['districts'] : array() as $district) {
            $lookup[(int) $district['code']] = array(
                'district_name' => trim((string) $district['name']),
                'district_type' => trim((string) $district['division_type'])
            );
        }
    }
    return $lookup;
}

function parseConversions($pythonSource)
{
    $start = strpos($pythonSource, 'OLD_TO_NEW:');
    $end = strpos($pythonSource, 'NEW_TO_OLD:', $start === false ? 0 : $start);
    if ($start === false || $end === false) {
        throw new RuntimeException('Không tìm thấy bảng OLD_TO_NEW trong dữ liệu chuyển đổi.');
    }
    $section = substr($pythonSource, $start, $end - $start);
    preg_match_all(
        '/^\s*\d+:\s*OldToNewEntry\((.*?)(?=^\s*\d+:\s*OldToNewEntry\(|^\})/ms',
        $section,
        $entries
    );

    $result = array();
    foreach ($entries[1] as $entry) {
        if (!preg_match('/OldWardRef\((\d+),\s*(\d+),\s*(\d+),\s*(True|False)\)/', $entry, $old)) {
            continue;
        }
        preg_match_all('/NewWardRef\((\d+),\s*(\d+)\)/', $entry, $newMatches, PREG_SET_ORDER);
        foreach ($newMatches as $new) {
            $result[] = array(
                'old_ward_code' => (int) $old[1],
                'old_district_code' => (int) $old[2],
                'old_province_code' => (int) $old[3],
                'partly_merged' => $old[4] === 'True' ? 1 : 0,
                'new_ward_code' => (int) $new[1],
                'new_province_code' => (int) $new[2]
            );
        }
    }
    if (empty($result)) {
        throw new RuntimeException('Không phân tích được dữ liệu chuyển đổi phường xã.');
    }
    return $result;
}

$legacyProvinces = downloadJson(API_V1);
$currentProvinces = downloadJson(API_V2);
$legacy = buildLegacyLookup($legacyProvinces);
$legacyProvinceNames = buildProvinceNameLookup($legacyProvinces);
$legacyDistricts = buildLegacyDistrictLookup($legacyProvinces);
$current = buildCurrentLookup($currentProvinces);
$conversions = parseConversions(downloadText(CONVERSION_URL));

// Một số đơn vị chuyển tiếp không còn nằm trong danh sách lồng của API v1.
// Chỉ với các mã này, lấy lại thông tin cũ từ endpoint đối chiếu chính thức của API v2.
$legacySourceCache = array();
foreach ($conversions as $mapping) {
    $oldCode = $mapping['old_ward_code'];
    $newCode = $mapping['new_ward_code'];
    if (isset($legacy[$oldCode])) {
        continue;
    }
    if (!isset($legacySourceCache[$newCode])) {
        $legacySourceCache[$newCode] = downloadJson(
            'https://provinces.open-api.vn/api/v2/w/' . $newCode . '/to-legacies/'
        );
    }
    foreach ($legacySourceCache[$newCode] as $ward) {
        if ((int) $ward['code'] !== $oldCode) {
            continue;
        }
        $districtCode = (int) $ward['district_code'];
        if (!isset($legacyDistricts[$districtCode])) {
            break;
        }
        $legacy[$oldCode] = array(
            'province_code' => (int) $ward['province_code'],
            'province_name' => isset($legacyProvinceNames[(int) $ward['province_code']])
                ? $legacyProvinceNames[(int) $ward['province_code']] : '',
            'district_code' => $districtCode,
            'district_name' => $legacyDistricts[$districtCode]['district_name'],
            'district_type' => $legacyDistricts[$districtCode]['district_type'],
            'ward_code' => $oldCode,
            'ward_name' => trim((string) $ward['name']),
            'ward_type' => trim((string) $ward['division_type'])
        );
        break;
    }
}

$db = new db_mysql();
$db->connect();
$db->selectdb();
$connect->set_charset('utf8mb4');
$connect->begin_transaction();

try {
    if (!$connect->query('DELETE FROM `quanhuyen_phuongxa`')) {
        throw new RuntimeException('Không thể làm sạch dữ liệu quận huyện/phường xã cũ.');
    }
    $statement = $connect->prepare(
        'INSERT INTO `quanhuyen_phuongxa`
         (`ma_tinh_cu`, `ten_tinh_cu`, `ma_quanhuyen_cu`, `ten_quanhuyen_cu`, `loai_quanhuyen_cu`,
          `ma_phuongxa_cu`, `ten_phuongxa_cu`, `loai_phuongxa_cu`, `ma_tinh_moi`, `ten_tinh_moi`,
          `ma_phuongxa_moi`, `ten_phuongxa_moi`, `loai_phuongxa_moi`, `sap_nhap_mot_phan`, `nguon`)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    if (!$statement) {
        throw new RuntimeException('Không thể chuẩn bị câu lệnh import.');
    }

    $inserted = 0;
    $skipped = 0;
    $skippedPairs = array();
    foreach ($conversions as $mapping) {
        $oldCode = $mapping['old_ward_code'];
        $newCode = $mapping['new_ward_code'];
        if (!isset($legacy[$oldCode], $current[$newCode], $legacyProvinceNames[$mapping['old_province_code']])) {
            $skipped++;
            $skippedPairs[] = $oldCode . '→' . $newCode;
            continue;
        }
        $old = $legacy[$oldCode];
        $new = $current[$newCode];
        if ($old['district_code'] !== $mapping['old_district_code']
            || $new['province_code'] !== $mapping['new_province_code']) {
            throw new RuntimeException(
                'Mã hành chính không khớp tại phường/xã cũ ' . $oldCode
                . ' (API: ' . $old['district_code'] . '/' . $old['province_code'] . ' → ' . $new['province_code']
                . '; ánh xạ: ' . $mapping['old_district_code'] . '/' . $mapping['old_province_code']
                . ' → ' . $mapping['new_province_code'] . ').'
            );
        }
        $source = SOURCE_URL;
        $statement->bind_param(
            'isississisissis',
            $mapping['old_province_code'], $legacyProvinceNames[$mapping['old_province_code']],
            $old['district_code'], $old['district_name'], $old['district_type'],
            $old['ward_code'], $old['ward_name'], $old['ward_type'],
            $new['province_code'], $new['province_name'],
            $new['ward_code'], $new['ward_name'], $new['ward_type'],
            $mapping['partly_merged'], $source
        );
        if (!$statement->execute()) {
            throw new RuntimeException('Không thể lưu ánh xạ phường/xã ' . $oldCode . ' → ' . $newCode . '.');
        }
        $inserted++;
    }
    $statement->close();
    $connect->commit();
} catch (Throwable $exception) {
    $connect->rollback();
    throw $exception;
}

echo 'Đã import ' . $inserted . ' ánh xạ; bỏ qua ' . $skipped . ' ánh xạ thiếu danh mục.' . PHP_EOL;
if (!empty($skippedPairs)) {
    echo 'Các ánh xạ bị bỏ qua: ' . implode(', ', $skippedPairs) . PHP_EOL;
}
