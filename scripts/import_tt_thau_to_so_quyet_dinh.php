<?php

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Script này chỉ được chạy từ dòng lệnh.\n");
    exit(1);
}

$_SERVER['HTTPS'] = 'off';
$_SERVER['SERVER_PORT'] = 80;
$_SERVER['HTTP_HOST'] = 'localhost';
$connect = '';
require dirname(__DIR__) . '/conf/config.php';
require dirname(__DIR__) . '/web_src/common/mysql.php';

$arguments = array_slice($argv, 1);
$dryRun = in_array('--dry-run', $arguments, true);
$paths = array_values(array_filter($arguments, function ($argument) { return $argument !== '--dry-run'; }));
$path = isset($paths[0]) ? $paths[0]
    : 'C:/Users/ASUS/OneDrive/Máy tính/ĐT_QL_ERROR/TAI LIEU/FileDanhMucThuoc.xlsx';
if (!is_file($path)) throw new RuntimeException('Không tìm thấy file Excel: ' . $path);

function normalizeExcelText($value)
{
    $value = str_replace(array("\r\n", "\r", "\n"), ' ', (string) $value);
    return trim(preg_replace('/[\x{00A0}\s]+/u', ' ', $value));
}

function xlsxColumnName($reference)
{
    return preg_replace('/\d+/', '', (string) $reference);
}

function xlsxCellValue(SimpleXMLElement $cell, array $sharedStrings)
{
    $type = isset($cell['t']) ? (string) $cell['t'] : '';
    if ($type === 'inlineStr') {
        $value = '';
        foreach ($cell->xpath('.//*[local-name()="t"]') ?: array() as $node) $value .= (string) $node;
        return normalizeExcelText($value);
    }
    $value = isset($cell->v) ? (string) $cell->v : '';
    if ($type === 's') {
        $index = (int) $value;
        $value = isset($sharedStrings[$index]) ? $sharedStrings[$index] : '';
    }
    return normalizeExcelText($value);
}

function firstWorksheetPath(ZipArchive $zip)
{
    $workbookXml = $zip->getFromName('xl/workbook.xml');
    $relationsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');
    if ($workbookXml !== false && $relationsXml !== false) {
        $workbook = simplexml_load_string($workbookXml, 'SimpleXMLElement', LIBXML_NONET);
        $relations = simplexml_load_string($relationsXml, 'SimpleXMLElement', LIBXML_NONET);
        if ($workbook && $relations) {
            $sheets = $workbook->xpath('//*[local-name()="sheet"]');
            if ($sheets) {
                $attributes = $sheets[0]->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships');
                $relationshipId = isset($attributes['id']) ? (string) $attributes['id'] : '';
                foreach ($relations->xpath('//*[local-name()="Relationship"]') ?: array() as $relation) {
                    if ((string) $relation['Id'] !== $relationshipId) continue;
                    $target = str_replace('\\', '/', (string) $relation['Target']);
                    return strpos($target, 'xl/') === 0 ? $target : 'xl/' . ltrim($target, '/');
                }
            }
        }
    }
    for ($index = 0; $index < $zip->numFiles; $index++) {
        $name = $zip->getNameIndex($index);
        if (preg_match('#^xl/worksheets/[^/]+\.xml$#', $name)) return $name;
    }
    throw new RuntimeException('Không tìm thấy worksheet trong file XLSX.');
}

function parseTenderInformation($value)
{
    $normalizedValue = normalizeExcelText($value);
    $parts = array_map('normalizeExcelText', explode(';', $normalizedValue));
    if (count($parts) < 4 || $parts[0] === '' || $parts[1] === '' || $parts[2] === ''
        || !preg_match('/^(19|20)\d{2}$/', $parts[3])) return false;
    return array(
        // TT_THAU is validated against this column, so retain the complete
        // Excel representation, including any component after the year.
        'so_quyet_dinh' => $normalizedValue,
        'goi_thau' => $parts[1],
        'nhom_tieu_chi' => $parts[2],
        'nam_thau' => (int) $parts[3]
    );
}

