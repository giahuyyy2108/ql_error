<?php

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only.\n");
    exit(1);
}

$xlsx = $argv[1] ?? '';
if ($xlsx === '' || !is_file($xlsx)) {
    fwrite(STDERR, "Usage: php scripts/import_danh_muc_dvkt_ma_may_xlsx.php <file.xlsx>\n");
    exit(1);
}

$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['SERVER_PORT'] = 80;
require dirname(__DIR__) . '/conf/config.php';
require dirname(__DIR__) . '/web_src/common/mysql.php';

function dmText($value): string
{
    return trim((string)($value ?? ''));
}

function dmBool($value): int
{
    $value = mb_strtolower(dmText($value), 'UTF-8');
    return in_array($value, ['có', 'co', '1', 'true', 'yes'], true) ? 1 : 0;
}

function dmDate($value): ?string
{
    $value = dmText($value);
    if ($value === '') return null;
    foreach (['d/m/Y', 'Y-m-d'] as $format) {
        $date = DateTime::createFromFormat('!' . $format, $value);
        if ($date && $date->format($format) === $value) return $date->format('Y-m-d');
    }
    return null;
}

function dmRows(string $path): array
{
    $zip = new ZipArchive();
    if ($zip->open($path) !== true) throw new RuntimeException('Không thể mở file XLSX.');
    $shared = [];
    $sharedXml = $zip->getFromName('xl/sharedStrings.xml');
    if ($sharedXml !== false) {
        $dom = new DOMDocument();
        $dom->loadXML($sharedXml);
        $xp = new DOMXPath($dom);
        $xp->registerNamespace('x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        foreach ($xp->query('//x:si') as $si) {
            $parts = [];
            foreach ($xp->query('.//x:t', $si) as $t) $parts[] = $t->textContent;
            $shared[] = implode('', $parts);
        }
    }
    $sheetName = null;
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $name = $zip->getNameIndex($i);
        if (preg_match('#^xl/worksheets/sheet\d+\.xml$#', $name)) {
            $sheetName = $name;
            break;
        }
    }
    if ($sheetName === null) throw new RuntimeException('Không tìm thấy worksheet trong XLSX.');
    $dom = new DOMDocument();
    $dom->loadXML($zip->getFromName($sheetName));
    $xp = new DOMXPath($dom);
    $xp->registerNamespace('x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
    $rows = [];
    foreach ($xp->query('//x:sheetData/x:row') as $rowNode) {
        $cells = [];
        foreach ($xp->query('./x:c', $rowNode) as $cell) {
            if (!preg_match('/^([A-Z]+)/', $cell->getAttribute('r'), $match)) continue;
            $type = $cell->getAttribute('t');
            if ($type === 'inlineStr') {
                $parts = [];
                foreach ($xp->query('.//x:t', $cell) as $text) $parts[] = $text->textContent;
                $value = implode('', $parts);
            } else {
                $node = $xp->query('./x:v', $cell)->item(0);
                $raw = $node ? $node->textContent : '';
                $value = ($type === 's' && $raw !== '') ? ($shared[(int)$raw] ?? '') : $raw;
            }
            $cells[$match[1]] = $value;
        }
        $rows[] = $cells;
    }
    $zip->close();
    return $rows;
}

$rows = dmRows($xlsx);
$header = array_shift($rows);
$expected = ['A' => 'STT', 'B' => 'Mã', 'C' => 'Tên DVKT', 'D' => 'Hiệu lực',
    'E' => 'Ngày hiệu lực', 'F' => 'Ngày hết hiệu lực', 'G' => 'Trả kết quả sau'];
foreach ($expected as $column => $name) {
    if (mb_strtolower(dmText($header[$column] ?? ''), 'UTF-8') !== mb_strtolower($name, 'UTF-8')) {
        throw new RuntimeException("Cột {$column} phải là '{$name}'.");
    }
}

$db = new db_mysql();
$db->connect();
$db->selectdb();
global $connect;
$connect->set_charset('utf8mb4');
$schema = file_get_contents(dirname(__DIR__) . '/sql/create_danh_muc_dvkt_ma_may.sql');
if (!$connect->multi_query($schema)) throw new RuntimeException($connect->error);
do {
    if ($result = $connect->store_result()) $result->free();
} while ($connect->more_results() && $connect->next_result());

$sql = 'INSERT INTO danh_muc_dvkt_ma_may
    (stt, ma_dvkt, ten_dvkt, hieu_luc, ngay_hieu_luc, ngay_het_hieu_luc, tra_ket_qua_sau, source_file)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE ma_dvkt=VALUES(ma_dvkt), ten_dvkt=VALUES(ten_dvkt),
        hieu_luc=VALUES(hieu_luc), ngay_hieu_luc=VALUES(ngay_hieu_luc),
        ngay_het_hieu_luc=VALUES(ngay_het_hieu_luc),
        tra_ket_qua_sau=VALUES(tra_ket_qua_sau), source_file=VALUES(source_file)';
$stmt = $connect->prepare($sql);
if (!$stmt) throw new RuntimeException($connect->error);

$processed = 0;
$invalid = 0;
$sourceFile = basename($xlsx);
$connect->begin_transaction();
try {
    foreach ($rows as $row) {
        $sttText = dmText($row['A'] ?? '');
        $ma = dmText($row['B'] ?? '');
        $ten = dmText($row['C'] ?? '');
        if ($sttText === '' && $ma === '' && $ten === '') continue;
        if (!ctype_digit($sttText) || $ma === '' || $ten === '') {
            $invalid++;
            continue;
        }
        $stt = (int)$sttText;
        $hieuLuc = dmBool($row['D'] ?? '');
        $ngayHieuLuc = dmDate($row['E'] ?? '');
        $ngayHetHieuLuc = dmDate($row['F'] ?? '');
        $traKetQuaSau = dmBool($row['G'] ?? '');
        $stmt->bind_param('ississis', $stt, $ma, $ten, $hieuLuc, $ngayHieuLuc, $ngayHetHieuLuc, $traKetQuaSau, $sourceFile);
        if (!$stmt->execute()) throw new RuntimeException("Dòng {$stt}: " . $stmt->error);
        $processed++;
    }
    $connect->commit();
} catch (Throwable $exception) {
    $connect->rollback();
    throw $exception;
} finally {
    $stmt->close();
}

$stats = $connect->query('SELECT COUNT(*) total, COUNT(DISTINCT ma_dvkt) unique_codes,
    SUM(hieu_luc = 1) active_rows, SUM(tra_ket_qua_sau = 1) delayed_result_rows
    FROM danh_muc_dvkt_ma_may')->fetch_assoc();
fwrite(STDOUT, json_encode([
    'xlsx_rows' => count($rows),
    'processed' => $processed,
    'invalid' => $invalid,
    'database' => $stats,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL);
