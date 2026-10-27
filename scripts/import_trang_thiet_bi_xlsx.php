<?php

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only.\n");
    exit(1);
}

$xlsx = $argv[1] ?? '';
if ($xlsx === '' || !is_file($xlsx)) {
    fwrite(STDERR, "Usage: php scripts/import_trang_thiet_bi_xlsx.php <FileTrangThietBi.xlsx>\n");
    exit(1);
}

$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['SERVER_PORT'] = 80;
require dirname(__DIR__) . '/conf/config.php';
require dirname(__DIR__) . '/web_src/common/mysql.php';

function ttbText($value): string
{
    return trim((string)($value ?? ''));
}

function ttbDate($value): ?string
{
    $value = preg_replace('/\D/', '', ttbText($value));
    if (strlen($value) !== 8) return null;
    $year = (int)substr($value, 0, 4);
    $month = (int)substr($value, 4, 2);
    $day = (int)substr($value, 6, 2);
    return checkdate($month, $day, $year)
        ? sprintf('%04d-%02d-%02d', $year, $month, $day)
        : null;
}

function ttbYear($value): ?int
{
    $value = ttbText($value);
    return preg_match('/^\d{4}$/', $value) ? (int)$value : null;
}

function ttbXlsxRows(string $path): array
{
    $zip = new ZipArchive();
    if ($zip->open($path) !== true) throw new RuntimeException('Cannot open XLSX file.');

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
    if ($sheetName === null) throw new RuntimeException('Worksheet XML not found.');

    $sheetXml = $zip->getFromName($sheetName);
    if ($sheetXml === false || $sheetXml === '') throw new RuntimeException('Cannot read worksheet XML.');

    $dom = new DOMDocument();
    $dom->loadXML($sheetXml);
    $xp = new DOMXPath($dom);
    $xp->registerNamespace('x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');

    $rows = [];
    foreach ($xp->query('//x:sheetData/x:row') as $rowNode) {
        $cells = [];
        foreach ($xp->query('./x:c', $rowNode) as $cell) {
            $ref = $cell->getAttribute('r');
            if (!preg_match('/^([A-Z]+)/', $ref, $m)) continue;
            $col = $m[1];
            $type = $cell->getAttribute('t');
            if ($type === 'inlineStr') {
                $parts = [];
                foreach ($xp->query('.//x:t', $cell) as $t) $parts[] = $t->textContent;
                $value = implode('', $parts);
            } else {
                $v = $xp->query('./x:v', $cell)->item(0);
                $raw = $v ? $v->textContent : '';
                $value = ($type === 's' && $raw !== '') ? ($shared[(int)$raw] ?? '') : $raw;
            }
            $cells[$col] = $value;
        }
        $rows[] = $cells;
    }
    $zip->close();
    return $rows;
}

$rows = ttbXlsxRows($xlsx);
if (!$rows) throw new RuntimeException('Workbook has no rows.');

$headerCells = array_shift($rows);
$headers = [];
foreach ($headerCells as $col => $header) {
    $header = mb_strtoupper(ttbText($header), 'UTF-8');
    if ($header !== '') $headers[$col] = $header;
}

foreach (['TEN_TB', 'ID'] as $required) {
    if (!in_array($required, $headers, true)) {
        throw new RuntimeException("Missing required column: {$required}");
    }
}

$data = [];
foreach ($rows as $cells) {
    $row = [];
    foreach ($headers as $col => $name) $row[$name] = $cells[$col] ?? '';
    if (ttbText($row['TEN_TB'] ?? '') === '' && ttbText($row['ID'] ?? '') === '') continue;
    $data[] = $row;
}

$db = new db_mysql();
$db->connect();
$db->selectdb();
global $connect;
$connect->set_charset('utf8mb4');

$existing = [];
$result = $connect->query('SELECT source_id FROM trang_thiet_bi');
if (!$result) throw new RuntimeException($connect->error);
while ($item = $result->fetch_assoc()) $existing[(string)$item['source_id']] = true;

$sql = 'INSERT INTO trang_thiet_bi (
    stt, ten_tb, ky_hieu, congty_sx, nuoc_sx, nam_sx, nam_sd, ma_may,
    so_luu_hanh, hd_tu, hd_den, tu_ngay, den_ngay, source_id, source_file
) VALUES (' . implode(',', array_fill(0, 15, '?')) . ')';
$stmt = $connect->prepare($sql);
if (!$stmt) throw new RuntimeException($connect->error);

$inserted = 0;
$skippedExisting = 0;
$skippedInvalid = 0;
$connect->begin_transaction();
try {
    foreach ($data as $row) {
        $sourceId = ttbText($row['ID'] ?? '');
        $tenTb = ttbText($row['TEN_TB'] ?? '');
        if ($sourceId === '' || !ctype_digit($sourceId) || $tenTb === '') {
            $skippedInvalid++;
            continue;
        }
        if (isset($existing[$sourceId])) {
            $skippedExisting++;
            continue;
        }

        $values = [
            ttbText($row['STT'] ?? '') === '' ? null : (int)$row['STT'],
            $tenTb,
            ttbText($row['KY_HIEU'] ?? ''),
            ttbText($row['CONGTY_SX'] ?? ''),
            ttbText($row['NUOC_SX'] ?? ''),
            ttbYear($row['NAM_SX'] ?? null),
            ttbYear($row['NAM_SD'] ?? null),
            ttbText($row['MA_MAY'] ?? ''),
            ttbText($row['SO_LUU_HANH'] ?? ''),
            ttbDate($row['HD_TU'] ?? null),
            ttbDate($row['HD_DEN'] ?? null),
            ttbDate($row['TU_NGAY'] ?? null),
            ttbDate($row['DEN_NGAY'] ?? null),
            $sourceId,
            basename($xlsx),
        ];
        $types = str_repeat('s', count($values));
        $stmt->bind_param($types, ...$values);
        if (!$stmt->execute()) throw new RuntimeException("source_id {$sourceId}: " . $stmt->error);
        $existing[$sourceId] = true;
        $inserted++;
    }
    $connect->commit();
} catch (Throwable $e) {
    $connect->rollback();
    throw $e;
} finally {
    $stmt->close();
}

$total = (int)$connect->query('SELECT COUNT(*) AS c FROM trang_thiet_bi')->fetch_assoc()['c'];
fwrite(STDOUT, json_encode([
    'xlsx_rows' => count($data),
    'inserted' => $inserted,
    'skipped_existing' => $skippedExisting,
    'skipped_invalid' => $skippedInvalid,
    'table_rows' => $total,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL);

