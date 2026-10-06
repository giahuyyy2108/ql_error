<?php

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This script must be run from CLI.\n");
    exit(1);
}
if ($argc < 2 || !is_file($argv[1])) {
    fwrite(STDERR, "Usage: php scripts/import_danhmuc_thuoc_ndjson.php <rows.ndjson>\n");
    exit(1);
}

$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['SERVER_PORT'] = 80;
require dirname(__DIR__) . '/conf/config.php';
require dirname(__DIR__) . '/web_src/common/mysql.php';

$db = new db_mysql();
$db->connect();
$db->selectdb();
global $connect;
$connect->set_charset('utf8mb4');
$sourceUrl = 'https://baohiemxahoi.gov.vn:4545/File_Server_BHXH/documents/Tan_duoc2021-05-24-03-48-20-PM.pdf';

function decimalValue($value)
{
    $value = trim((string) $value);
    if ($value === '') return null;
    $value = preg_replace('/[^0-9,.-]/u', '', $value);
    if (preg_match('/^-?\d{1,3}(?:\.\d{3})+(?:,\d+)?$/', $value)) {
        $value = str_replace('.', '', $value);
        $value = str_replace(',', '.', $value);
    } elseif (strpos($value, ',') !== false && strpos($value, '.') === false) {
        $value = str_replace(',', '.', $value);
    }
    return is_numeric($value) ? $value : null;
}

function dateValue($value)
{
    $value = trim((string) $value);
    if (!preg_match('/\b(\d{1,2})[\/.-](\d{1,2})[\/.-](\d{4})\b/', $value, $matches)) return null;
    if (!checkdate((int) $matches[2], (int) $matches[1], (int) $matches[3])) return null;
    return sprintf('%04d-%02d-%02d', $matches[3], $matches[2], $matches[1]);
}

$sql = 'INSERT INTO danhmuc_thuoc
    (stt_nguon, ma_hoat_chat, ten_hoat_chat, duong_dung_dang_bao_che, nong_do_ham_luong,
     ten_thuoc, sdk_gpnk, sdk_chuan_hoa, nha_san_xuat, nuoc_san_xuat, quy_cach_dong_goi,
     don_vi_tinh, so_luong, don_gia, thanh_tien, nha_thau_trung_thau, nhom_tieu_chi,
     goi_thau, don_vi_cong_bo, tinh_thanh, so_quyet_dinh, ngay_cong_bo, source_url, source_page)
    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)';
$statement = $connect->prepare($sql);
if (!$statement) throw new RuntimeException($connect->error);

$handle = fopen($argv[1], 'rb');
$count = 0;
$connect->begin_transaction();
try {
    $connect->query('TRUNCATE TABLE danhmuc_thuoc');
    while (($line = fgets($handle)) !== false) {
        $row = json_decode($line, true);
        if (!is_array($row)) continue;
        $values = array(
            (int) $row['stt_nguon'], (string) $row['ma_hoat_chat'], (string) $row['ten_hoat_chat'],
            (string) $row['duong_dung_dang_bao_che'], (string) $row['nong_do_ham_luong'], (string) $row['ten_thuoc'],
            (string) $row['sdk_gpnk'], (string) $row['sdk_chuan_hoa'], (string) $row['nha_san_xuat'],
            (string) $row['nuoc_san_xuat'], (string) $row['quy_cach_dong_goi'], (string) $row['don_vi_tinh'],
            decimalValue($row['so_luong']), decimalValue($row['don_gia']), decimalValue($row['thanh_tien']),
            (string) $row['nha_thau_trung_thau'], (string) $row['nhom_tieu_chi'], (string) $row['goi_thau'],
            (string) $row['don_vi_cong_bo'], (string) $row['tinh_thanh'], (string) $row['so_quyet_dinh'],
            dateValue($row['ngay_cong_bo']), $sourceUrl, (int) $row['source_page']
        );
        $types = 'issssssssssssssssssssssi';
        $statement->bind_param($types, ...$values);
        if (!$statement->execute()) throw new RuntimeException($statement->error);
        $count++;
    }
    // Corrections for five cells which are rendered as hashes/overlapping text
    // in the source PDF and therefore cannot be recovered from text positions.
    $connect->query("UPDATE danhmuc_thuoc SET thanh_tien=so_luong*don_gia WHERE stt_nguon=98");
    $connect->query("UPDATE danhmuc_thuoc SET ma_hoat_chat='40.100', ten_hoat_chat='Deferoxamin', ten_thuoc='Derikad', so_luong=250, don_gia=127000, thanh_tien=31750000 WHERE stt_nguon=3249");
    $connect->query("UPDATE danhmuc_thuoc SET ma_hoat_chat='40.82', ten_hoat_chat='Desloratadine', ten_thuoc='Highercoldz One', so_luong=70000, don_gia=2999, thanh_tien=209930000 WHERE stt_nguon=5104");
    $connect->query("UPDATE danhmuc_thuoc SET ma_hoat_chat='40.798', ten_hoat_chat='Acarbose', ten_thuoc='Savi Acarbose 25', so_luong=80000, don_gia=1800, thanh_tien=144000000 WHERE stt_nguon=5247");
    $connect->query("UPDATE danhmuc_thuoc SET ma_hoat_chat='40.976', ten_hoat_chat='Ipratropium bromide + Fenoterol hydrobromide', ten_thuoc='Berodual', so_luong=4000, don_gia=96870, thanh_tien=387480000 WHERE stt_nguon=6788");
    $connect->query("UPDATE danhmuc_thuoc SET don_vi_cong_bo='BV Phong - Da liễu Trung ương Quy Hòa' WHERE so_quyet_dinh='122/QĐ-TWQH' AND tinh_thanh='Bình Định'");
    $connect->commit();
} catch (Throwable $exception) {
    $connect->rollback();
    throw $exception;
} finally {
    fclose($handle);
    $statement->close();
}

fwrite(STDOUT, "Imported {$count} rows.\n");
