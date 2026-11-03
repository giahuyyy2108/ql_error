<?php

/**
 * Khoi phuc file.thongtin_bn cho cac ho so duoc import truoc khi worker
 * luu thong tin benh nhan tu XML1.
 *
 * Chi doc/kiem tra theo mac dinh:
 *   php scripts/backfill_thongtin_bn.php --limit=20
 * Thuc su cap nhat (nen sao luu CSDL truoc):
 *   php scripts/backfill_thongtin_bn.php --apply --limit=5000
 *
 * Khong goi API BHYT, khong thay doi trang thai ho so, validation,
 * file_path hay update_at. Chi cap nhat nhung ban ghi dang NULL.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit(1);
}

$apply = false;
$limit = null;
$afterId = 0;
foreach (array_slice($argv, 1) as $arg) {
    if ($arg === '--apply') {
        $apply = true;
    } elseif ($arg === '--help' || $arg === '-h') {
        echo "Usage: php scripts/backfill_thongtin_bn.php [--limit=20] [--after-id=0] [--apply]\n";
        exit(0);
    } elseif (preg_match('/^--limit=([0-9]+)$/', $arg, $m)) {
        $limit = (int) $m[1];
    } elseif (preg_match('/^--after-id=([0-9]+)$/', $arg, $m)) {
        $afterId = (int) $m[1];
    } else {
        fwrite(STDERR, "Unknown option: $arg\n");
        exit(1);
    }
}

$limit = $limit === null ? ($apply ? 5000 : 20) : $limit;
if ($limit < 1 || $limit > 10000 || $afterId < 0) {
    fwrite(STDERR, "--limit must be 1..10000 and --after-id must be non-negative.\n");
    exit(1);
}

$projectRoot = dirname(__DIR__);
$_SERVER['HTTPS'] = 'off';
$_SERVER['SERVER_PORT'] = 80;
$_SERVER['HTTP_HOST'] = 'localhost';
require $projectRoot . '/conf/config.php';
require $projectRoot . '/web_src/common/mysql.php';
require $projectRoot . '/web_src/bean/FilePeer.php';
require $projectRoot . '/web_src/common/XmlFileDecoder.php';
require $projectRoot . '/web_src/common/FileStorageService.php';

$connect = '';
$filePeer = new FilePeer();
if (!$connect->set_charset('utf8mb4')) {
    throw new RuntimeException('Cannot set database charset to utf8mb4.');
}

$inputRoot = null;
$inputPathResult = $connect->query('SELECT path FROM path WHERE id = 1 LIMIT 1');
if ($inputPathResult) {
    $row = $inputPathResult->fetch_assoc();
    $inputRoot = $row && trim((string) $row['path']) !== '' ? $row['path'] : null;
    $inputPathResult->free();
}
$storage = new FileStorageService(
    $projectRoot,
    $inputRoot,
    $filePeer->getConfiguredKhoaOutputPaths()
);

$read = $connect->prepare(
    'SELECT id, ma_lk, file_path FROM `file`
     WHERE thongtin_bn IS NULL AND id > ? ORDER BY id ASC LIMIT ?'
);
if (!$read) {
    throw new RuntimeException('Cannot prepare read query.');
}
$read->bind_param('ii', $afterId, $limit);
$read->execute();
$result = $read->get_result();
if (!$result) {
    throw new RuntimeException('Cannot read files for backfill.');
}

$update = null;
if ($apply) {
    // Explicitly keep update_at: an automatic timestamp change would move
    // historical records into today's date-filtered list on the UI.
    $update = $connect->prepare(
        'UPDATE `file` SET thongtin_bn = ?, update_at = update_at
         WHERE id = ? AND thongtin_bn IS NULL'
    );
    if (!$update) {
        throw new RuntimeException('Cannot prepare update query.');
    }
}

$fieldMap = array(
    'MA_BN' => 'maBn',
    'HO_TEN' => 'hoTen',
    'NGAY_SINH' => 'ngaySinh',
    'GIOI_TINH' => 'gioiTinh',
    'DIA_CHI' => 'diaChi',
    'MA_DKBD' => 'maDKBD',
    'GT_THE_TU' => 'gtTheTu',
    'GT_THE_DEN' => 'gtTheDen',
    'MA_SO_BHXH' => 'maSoBHXH'
);
$fieldNames = array_merge(array('MA_THE_BHYT'), array_keys($fieldMap));
$stats = array('scanned' => 0, 'ready' => 0, 'updated' => 0,
    'missing' => 0, 'mismatch' => 0, 'invalid' => 0, 'failed' => 0);
$lastId = $afterId;

echo $apply ? "Mode: APPLY (only NULL rows)\n" : "Mode: DRY-RUN (no writes)\n";
while ($row = $result->fetch_assoc()) {
    $id = (int) $row['id'];
    $lastId = $id;
    ++$stats['scanned'];
    try {
        $path = $storage->resolveStoredFilePath($row['file_path']);
    } catch (Throwable $e) {
        ++$stats['missing'];
        continue;
    }

    try {
        $decoded = XmlFileDecoder::decodeDanhSachHoSoPathWithMetadata($path);
        if (XmlFileDecoder::extractMaLk($decoded['content']) !== (string) $row['ma_lk']) {
            ++$stats['mismatch'];
            continue;
        }
        $xml1 = XmlFileDecoder::extractValuesFromFileType($decoded['content'], 'XML1', $fieldNames);
        unset($decoded);
        $info = array('maThe' => trim((string) $xml1['MA_THE_BHYT']));
        foreach ($fieldMap as $xmlField => $targetField) {
            if ($xml1[$xmlField] !== null && trim((string) $xml1[$xmlField]) !== '') {
                $info[$targetField] = trim((string) $xml1[$xmlField]);
            }
        }
        if ($info['maThe'] === '' && !isset($info['maBn']) && !isset($info['hoTen'])) {
            ++$stats['invalid'];
            continue;
        }
        // Backfill does not contact BHYT, so it must not claim lookup success.
        $info['bhytLookupStatus'] = 'not_checked';
        $json = json_encode($info, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            throw new RuntimeException('Unable to encode XML patient information.');
        }
        ++$stats['ready'];
        if ($apply) {
            $update->bind_param('si', $json, $id);
            if (!$update->execute()) {
                throw new RuntimeException('Unable to update patient information.');
            }
            $stats['updated'] += $update->affected_rows;
        }
    } catch (Throwable $e) {
        ++$stats['failed'];
        // Log record ID only, never patient's personal information or XML path.
        fwrite(STDERR, "Skipping file ID $id: " . get_class($e) . "\n");
    }
    if ($stats['scanned'] % 100 === 0) {
        echo "Scanned {$stats['scanned']}; ready {$stats['ready']}; updated {$stats['updated']}\n";
    }
}

$result->free();
$read->close();
if ($update) $update->close();
echo 'Summary: ' . json_encode($stats) . "; last_id=$lastId\n";
if ($stats['scanned'] === $limit) {
    echo "More rows may remain. Re-run with --after-id=$lastId or use a larger --limit.\n";
}
if ($stats['failed'] > 0) exit(2);
