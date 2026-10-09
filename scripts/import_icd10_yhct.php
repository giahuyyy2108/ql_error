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

const ICD10_YHCT_SOURCE = 'https://luatvietnam.vn/y-te/quyet-dinh-2552-qd-byt-cua-bo-y-te-ve-viec-ban-hanh-danh-muc-ma-dung-chung-thuat-ngu-y-hoc-co-truyen-dot-1-408356-d1.html';

function downloadIcd10YhctHtml($url)
{
    $context = stream_context_create(array(
        'http' => array(
            'timeout' => 120,
            'ignore_errors' => false,
            'header' => "User-Agent: ql-error-icd10-yhct-importer/1.0\r\n"
        ),
        'ssl' => array('verify_peer' => true, 'verify_peer_name' => true)
    ));
    $html = @file_get_contents($url, false, $context);
    if ($html === false || $html === '') {
        throw new RuntimeException('Không thể tải Quyết định 2552/QĐ-BYT.');
    }
    return $html;
}

function cleanIcd10YhctText($text)
{
    $text = html_entity_decode((string) $text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = str_replace("\xc2\xa0", ' ', $text);
    return trim(preg_replace('/\s+/u', ' ', $text));
}

function parseIcd10YhctRows($html)
{
    $previous = libxml_use_internal_errors(true);
    libxml_clear_errors();
    $document = new DOMDocument();
    $loaded = $document->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
    libxml_clear_errors();
    libxml_use_internal_errors($previous);
    if (!$loaded) throw new RuntimeException('Không thể phân tích nội dung Quyết định 2552/QĐ-BYT.');

    $targetTable = null;
    foreach ($document->getElementsByTagName('table') as $table) {
        $header = cleanIcd10YhctText($table->textContent);
        if (mb_stripos($header, 'Mã dùng chung', 0, 'UTF-8') !== false
            && mb_stripos($header, 'Mã ICD', 0, 'UTF-8') !== false
            && mb_stripos($header, 'Các thể lâm sàng', 0, 'UTF-8') !== false) {
            $targetTable = $table;
            break;
        }
    }
    if ($targetTable === null) throw new RuntimeException('Không tìm thấy bảng Phụ lục I trong văn bản nguồn.');

    $rows = array();
    $parent = null;
    foreach ($targetTable->getElementsByTagName('tr') as $tr) {
        $cells = array();
        foreach ($tr->childNodes as $child) {
            if ($child instanceof DOMElement && in_array(strtolower($child->tagName), array('td', 'th'), true)) {
                $cells[] = cleanIcd10YhctText($child->textContent);
            }
        }
        if (count($cells) < 8 || !preg_match('/^\d{7}$/', $cells[0])) continue;
        $cells = array_slice(array_pad($cells, 8, ''), 0, 8);
        $isParent = $cells[1] !== '' || $cells[2] !== '' || $cells[3] !== '' || $cells[4] !== '' || $cells[5] !== '';
        if ($isParent) {
            $parent = array(
                'ma_dung_chung' => $cells[0],
                'ten_benh_huong_dan' => $cells[1],
                'ten_benh_y_hoc_hien_dai' => $cells[2],
                'ma_icd10' => $cells[3],
                'benh_danh_yhct' => $cells[4],
                'ma_u' => $cells[5]
            );
        }
        $rows[] = array(
            'ma_dung_chung' => $cells[0],
            'ma_dung_chung_cha' => $isParent || $parent === null ? null : $parent['ma_dung_chung'],
            'ten_benh_huong_dan' => $cells[1] !== '' ? $cells[1] : ($parent ? $parent['ten_benh_huong_dan'] : null),
            'ten_benh_y_hoc_hien_dai' => $cells[2] !== '' ? $cells[2] : ($parent ? $parent['ten_benh_y_hoc_hien_dai'] : null),
            'ma_icd10' => $cells[3] !== '' ? $cells[3] : ($parent ? $parent['ma_icd10'] : null),
            'benh_danh_yhct' => $cells[4] !== '' ? $cells[4] : ($parent ? $parent['benh_danh_yhct'] : null),
            'ma_u' => $cells[5] !== '' ? $cells[5] : ($parent ? $parent['ma_u'] : null),
            'the_lam_sang' => $cells[6] !== '' ? $cells[6] : null,
            'ma_hoa' => $cells[7] !== '' ? $cells[7] : null
        );
    }
    if (!$rows) throw new RuntimeException('Phụ lục I không có dữ liệu hợp lệ.');
    return $rows;
}

$rows = parseIcd10YhctRows(downloadIcd10YhctHtml(ICD10_YHCT_SOURCE));
$db = new db_mysql();
$db->connect();
$db->selectdb();
$connect->set_charset('utf8mb4');
$connect->begin_transaction();

try {
    $statement = $connect->prepare(
        'INSERT INTO `icd10_yhct`
         (`ma_dung_chung`, `ma_dung_chung_cha`, `ten_benh_huong_dan`, `ten_benh_y_hoc_hien_dai`,
          `ma_icd10`, `benh_danh_yhct`, `ma_u`, `the_lam_sang`, `ma_hoa`, `source_url`, `is_active`)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
         ON DUPLICATE KEY UPDATE
          `ma_dung_chung_cha`=VALUES(`ma_dung_chung_cha`),
          `ten_benh_huong_dan`=VALUES(`ten_benh_huong_dan`),
          `ten_benh_y_hoc_hien_dai`=VALUES(`ten_benh_y_hoc_hien_dai`),
          `ma_icd10`=VALUES(`ma_icd10`), `benh_danh_yhct`=VALUES(`benh_danh_yhct`),
          `ma_u`=VALUES(`ma_u`), `the_lam_sang`=VALUES(`the_lam_sang`),
          `ma_hoa`=VALUES(`ma_hoa`), `source_url`=VALUES(`source_url`), `is_active`=1'
    );
    if (!$statement) throw new RuntimeException('Không thể chuẩn bị câu lệnh import ICD-10 YHCT.');
    foreach ($rows as $row) {
        $source = ICD10_YHCT_SOURCE;
        $statement->bind_param(
            'ssssssssss',
            $row['ma_dung_chung'], $row['ma_dung_chung_cha'], $row['ten_benh_huong_dan'],
            $row['ten_benh_y_hoc_hien_dai'], $row['ma_icd10'], $row['benh_danh_yhct'],
            $row['ma_u'], $row['the_lam_sang'], $row['ma_hoa'], $source
        );
        if (!$statement->execute()) {
            throw new RuntimeException('Không thể import mã ' . $row['ma_dung_chung'] . '.');
        }
    }
    $statement->close();
    $connect->commit();
} catch (Throwable $exception) {
    $connect->rollback();
    throw $exception;
}

echo 'Đã import ' . count($rows) . ' dòng ICD-10 YHCT từ Quyết định 2552/QĐ-BYT.' . PHP_EOL;
