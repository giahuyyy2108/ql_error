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

$sqlPath = dirname(__DIR__) . '/sql/update_doituong_kcb_qd1804.sql';
$sql = file_get_contents($sqlPath);
if ($sql === false || trim($sql) === '') {
    throw new RuntimeException('Không thể đọc migration Quyết định 1804/QĐ-BYT.');
}

$db = new db_mysql();
$db->connect();
$db->selectdb();
if (!$connect->multi_query($sql)) {
    throw new RuntimeException('Không thể cập nhật danh mục mã loại KCB: ' . $connect->error);
}
do {
    if ($result = $connect->store_result()) $result->free();
} while ($connect->more_results() && $connect->next_result());
if ($connect->errno) {
    throw new RuntimeException('Cập nhật danh mục mã loại KCB thất bại: ' . $connect->error);
}

$summary = $connect->query(
    'SELECT COUNT(*) total, COUNT(DISTINCT ma) distinct_total, SUM(is_active=1) active_total
     FROM doituong_kcb'
)->fetch_assoc();
if ((int) $summary['total'] !== 16 || (int) $summary['distinct_total'] !== 16) {
    throw new RuntimeException('Danh mục sau cập nhật không đủ 16 mã 01-16.');
}
echo 'Đã cập nhật ' . $summary['total'] . ' mã loại KCB; '
    . $summary['active_total'] . ' mã đang hoạt động.' . PHP_EOL;
