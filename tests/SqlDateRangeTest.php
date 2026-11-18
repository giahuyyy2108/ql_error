<?php
// Integration test: temporary tables only; no application records are changed.
// Run: C:\xampp\php\php.exe tests/SqlDateRangeTest.php
require_once dirname(__DIR__) . '/web_src/bean/FilePeer.php';
require_once dirname(__DIR__) . '/conf/config.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$db = new mysqli(_DATABASE_HOST_, _DATABASE_USERNAME_, _DATABASE_PASSWORD_, _DATABASE_NAME_);
$db->set_charset('utf8mb4');
$db->query("CREATE TEMPORARY TABLE file (
    id INT PRIMARY KEY, ten VARCHAR(50), ma_lk VARCHAR(50), file_path VARCHAR(100),
    update_at DATETIME(6), processing_status VARCHAR(30), da_day_cong INT DEFAULT 0,
    validation_result TEXT, id_khoa VARCHAR(20), revalidation_claimed_at DATETIME NULL
)");
$db->query("CREATE TEMPORARY TABLE cong_hoso (
    id INT PRIMARY KEY, cccd VARCHAR(20), malk VARCHAR(50), ten_file VARCHAR(50),
    nguoi_day VARCHAR(50), id_khoa VARCHAR(20), ngay_day DATETIME(6)
)");
$times = array('2024-02-28 23:59:59.999999', '2024-02-29 00:00:00',
    '2024-02-29 23:59:59.999999', '2024-03-01 00:00:00');
foreach ($times as $index => $time) {
    $id = $index + 1;
    $db->query("INSERT INTO file (id, ma_lk, update_at, processing_status, validation_result, id_khoa)
        VALUES ($id, 'record$id', '$time', 'processed', '[{\"warning_count\":2}]', 'K1')");
    $db->query("INSERT INTO cong_hoso (id, malk, ngay_day) VALUES ($id, 'portal$id', '$time')");
}
$peer = (new ReflectionClass('FilePeer'))->newInstanceWithoutConstructor();
$connection = new ReflectionProperty('FilePeer', 'connection');
$connection->setAccessible(true);
$connection->setValue($peer, $db);
function checkSqlRange($condition, $message) {
    if (!$condition) throw new RuntimeException($message);
}
checkSqlRange(array_column($peer->getPortalFiles('2024-02-29'), 'id') === array(2, 3), 'Portal date boundaries');
checkSqlRange(array_column($peer->getCongHosoData('2024-02-29'), 'id') === array(3, 2), 'Portal log date boundaries');
checkSqlRange(count($peer->getCongHosoData('2024-02-29', 'portal2')) === 1, 'Date and record parameter binding');
$progress = $peer->getRevalidationProgress('2024-02-29');
checkSqlRange($progress['total'] === 2 && $progress['warning_count'] === 4, 'Progress date boundaries');
checkSqlRange($peer->getRevalidationProgress()['total'] === 4, 'Unfiltered progress');
$db->query("UPDATE file SET processing_status = 'pending_revalidation'");
checkSqlRange(array_column($peer->claimPendingRevalidation(20, '2024-02-29', 'K1'), 'id') === array(2, 3), 'Queue date and department binding');
checkSqlRange(count($peer->claimPendingRevalidation(20, '2024-02-29')) === 0, 'Queue date-only binding');
checkSqlRange(count($peer->claimPendingRevalidation(20, null, 'K1')) === 2, 'Queue department-only binding');
$db->close();
echo "PASS SqlDateRangeTest (temporary tables, leap day, midnight boundaries, progress and queue filters)\n";
