<?php

// Chạy: C:\xampp\php\php.exe tests/HosoExportPeriodTest.php (không cần CSDL).
require_once dirname(__DIR__) . '/web_src/bean/FilePeer.php';

$method = new ReflectionMethod('FilePeer', 'khoaPeriodBounds');
$method->setAccessible(true);

$cases = array(
    array('2026-11-02', 'day', array('2026-11-02 00:00:00', '2026-11-03 00:00:00')),
    array('2024-02-29', 'day', array('2024-02-29 00:00:00', '2024-03-01 00:00:00')),
    array('2024-02', 'month', array('2024-02-01 00:00:00', '2024-03-01 00:00:00')),
    array('2026-12', 'month', array('2026-12-01 00:00:00', '2027-01-01 00:00:00'))
);

foreach ($cases as $case) {
    $actual = $method->invoke(null, $case[0], $case[1]);
    if ($actual !== $case[2]) {
        throw new RuntimeException('Sai khoảng ngày: ' . $case[1] . ' / ' . $case[0]);
    }
}

foreach (array(
    array('2025-02-29', 'day'),
    array('2026-11-31', 'day'),
    array('2026-2-01', 'day'),
    array('2026-13', 'month'),
    array('2026-2', 'month'),
    array('2026-11-02', 'month'),
    array('2026-11', 'year')
) as $case) {
    try {
        $method->invoke(null, $case[0], $case[1]);
    } catch (RuntimeException $e) {
        continue;
    }
    throw new RuntimeException('Chấp nhận giá trị không hợp lệ: ' . $case[1] . ' / ' . $case[0]);
}

echo "PASS HosoExportPeriodTest (lọc ngày/tháng theo update_at)\n";
