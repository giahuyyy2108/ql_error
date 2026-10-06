<?php

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Script này chỉ chạy từ dòng lệnh.\n");
    exit(1);
}

$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['SERVER_PORT'] = 80;
require dirname(__DIR__) . '/conf/config.php';
require dirname(__DIR__) . '/web_src/common/mysql.php';

$url = 'https://daklak.baohiemxahoi.gov.vn/Pages/thong-bao-moi.aspx?CateID=0&ItemID=5843';
$html = file_get_contents($url);
if ($html === false) throw new RuntimeException('Không thể tải danh mục BHYT từ nguồn chính thức.');

$start = strpos($html, 'Nhóm do NLĐ');
$end = $start === false ? false : strpos($html, 'Ký tự tiếp theo', $start);
if ($start === false || $end === false) throw new RuntimeException('Không tìm thấy danh sách nhóm BHYT trong trang nguồn.');

$segment = substr($html, $start, $end - $start);
$segment = preg_replace('/<br\s*\/?>/i', "\n", $segment);
$segment = preg_replace('/<\/(?:p|div|li)>/i', "\n", $segment);
$text = html_entity_decode(strip_tags($segment), ENT_QUOTES | ENT_HTML5, 'UTF-8');
$text = preg_replace('/[ \t]+/u', ' ', $text);
$text = preg_replace('/\R\s*/u', "\n", $text);

$descriptions = array();
foreach (explode("\n", $text) as $line) {
    $line = trim($line);
    if (preg_match('/^-\s*([A-Z]{2}):\s*(.+)$/u', $line, $matches)) {
        $descriptions[$matches[1]] = trim($matches[2]);
    }
}

$benefitLevels = array(
    1 => array('CC', 'TE'),
    2 => array('CK', 'CB', 'KC', 'HN', 'DT', 'DK', 'XD', 'BT', 'TS'),
    3 => array('HT', 'TC', 'CN'),
    4 => array('DN', 'HX', 'CH', 'NN', 'TK', 'HC', 'XK', 'TB', 'NO', 'CT', 'XB', 'TN', 'CS', 'XN', 'MS', 'HD', 'TQ', 'TV', 'TA', 'TY', 'HG', 'LS', 'PV', 'HS', 'SV', 'GB', 'GD'),
    5 => array('QN', 'CA', 'CY')
);
$levelByCode = array();
foreach ($benefitLevels as $level => $codes) foreach ($codes as $code) $levelByCode[$code] = $level;

$db = new db_mysql();
$db->connect();
$db->selectdb();
global $connect;
$connect->set_charset('utf8mb4');

$result = $connect->query("SELECT id FROM nhom_bhyt WHERE ten LIKE '%?%' OR mota LIKE '%?%'");
$corruptCodes = array();
while ($row = $result->fetch_assoc()) $corruptCodes[] = $row['id'];
$result->free();

$update = $connect->prepare('UPDATE nhom_bhyt SET ten=?, mota=?, dien=? WHERE id=?');
$updated = array();
$missing = array();
$connect->begin_transaction();
try {
    foreach ($corruptCodes as $code) {
        if (!isset($descriptions[$code], $levelByCode[$code])) {
            $missing[] = $code;
            continue;
        }
        $description = $descriptions[$code];
        $name = preg_split('/[.;]/u', $description, 2)[0];
        if (function_exists('mb_substr')) $name = mb_substr($name, 0, 255, 'UTF-8');
        else $name = substr($name, 0, 255);
        $level = $levelByCode[$code];
        $update->bind_param('ssis', $name, $description, $level, $code);
        if (!$update->execute()) throw new RuntimeException($update->error);
        $updated[] = $code;
    }
    $connect->commit();
} catch (Throwable $exception) {
    $connect->rollback();
    throw $exception;
} finally {
    $update->close();
}

echo 'Đã sửa UTF-8: ' . count($updated) . ' mã (' . implode(', ', $updated) . ").\n";
if ($missing) echo 'Không tìm thấy trong nguồn: ' . implode(', ', $missing) . ".\n";

