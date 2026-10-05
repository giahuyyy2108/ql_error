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

$v1Endpoint = 'https://provinces.open-api.vn/api/v1/p/';
$v2Endpoint = 'https://provinces.open-api.vn/api/v2/p/';

// Old province code => province code after the 2025 reorganization.
// Based on Resolution 202/2025/QH15 and checked against the API v2 code set.
$mergedCodes = array(
    1 => 1, 2 => 8, 4 => 4, 6 => 19, 8 => 8, 10 => 15, 11 => 11, 12 => 12,
    14 => 14, 15 => 15, 17 => 25, 19 => 19, 20 => 20, 22 => 22, 24 => 24,
    25 => 25, 26 => 25, 27 => 24, 30 => 31, 31 => 31, 33 => 33, 34 => 33,
    35 => 37, 36 => 37, 37 => 37, 38 => 38, 40 => 40, 42 => 42, 44 => 44,
    45 => 44, 46 => 46, 48 => 48, 49 => 48, 51 => 51, 52 => 52, 54 => 66,
    56 => 56, 58 => 56, 60 => 68, 62 => 51, 64 => 52, 66 => 66, 67 => 68,
    68 => 68, 70 => 75, 72 => 80, 74 => 79, 75 => 75, 77 => 79, 79 => 79,
    80 => 80, 82 => 82, 83 => 86, 84 => 86, 86 => 86, 87 => 82, 89 => 91,
    91 => 91, 92 => 92, 93 => 92, 94 => 92, 95 => 96, 96 => 96
);

function downloadProvinceJson($url)
{
    $context = stream_context_create(array(
        'http' => array('timeout' => 30, 'ignore_errors' => false),
        'ssl' => array('verify_peer' => true, 'verify_peer_name' => true)
    ));
    $content = @file_get_contents($url, false, $context);
    if ($content === false) {
        throw new RuntimeException('Không thể tải dữ liệu từ ' . $url);
    }
    $data = json_decode($content, true);
    if (!is_array($data)) {
        throw new RuntimeException('API tỉnh thành không trả về JSON hợp lệ.');
    }
    return $data;
}

$oldProvinces = downloadProvinceJson($v1Endpoint);
$newProvinces = downloadProvinceJson($v2Endpoint);
if (count($oldProvinces) !== 63 || count($newProvinces) !== 34) {
    throw new RuntimeException('Số lượng tỉnh thành từ API không đúng 63/34 như mong đợi.');
}

$oldCodes = array();
foreach ($oldProvinces as $province) {
    if (!isset($province['code'], $province['name'])) {
        throw new RuntimeException('Dữ liệu API v1 thiếu code hoặc name.');
    }
    $oldCodes[] = (int) $province['code'];
}
sort($oldCodes);
$mappingCodes = array_keys($mergedCodes);
sort($mappingCodes);
if ($oldCodes !== $mappingCodes) {
    throw new RuntimeException('Bảng ánh xạ không khớp đầy đủ mã tỉnh từ API v1.');
}

$newCodes = array_map(function ($province) {
    return (int) $province['code'];
}, $newProvinces);
sort($newCodes);
$mappedNewCodes = array_values(array_unique(array_values($mergedCodes)));
sort($mappedNewCodes);
if ($newCodes !== $mappedNewCodes) {
    throw new RuntimeException('Mã sau sáp nhập không khớp danh sách API v2.');
}

$db = new db_mysql();
$db->connect();
$db->selectdb();
$connect->begin_transaction();

try {
    $statement = $connect->prepare(
        'INSERT INTO tinhthanh (ten_tinhthanh, ma_cu, ma_sau_sapnhap)
         VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE
            ten_tinhthanh = VALUES(ten_tinhthanh),
            ma_sau_sapnhap = VALUES(ma_sau_sapnhap)'
    );
    if (!$statement) {
        throw new RuntimeException('Không thể chuẩn bị dữ liệu tỉnh thành.');
    }

    foreach ($oldProvinces as $province) {
        $name = trim((string) $province['name']);
        $oldCode = (int) $province['code'];
        $newCode = (int) $mergedCodes[$oldCode];
        $statement->bind_param('sii', $name, $oldCode, $newCode);
        if (!$statement->execute()) {
            throw new RuntimeException('Không thể lưu mã tỉnh ' . $oldCode . '.');
        }
    }
    $statement->close();
    $connect->commit();
} catch (Throwable $exception) {
    $connect->rollback();
    throw $exception;
}

$result = $connect->query(
    'SELECT COUNT(*) AS total, COUNT(DISTINCT ma_cu) AS old_total,
            COUNT(DISTINCT ma_sau_sapnhap) AS new_total
     FROM tinhthanh'
)->fetch_assoc();

echo 'Đã import ' . $result['total'] . ' dòng; '
    . $result['old_total'] . ' mã cũ và '
    . $result['new_total'] . ' mã sau sáp nhập.' . PHP_EOL;
