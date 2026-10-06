<?php

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This script must be run from CLI.\n");
    exit(1);
}
if ($argc < 2 || !is_file($argv[1])) {
    fwrite(STDERR, "Usage: php scripts/import_danhmuc_thuoc_csv.php <FileDanhMucThuoc.csv>\n");
    exit(1);
}

$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['SERVER_PORT'] = 80;
require dirname(__DIR__) . '/conf/config.php';
require dirname(__DIR__) . '/web_src/common/mysql.php';

function textValue($value)
{
    return trim((string) $value);
}

function firstValue(array $row, array $names)
{
    foreach ($names as $name) {
        if (isset($row[$name]) && textValue($row[$name]) !== '') return textValue($row[$name]);
    }
    return '';
}

function decimalValue($value)
{
    if ($value === null || textValue($value) === '') return null;
    if (is_numeric($value)) return (string) $value;
    $value = preg_replace('/[^0-9,.-]/u', '', textValue($value));
    if (preg_match('/^-?\d{1,3}(?:\.\d{3})+(?:,\d+)?$/', $value)) {
        $value = str_replace('.', '', $value);
        $value = str_replace(',', '.', $value);
    } elseif (strpos($value, ',') !== false && strpos($value, '.') === false) {
        $value = str_replace(',', '.', $value);
    }
    return is_numeric($value) ? $value : null;
}

function compactDate($value)
{
    $value = preg_replace('/\D/', '', textValue($value));
    if (strlen($value) !== 8) return null;
    $year = (int) substr($value, 0, 4);
    $month = (int) substr($value, 4, 2);
    $day = (int) substr($value, 6, 2);
    return checkdate($month, $day, $year) ? sprintf('%04d-%02d-%02d', $year, $month, $day) : null;
}

$handle = fopen($argv[1], 'rb');
if (!$handle) throw new RuntimeException('Cannot open CSV file.');
$headers = fgetcsv($handle);
if (!$headers) throw new RuntimeException('CSV file has no header row.');
$headers[0] = trim(preg_replace('/^\xEF\xBB\xBF/', '', $headers[0]), '"');
$headers = array_map('textValue', $headers);

$required = array('STT', 'TEN_HOAT_CHAT', 'TEN_THUOC', 'SO_DANG_KY');
foreach ($required as $name) {
    if (!in_array($name, $headers, true)) throw new RuntimeException("Missing required column: {$name}");
}

$db = new db_mysql();
$db->connect();
$db->selectdb();
global $connect;
$connect->set_charset('utf8mb4');

$sql = 'INSERT INTO danhmuc_thuoc
    (stt_nguon, ma_hoat_chat, ten_hoat_chat, duong_dung_dang_bao_che, nong_do_ham_luong,
     ten_thuoc, sdk_gpnk, sdk_chuan_hoa, nha_san_xuat, nuoc_san_xuat, quy_cach_dong_goi,
     don_vi_tinh, so_luong, don_gia, thanh_tien, nha_thau_trung_thau, nhom_tieu_chi,
     goi_thau, don_vi_cong_bo, tinh_thanh, so_quyet_dinh, ngay_cong_bo, source_url,
     source_page, is_active)
    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)';
$statement = $connect->prepare($sql);
if (!$statement) throw new RuntimeException($connect->error);

$count = 0;
$skipped = 0;
$connect->begin_transaction();
try {
    $connect->query('DELETE FROM danhmuc_thuoc');
    $connect->query('ALTER TABLE danhmuc_thuoc AUTO_INCREMENT = 1');
    $rowNumber = 1;
    while (($cells = fgetcsv($handle)) !== false) {
        $rowNumber++;
        $cells = array_pad($cells, count($headers), null);
        $cells = array_slice($cells, 0, count($headers));
        $row = array_combine($headers, $cells);
        $drugName = firstValue($row, array('TEN_THUOC_AX', 'TEN_THUOC'));
        $activeIngredient = firstValue($row, array('HOAT_CHAT_AX', 'TEN_HOAT_CHAT'));
        if ($drugName === '' && $activeIngredient === '') {
            $skipped++;
            continue;
        }

        $route = firstValue($row, array('DUONG_DUNG_AX', 'DUONG_DUNG'));
        $dosageForm = firstValue($row, array('DANG_BAO_CHE'));
        $routeAndForm = implode('; ', array_filter(array($route, $dosageForm), 'strlen'));
        $registration = firstValue($row, array('SO_DANG_KY_AX', 'SO_DANG_KY'));
        $quantity = decimalValue($row['SO_LUONG']);
        $price = decimalValue($row['DON_GIA']);
        $amount = ($quantity !== null && $price !== null) ? (string) ((float) $quantity * (float) $price) : null;
        $effective = mb_strtolower(firstValue($row, array('HIEU_LUC')), 'UTF-8');
        $isActive = ($effective === '' || in_array($effective, array('có', 'co', 'true', '1'), true)) ? 1 : 0;
        $values = array(
            (int) $row['STT'], firstValue($row, array('MA_HOAT_CHAT_AX', 'MA_THUOC')), $activeIngredient,
            $routeAndForm, textValue($row['HAM_LUONG']), $drugName, $registration, $registration,
            textValue($row['NHA_SX']), textValue($row['NUOC_SX']), textValue($row['QUY_CACH']),
            textValue($row['DON_VI_TINH']), $quantity, $price, $amount, textValue($row['NHA_THAU']),
            textValue($row['NHOM_THAU']), textValue($row['LOAI_THAU']), textValue($row['MA_CSKCB']), '',
            textValue($row['QUYET_DINH']), compactDate($row['NGAY_NHAN']), 'FileDanhMucThuoc.xlsx', null, $isActive
        );
        $types = 'issssssssssssssssssssssii';
        $statement->bind_param($types, ...$values);
        if (!$statement->execute()) throw new RuntimeException("Row {$rowNumber}: " . $statement->error);
        $count++;
    }
    $connect->commit();
} catch (Throwable $exception) {
    $connect->rollback();
    throw $exception;
} finally {
    $statement->close();
    fclose($handle);
}

fwrite(STDOUT, "Imported {$count} rows; skipped {$skipped} empty rows.\n");