$zip = new ZipArchive();
if ($zip->open($path) !== true) throw new RuntimeException('Không thể mở file XLSX. Hãy đóng file Excel rồi thử lại.');
$sharedStrings = array();
$sharedXml = $zip->getFromName('xl/sharedStrings.xml');
if ($sharedXml !== false) {
    $shared = simplexml_load_string($sharedXml, 'SimpleXMLElement', LIBXML_NONET | LIBXML_NOCDATA);
    foreach ($shared->xpath('//*[local-name()="si"]') ?: array() as $item) {
        $text = '';
        foreach ($item->xpath('.//*[local-name()="t"]') ?: array() as $node) $text .= (string) $node;
        $sharedStrings[] = normalizeExcelText($text);
    }
}
$worksheetPath = firstWorksheetPath($zip);
$sheetXml = $zip->getFromName($worksheetPath);
$zip->close();
if ($sheetXml === false) throw new RuntimeException('Không thể đọc worksheet: ' . $worksheetPath);
$sheet = simplexml_load_string($sheetXml, 'SimpleXMLElement', LIBXML_NONET | LIBXML_NOCDATA);
if ($sheet === false) throw new RuntimeException('Không thể phân tích worksheet.');

$rowsXml = $sheet->xpath('//*[local-name()="sheetData"]/*[local-name()="row"]');
$headers = array();
$rows = array();
$invalid = array();
foreach ($rowsXml ?: array() as $row) {
    $values = array();
    foreach ($row->xpath('./*[local-name()="c"]') ?: array() as $cell) {
        $values[xlsxColumnName((string) $cell['r'])] = xlsxCellValue($cell, $sharedStrings);
    }
    if (!$headers) {
        foreach ($values as $column => $heading) $headers[$column] = strtoupper($heading);
        continue;
    }
    $record = array();
    foreach ($headers as $column => $heading) $record[$heading] = isset($values[$column]) ? $values[$column] : '';
    $stt = isset($record['STT']) ? (int) $record['STT'] : 0;
    $rawTender = isset($record['TT_THAU']) ? $record['TT_THAU'] : '';
    if ($stt <= 0 || $rawTender === '') continue;
    $tender = parseTenderInformation($rawTender);
    if ($tender === false) {
        $invalid[] = 'STT ' . $stt . ': ' . $rawTender;
        continue;
    }
    $rows[$stt] = $tender;
}
if (!$rows) throw new RuntimeException('Không đọc được dữ liệu STT/TT_THAU hợp lệ từ Excel.');

$db = new db_mysql();
$db->connect();
$db->selectdb();
$connect->set_charset('utf8mb4');
$connect->begin_transaction();
try {
    $select = $connect->prepare('SELECT id FROM danhmuc_thuoc WHERE stt_nguon = ? LIMIT 1');
    $update = $connect->prepare(
        'UPDATE danhmuc_thuoc SET so_quyet_dinh = ?, goi_thau = ?, nhom_tieu_chi = ? WHERE stt_nguon = ?'
    );
    if (!$select || !$update) throw new RuntimeException('Không thể chuẩn bị câu lệnh cập nhật danh mục thuốc.');
    $updated = 0;
    $missing = 0;
    foreach ($rows as $stt => $tender) {
        $select->bind_param('i', $stt);
        $select->execute();
        if (!$select->get_result()->fetch_assoc()) { $missing++; continue; }
        $update->bind_param('sssi', $tender['so_quyet_dinh'], $tender['goi_thau'], $tender['nhom_tieu_chi'], $stt);
        if (!$update->execute()) throw new RuntimeException('Không thể cập nhật STT ' . $stt . '.');
        $updated++;
    }
    $select->close();
    $update->close();
    if ($dryRun) $connect->rollback(); else $connect->commit();
} catch (Throwable $exception) {
    $connect->rollback();
    throw $exception;
}

echo ($dryRun ? '[DRY RUN] ' : '') . 'Đã xử lý ' . $updated . ' dòng; thiếu ' . $missing
    . ' dòng trong CSDL; bỏ qua ' . count($invalid) . ' TT_THAU sai định dạng.' . PHP_EOL;
if ($invalid) echo implode(PHP_EOL, array_slice($invalid, 0, 20)) . PHP_EOL;
