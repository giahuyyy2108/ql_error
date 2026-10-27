<?php

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only.\n");
    exit(1);
}

$xlsx = $argv[1] ?? '';
if ($xlsx === '' || !is_file($xlsx)) {
    fwrite(STDERR, "Usage: php scripts/import_vattuyte_xlsx.php <FileVatTuYTe.xlsx>\n");
    exit(1);
}

$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['SERVER_PORT'] = 80;
require dirname(__DIR__) . '/conf/config.php';
require dirname(__DIR__) . '/web_src/common/mysql.php';

function vtytText($value): string
{
    return trim((string)($value ?? ''));
}

function vtytDecimal($value): ?string
{
    $value = vtytText($value);
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

function vtytDate($value): ?string
{
    $value = preg_replace('/\D/', '', vtytText($value));
    if (strlen($value) !== 8) return null;
    $year = (int)substr($value, 0, 4);
    $month = (int)substr($value, 4, 2);
    $day = (int)substr($value, 6, 2);
    return checkdate($month, $day, $year) ? sprintf('%04d-%02d-%02d', $year, $month, $day) : null;
}

function vtytDateTime($value): ?string
{
    $value = preg_replace('/\D/', '', vtytText($value));
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

function vtytXlsxRows(string $path): array
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

$rows = vtytXlsxRows($xlsx);
if (!$rows) throw new RuntimeException('Workbook has no rows.');
$headerCells = array_shift($rows);
$headers = [];
foreach ($headerCells as $col => $header) {
    $header = mb_strtoupper(vtytText($header), 'UTF-8');
    if ($header !== '') $headers[$col] = $header;
}

$required = ['MA_VAT_TU', 'TEN_VAT_TU', 'ID'];
foreach ($required as $name) {
    if (!in_array($name, $headers, true)) throw new RuntimeException("Missing required column: {$name}");
}

$data = [];
foreach ($rows as $cells) {
    $row = [];
    foreach ($headers as $col => $name) $row[$name] = $cells[$col] ?? '';
    if (vtytText($row['MA_VAT_TU'] ?? '') === '' && vtytText($row['TEN_VAT_TU'] ?? '') === '') continue;
    $data[] = $row;
}

$db = new db_mysql();
$db->connect();
$db->selectdb();
global $connect;
$connect->set_charset('utf8mb4');

$existing = [];
$result = $connect->query('SELECT source_id FROM vattuyte');
while ($item = $result->fetch_assoc()) $existing[(string)$item['source_id']] = true;

$sql = 'INSERT INTO vattuyte (
    stt, ma_vat_tu, nhom_vat_tu, ten_vat_tu, ma_hieu, quy_cach, hang_sx, nuoc_sx,
    don_vi_tinh, don_gia, don_gia_bh, tyle_tt_bh, so_luong, dinh_muc, nha_thau,
    tt_thau, tu_ngay, den_ngay_hd, ma_cskcb, loai_thau, ht_thau, denngay, tyle,
    quyet_dinh, ma_nhom_5937, don_vi_dau_thau, ma_vtyt_bv, ten_vtyt_bv, hieu_luc,
    ly_do_tu_choi, ngay_nhan, ngay_gui, so_luu_hanh, tinh_nang_kt, ma_cskcb_tbyt,
    ma_cskcb_dau_thau, trang_thai_gui, ket_qua_giam_dinh, source_id, source_file
) VALUES (' . implode(',', array_fill(0, 40, '?')) . ')';
$stmt = $connect->prepare($sql);
if (!$stmt) throw new RuntimeException($connect->error);

$inserted = 0;
$skippedExisting = 0;
$skippedInvalid = 0;
$connect->begin_transaction();
try {
    foreach ($data as $row) {
        $sourceId = vtytText($row['ID'] ?? '');
        $maVatTu = vtytText($row['MA_VAT_TU'] ?? '');
        $tenVatTu = vtytText($row['TEN_VAT_TU'] ?? '');
        if ($sourceId === '' || !ctype_digit($sourceId) || $maVatTu === '' || $tenVatTu === '') {
            $skippedInvalid++;
            continue;
        }
        if (isset($existing[$sourceId])) {
            $skippedExisting++;
            continue;
        }

        $values = [
            vtytText($row['STT'] ?? '') === '' ? null : (int)$row['STT'],
            $maVatTu,
            vtytText($row['NHOM_VAT_TU'] ?? ''),
            $tenVatTu,
            vtytText($row['MA_HIEU'] ?? ''),
            vtytText($row['QUY_CACH'] ?? ''),
            vtytText($row['HANG_SX'] ?? ''),
            vtytText($row['NUOC_SX'] ?? ''),
            vtytText($row['DON_VI_TINH'] ?? ''),
            vtytDecimal($row['DON_GIA'] ?? null),
            vtytDecimal($row['DON_GIA_BH'] ?? null),
            vtytDecimal($row['TYLE_TT_BH'] ?? null),
            vtytDecimal($row['SO_LUONG'] ?? null),
            vtytDecimal($row['DINH_MUC'] ?? null),
            vtytText($row['NHA_THAU'] ?? ''),
            vtytText($row['TT_THAU'] ?? ''),
            vtytDate($row['TU_NGAY'] ?? null),
            vtytDate($row['DEN_NGAY_HD'] ?? null),
            vtytText($row['MA_CSKCB'] ?? ''),
            vtytText($row['LOAI_THAU'] ?? ''),
            vtytText($row['HT_THAU'] ?? ''),
            vtytDate($row['DENNGAY'] ?? null),
            vtytDecimal($row['TYLE'] ?? null),
            vtytText($row['QUYET_DINH'] ?? ''),
            vtytText($row['MA_NHOM_5937'] ?? ''),
            vtytText($row['DON_VI_DAU_THAU'] ?? ''),
            vtytText($row['MA_VTYT_BV'] ?? ''),
            vtytText($row['TEN_VTYT_BV'] ?? ''),
            vtytText($row['HIEU_LUC'] ?? ''),
            vtytText($row['LY_DO_TU_CHOI'] ?? ''),
            vtytDate($row['NGAY_NHAN'] ?? null),
            vtytDateTime($row['NGAY_GUI'] ?? null),
            vtytText($row['SO_LUU_HANH'] ?? ''),
            vtytText($row['TINH_NANG_KT'] ?? ''),
            vtytText($row['MA_CSKCB_TBYT'] ?? ''),
            vtytText($row['MA_CSKCB_DAU_THAU'] ?? ''),
            vtytText($row['TRANG_THAI_GUI'] ?? ''),
            vtytText($row['KET_QUA_GIAM_DINH'] ?? ''),
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

$total = (int)$connect->query('SELECT COUNT(*) AS c FROM vattuyte')->fetch_assoc()['c'];
fwrite(STDOUT, json_encode([
    'xlsx_rows' => count($data),
    'inserted' => $inserted,
    'skipped_existing' => $skippedExisting,
    'skipped_invalid' => $skippedInvalid,
    'table_rows' => $total,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL);

