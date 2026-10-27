<?php

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only.\n");
    exit(1);
}

$xlsx = $argv[1] ?? '';
if ($xlsx === '' || !is_file($xlsx)) {
    fwrite(STDERR, "Usage: php scripts/import_dichvu_bv_xlsx.php <FileDichVuBV.xlsx>\n");
    exit(1);
}

$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['SERVER_PORT'] = 80;
require dirname(__DIR__) . '/conf/config.php';
require dirname(__DIR__) . '/web_src/common/mysql.php';

function dvText($value): string
{
    return trim((string)($value ?? ''));
}

function dvDecimal($value): ?string
{
    $value = dvText($value);
    if ($value === '') return null;
    if (is_numeric($value)) return $value;
    $normalized = preg_replace('/[^0-9,.-]/u', '', $value);
    if (preg_match('/^-?\d{1,3}(?:\.\d{3})+(?:,\d+)?$/', $normalized)) {
        $normalized = str_replace('.', '', $normalized);
        $normalized = str_replace(',', '.', $normalized);
    } elseif (str_contains($normalized, ',') && !str_contains($normalized, '.')) {
        $normalized = str_replace(',', '.', $normalized);
    }
    return is_numeric($normalized) ? $normalized : null;
}

function dvDate($value): ?string
{
    $value = preg_replace('/\D/', '', dvText($value));
    if (strlen($value) !== 8) return null;
    $year = (int)substr($value, 0, 4);
    $month = (int)substr($value, 4, 2);
    $day = (int)substr($value, 6, 2);
    return checkdate($month, $day, $year) ? sprintf('%04d-%02d-%02d', $year, $month, $day) : null;
}

function dvDateTime($value): ?string
{
    $value = preg_replace('/\D/', '', dvText($value));
    if (strlen($value) !== 12 && strlen($value) !== 14) return null;
    $year = (int)substr($value, 0, 4);
    $month = (int)substr($value, 4, 2);
    $day = (int)substr($value, 6, 2);
    $hour = (int)substr($value, 8, 2);
    $minute = (int)substr($value, 10, 2);
    $second = strlen($value) === 14 ? (int)substr($value, 12, 2) : 0;
    if (!checkdate($month, $day, $year) || $hour > 23 || $minute > 59 || $second > 59) return null;
    return sprintf('%04d-%02d-%02d %02d:%02d:%02d', $year, $month, $day, $hour, $minute, $second);
}

function dvXlsxRows(string $path): array
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

$rows = dvXlsxRows($xlsx);
if (!$rows) throw new RuntimeException('Workbook has no rows.');
$headerCells = array_shift($rows);
$headers = [];
foreach ($headerCells as $col => $header) {
    $header = mb_strtoupper(dvText($header), 'UTF-8');
    if ($header !== '') $headers[$col] = $header;
}

foreach (['MA_TUONG_DUONG', 'TEN_DVKT_PHEDUYET', 'ID'] as $required) {
    if (!in_array($required, $headers, true)) throw new RuntimeException("Missing required column: {$required}");
}

$data = [];
foreach ($rows as $cells) {
    $row = [];
    foreach ($headers as $col => $name) $row[$name] = $cells[$col] ?? '';
    if (dvText($row['MA_TUONG_DUONG'] ?? '') === '' && dvText($row['TEN_DVKT_PHEDUYET'] ?? '') === '') continue;
    $data[] = $row;
}

$db = new db_mysql();
$db->connect();
$db->selectdb();
global $connect;
$connect->set_charset('utf8mb4');

$existing = [];
$result = $connect->query('SELECT source_id FROM dichvu_bv');
while ($item = $result->fetch_assoc()) $existing[(string)$item['source_id']] = true;

$sql = 'INSERT INTO dichvu_bv (
    stt, ma_tuong_duong, ten_dvkt_pheduyet, ten_dvkt_gia, phan_loai_pttt, don_gia,
    ghichu, quyet_dinh, tu_ngay, den_ngay, cskcb_cgkt, cskcb_cls, ngay_nhan,
    ma, ten, gia, gia_dvkt, so_quyet_dinh, ma_nhom_5937, hieu_luc, ly_do_tu_choi,
    ngay_gui, trang_thai_gui, ket_qua_giam_dinh, source_id, source_file
) VALUES (' . implode(',', array_fill(0, 26, '?')) . ')';
$stmt = $connect->prepare($sql);
if (!$stmt) throw new RuntimeException($connect->error);

$inserted = 0;
$skippedExisting = 0;
$skippedInvalid = 0;
$connect->begin_transaction();
try {
    foreach ($data as $row) {
        $sourceId = dvText($row['ID'] ?? '');
        $maTuongDuong = dvText($row['MA_TUONG_DUONG'] ?? '');
        $tenPheDuyet = dvText($row['TEN_DVKT_PHEDUYET'] ?? '');
        if ($sourceId === '' || !ctype_digit($sourceId) || $maTuongDuong === '' || $tenPheDuyet === '') {
            $skippedInvalid++;
            continue;
        }
        if (isset($existing[$sourceId])) {
            $skippedExisting++;
            continue;
        }

        $values = [
            dvText($row['STT'] ?? '') === '' ? null : (int)$row['STT'],
            $maTuongDuong,
            $tenPheDuyet,
            dvText($row['TEN_DVKT_GIA'] ?? ''),
            dvText($row['PHAN_LOAI_PTTT'] ?? ''),
            dvDecimal($row['DON_GIA'] ?? null),
            dvText($row['GHICHU'] ?? ''),
            dvText($row['QUYET_DINH'] ?? ''),
            dvDate($row['TUNGAY'] ?? null),
            dvDate($row['DENNGAY'] ?? null),
            dvText($row['CSKCB_CGKT'] ?? ''),
            dvText($row['CSKCB_CLS'] ?? ''),
            dvDate($row['NGAY_NHAN'] ?? null),
            dvText($row['MA'] ?? ''),
            dvText($row['TEN'] ?? ''),
            dvDecimal($row['GIA'] ?? null),
            dvDecimal($row['GIA_DVKT'] ?? null),
            dvText($row['SO_QUYET_DINH'] ?? ''),
            dvText($row['MA_NHOM_5937'] ?? ''),
            dvText($row['HIEU_LUC'] ?? ''),
            dvText($row['LY_DO_TU_CHOI'] ?? ''),
            dvDateTime($row['NGAY_GUI'] ?? null),
            dvText($row['TRANG_THAI_GUI'] ?? ''),
            dvText($row['KET_QUA_GIAM_DINH'] ?? ''),
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

$total = (int)$connect->query('SELECT COUNT(*) AS c FROM dichvu_bv')->fetch_assoc()['c'];
fwrite(STDOUT, json_encode([
    'xlsx_rows' => count($data),
    'inserted' => $inserted,
    'skipped_existing' => $skippedExisting,
    'skipped_invalid' => $skippedInvalid,
    'table_rows' => $total,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL);

