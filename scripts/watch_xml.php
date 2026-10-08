<?php

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Script này chỉ được chạy từ dòng lệnh.\n");
    exit(1);
}

date_default_timezone_set('Asia/Saigon');

$options = array(
    'once' => false,
    'interval' => 1,
    'settle' => 2,
    'retention_days' => 30,
    'dir' => dirname(__DIR__) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'xml'
);

foreach (array_slice($argv, 1) as $argument) {
    if ($argument === '--once') {
        $options['once'] = true;
    } elseif ($argument === '--help' || $argument === '-h') {
        echo "Theo dõi thư mục và tự động nhập file XML.\n\n";
        echo "Cách dùng:\n";
        echo "  php scripts/watch_xml.php [--once] [--interval=2] [--settle=3] [--retention-days=30] [--dir=PATH]\n\n";
        echo "  --once       Quét một lần rồi thoát.\n";
        echo "  --interval   Số giây nghỉ giữa hai lần quét.\n";
        echo "  --settle     File phải ổn định bao nhiêu giây trước khi xử lý.\n";
        echo "  --retention-days  Số ngày giữ file trong deleted.\n";
        echo "  --dir        Thư mục nhận file; mặc định là storage/xml.\n";
        exit(0);
    } elseif (strpos($argument, '--interval=') === 0) {
        $options['interval'] = max(1, (int) substr($argument, 11));
    } elseif (strpos($argument, '--settle=') === 0) {
        $options['settle'] = max(1, (int) substr($argument, 9));
    } elseif (strpos($argument, '--retention-days=') === 0) {
        $options['retention_days'] = max(1, (int) substr($argument, 17));
    } elseif (strpos($argument, '--dir=') === 0) {
        $options['dir'] = substr($argument, 6);
    } else {
        fwrite(STDERR, "Tham số không hợp lệ: " . $argument . "\n");
        exit(1);
    }
}

$projectRoot = dirname(__DIR__);
$inboxDir = rtrim($options['dir'], "\\/");
if ($inboxDir === '') {
    fwrite(STDERR, "Thư mục theo dõi không hợp lệ.\n");
    exit(1);
}

$processingDir = $inboxDir . DIRECTORY_SEPARATOR . 'processing';
$processedDir = $inboxDir . DIRECTORY_SEPARATOR . 'processed';
$failedDir = $inboxDir . DIRECTORY_SEPARATOR . 'failed';
$deletedDir = $inboxDir . DIRECTORY_SEPARATOR . 'deleted';
foreach (array($inboxDir, $processingDir, $processedDir, $failedDir, $deletedDir) as $directory) {
    if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
        fwrite(STDERR, "Không thể tạo thư mục: " . $directory . "\n");
        exit(1);
    }
}

$lockHandle = fopen($inboxDir . DIRECTORY_SEPARATOR . '.watch_xml.lock', 'c');
if ($lockHandle === false || !flock($lockHandle, LOCK_EX | LOCK_NB)) {
    fwrite(STDERR, "Đã có một XML watcher khác đang chạy cho thư mục này.\n");
    exit(1);
}

// config.php expects these web server values even when loaded by a CLI worker.
$_SERVER['HTTPS'] = 'off';
$_SERVER['SERVER_PORT'] = 80;
$_SERVER['HTTP_HOST'] = 'localhost';
$connect = '';
require $projectRoot . '/conf/config.php';
require $projectRoot . '/web_src/common/mysql.php';
require $projectRoot . '/web_src/bean/FilePeer.php';
require $projectRoot . '/web_src/common/XmlFileImportService.php';
require $projectRoot . '/web_src/common/XmlRulesValidator.php';
require $projectRoot . '/web_src/bean/XmlValidationRulePeer.php';
require $projectRoot . '/web_src/bean/ApiValidationConfigPeer.php';
require $projectRoot . '/web_src/common/ApiValidationService.php';
require $projectRoot . '/web_src/common/TableLookupService.php';
require $projectRoot . '/web_src/common/XmlErrorArchiveOrganizer.php';

$filePeer = null;
$validationCallback = null;
$buildImportService = function () use (&$filePeer, &$validationCallback) {
    $filePeer = new FilePeer();
    $rulePeer = new XmlValidationRulePeer();
    $apiService = new ApiValidationService(new ApiValidationConfigPeer());
    $tableLookupService = new TableLookupService();
    $validationCallback = function (array $decodedContent) use ($rulePeer, $apiService, $tableLookupService) {
        return XmlRulesValidator::validate(
            $decodedContent,
            $rulePeer->getRulesForValidation(),
            $apiService,
            $tableLookupService
        );
    };
    return new XmlFileImportService($filePeer, 5242880, $validationCallback);
};
$importService = $buildImportService();
$observed = array();

$workerLogPath = $inboxDir . DIRECTORY_SEPARATOR . 'watcher.log';
$workerErrorLogPath = $inboxDir . DIRECTORY_SEPARATOR . 'watcher.error.log';
$log = function ($message, $level = 'INFO') use ($workerLogPath, $workerErrorLogPath) {
    $level = strtoupper((string) $level);
    $line = '[' . date('Y-m-d H:i:s') . '] [' . $level . '] ' . $message . PHP_EOL;
    echo $line;
    file_put_contents($workerLogPath, $line, FILE_APPEND | LOCK_EX);
    if ($level === 'ERROR') {
        file_put_contents($workerErrorLogPath, $line, FILE_APPEND | LOCK_EX);
    }
};

$logValidationErrors = function ($fileName, array $validation) use ($log) {
    $groups = array();
    foreach ($validation as $validationItem) {
        $fileType = isset($validationItem['file_type']) ? (string) $validationItem['file_type'] : 'XML';
        $errors = isset($validationItem['errors']) && is_array($validationItem['errors'])
            ? $validationItem['errors'] : array();
        foreach ($errors as $error) {
            if (isset($error['severity']) && $error['severity'] === 'warning') continue;
            $ruleId = isset($error['rule_id']) ? (string) $error['rule_id'] : '-';
            $ruleType = isset($error['rule_type']) ? (string) $error['rule_type'] : 'UNKNOWN';
            $ruleValue = isset($error['rule_value']) ? (string) $error['rule_value'] : '';
            $fieldName = isset($error['field_name']) ? (string) $error['field_name'] : '';
            $message = isset($error['message']) ? (string) $error['message'] : 'Lỗi validation';
            $key = $fileType . "\0" . $ruleId . "\0" . $ruleType . "\0" . $ruleValue
                . "\0" . $fieldName . "\0" . $message;
            if (!isset($groups[$key])) {
                $groups[$key] = array(
                    'file_type' => $fileType,
                    'rule_id' => $ruleId,
                    'rule_type' => $ruleType,
                    'rule_value' => $ruleValue,
                    'field_name' => $fieldName,
                    'message' => $message,
                    'count' => 0,
                    'values' => array()
                );
            }
            $groups[$key]['count']++;
            if (array_key_exists('value', $error) && $error['value'] !== null
                && count($groups[$key]['values']) < 3) {
                $actualValue = preg_replace('/\s+/', ' ', (string) $error['value']);
                if (!in_array($actualValue, $groups[$key]['values'], true)) {
                    $groups[$key]['values'][] = $actualValue;
                }
            }
        }
    }
    foreach ($groups as $group) {
        $log(
            'VALIDATION file=' . $fileName
            . ' xml=' . $group['file_type']
            . ' rule_id=' . $group['rule_id']
            . ' ma_loi=' . $group['rule_type']
            . ' rule_value=' . ($group['rule_value'] !== '' ? $group['rule_value'] : '(trống)')
            . ' truong=' . ($group['field_name'] !== '' ? $group['field_name'] : '-')
            . ' so_lan=' . $group['count']
            . ' values=' . (!empty($group['values']) ? implode(' | ', $group['values']) : '(trống)')
            . ' noi_dung=' . preg_replace('/\s+/', ' ', $group['message']),
            'WARNING'
        );
    }
};

$lastCleanup = 0;
$cleanupDeleted = function () use ($deletedDir, $options, $log, &$lastCleanup) {
    $now = time();
    if ($lastCleanup > 0 && ($now - $lastCleanup) < 3600) {
        return;
    }
    $lastCleanup = $now;
    $cutoff = $now - ((int) $options['retention_days'] * 86400);
    $deleted = 0;
    $iterator = new DirectoryIterator($deletedDir);
    foreach ($iterator as $item) {
        if (!$item->isFile() || $item->getMTime() > $cutoff) {
            continue;
        }
        if (@unlink($item->getPathname())) {
            $deleted++;
        }
    }
    if ($deleted > 0) {
        $log('Đã xóa vĩnh viễn ' . $deleted . ' file lưu quá ' . $options['retention_days'] . ' ngày.');
    }
};

$xmlFiles = function ($directory) {
    $files = array();
    $iterator = new DirectoryIterator($directory);
    foreach ($iterator as $item) {
        if ($item->isFile() && strtolower($item->getExtension()) === 'xml') {
            $files[] = $item->getPathname();
        }
    }
    sort($files, SORT_STRING);
    return $files;
};

$uniqueDestination = function ($directory, $fileName) {
    $destination = $directory . DIRECTORY_SEPARATOR . $fileName;
    if (!file_exists($destination)) {
        return $destination;
    }
    $base = pathinfo($fileName, PATHINFO_FILENAME);
    $extension = pathinfo($fileName, PATHINFO_EXTENSION);
    $suffix = date('Ymd_His') . '_' . substr(uniqid('', true), -6);
    return $directory . DIRECTORY_SEPARATOR . $base . '_' . $suffix
        . ($extension !== '' ? '.' . $extension : '');
};

$archive = function ($source, $directory) use ($uniqueDestination) {
    $destination = $uniqueDestination($directory, basename($source));
    if (!rename($source, $destination)) {
        throw new RuntimeException('Không thể chuyển file sang ' . $directory . '.');
    }
    return $destination;
};

$resetDatabase = function () use (&$importService, $buildImportService) {
    global $connect;
    if ($connect instanceof mysqli) {
        try {
            $connect->close();
        } catch (Throwable $ignored) {
        }
    }
    $connect = '';
    $importService = $buildImportService();
};

$process = function ($path) use (
    &$importService,
    &$filePeer,
    $projectRoot,
    $processedDir,
    $failedDir,
    $deletedDir,
    $uniqueDestination,
    $archive,
    $log,
    $logValidationErrors,
    $resetDatabase
) {
    $processedPath = $uniqueDestination($processedDir, basename($path));
    $normalizedRoot = str_replace('\\', '/', rtrim($projectRoot, '\\/')) . '/';
    $normalizedProcessedPath = str_replace('\\', '/', $processedPath);
    $storedPath = strpos($normalizedProcessedPath, $normalizedRoot) === 0
        ? substr($normalizedProcessedPath, strlen($normalizedRoot))
        : $normalizedProcessedPath;
    try {
        $result = $importService->importPath($path, $storedPath);
    } catch (Throwable $exception) {
        try {
            $failedPath = $archive($path, $failedDir);
            file_put_contents($failedPath . '.error.txt', $exception->getMessage() . PHP_EOL, LOCK_EX);
        } catch (Throwable $archiveException) {
            $log('Không thể lưu file lỗi ' . basename($path) . ': ' . $archiveException->getMessage(), 'ERROR');
        }
        $log('File lỗi ' . basename($path) . ': ' . $exception->getMessage(), 'ERROR');
        try {
            $resetDatabase();
        } catch (Throwable $resetException) {
            $log('Không thể kết nối lại database: ' . $resetException->getMessage(), 'ERROR');
        }
        return;
    }

    try {
        $isImport = in_array($result['status'], array('imported', 'reimported'), true);
        $validationFailed = $isImport
            && isset($result['processing_status']) && $result['processing_status'] === 'failed';
        $finalPath = $validationFailed
            ? $uniqueDestination($failedDir, basename($path))
            : $processedPath;
        if (!rename($path, $finalPath)) {
            throw new RuntimeException('Không thể chuyển file sang thư mục kết quả.');
        }
        if ($isImport) {
            $normalizedFinalPath = str_replace('\\', '/', $finalPath);
            $finalStoredPath = strpos($normalizedFinalPath, $normalizedRoot) === 0
                ? substr($normalizedFinalPath, strlen($normalizedRoot))
                : $normalizedFinalPath;
            $filePeer->updateStorageLocation(
                $result['id'],
                $finalStoredPath,
                $validationFailed ? 'failed' : 'processed'
            );
        }
        if ($result['status'] === 'reimported' && !empty($result['old_file_path'])) {
            $oldPath = preg_match('/^[A-Za-z]:[\\\\\/]/', $result['old_file_path']) === 1
                ? $result['old_file_path']
                : $projectRoot . DIRECTORY_SEPARATOR
                    . str_replace('/', DIRECTORY_SEPARATOR, $result['old_file_path']);
            $resolvedOldPath = realpath($oldPath);
            if ($resolvedOldPath !== false && is_file($resolvedOldPath)
                && strcasecmp($resolvedOldPath, $finalPath) !== 0) {
                $deletedPath = $uniqueDestination($deletedDir, basename($resolvedOldPath));
                if (rename($resolvedOldPath, $deletedPath)) {
                    touch($deletedPath);
                    $oldErrorPath = $resolvedOldPath . '.error.txt';
                    if (is_file($oldErrorPath)) {
                        $deletedErrorPath = $deletedPath . '.error.txt';
                        if (rename($oldErrorPath, $deletedErrorPath)) {
                            touch($deletedErrorPath);
                        }
                    }
                }
            }
        }
        if ($validationFailed) {
            $errorCount = 0;
            foreach ($result['validation'] as $validationItem) {
                $errorCount += isset($validationItem['error_count']) ? (int) $validationItem['error_count'] : 0;
            }
            file_put_contents(
                $finalPath . '.error.txt',
                'Validation failed with ' . $errorCount . ' error(s).' . PHP_EOL,
                LOCK_EX
            );
            $organizedPaths = XmlErrorArchiveOrganizer::organize(
                $finalPath,
                $failedDir,
                $result['validation'],
                true,
                $result['name']
            );
            if (!empty($organizedPaths)) {
                $normalizedOrganizedPath = str_replace('\\', '/', $organizedPaths[0]);
                $organizedStoredPath = strpos($normalizedOrganizedPath, $normalizedRoot) === 0
                    ? substr($normalizedOrganizedPath, strlen($normalizedRoot)) : $normalizedOrganizedPath;
                $filePeer->updateStorageLocation($result['id'], $organizedStoredPath, 'failed');
            }
            $log('File không đạt validation (' . $errorCount . ' lỗi): ' . $result['name'], 'WARNING');
            $logValidationErrors($result['name'], $result['validation']);
            return;
        }
        $staleErrorPath = $failedDir . DIRECTORY_SEPARATOR . $result['name'] . '.error.txt';
        if (is_file($staleErrorPath)) {
            unlink($staleErrorPath);
        }
        if ($result['status'] === 'reimported') {
            $log('Đã import và quét lại MA_LK ' . $result['ma_lk'] . ': ' . $result['name']);
        } else {
            $log('Đã nhập ' . $result['name'] . ' (MA_LK ' . $result['ma_lk'] . ').');
        }
    } catch (Throwable $exception) {
        // Keep the imported file in processing. On restart it is detected as a
        // duplicate and archiving is retried without inserting another record.
        $log('Đã nhập dữ liệu nhưng chưa thể lưu trữ file ' . basename($path) . ': ' . $exception->getMessage(), 'ERROR');
    }
};

$revalidatePending = function () use (
    &$filePeer,
    &$validationCallback,
    $projectRoot,
    $inboxDir,
    $processedDir,
    $failedDir,
    $uniqueDestination,
    $log,
    $logValidationErrors,
    $resetDatabase
) {
    try {
        $pendingFiles = $filePeer->claimPendingRevalidation(20);
    } catch (Throwable $exception) {
        $log('Không thể đọc hàng đợi quét lại: ' . $exception->getMessage(), 'ERROR');
        try {
            $resetDatabase();
        } catch (Throwable $ignored) {
        }
        return;
    }
    foreach ($pendingFiles as $file) {
        $currentStoredPath = $file['file_path'];
        try {
            $candidate = preg_match('/^[A-Za-z]:[\\\\\/]/', $currentStoredPath) === 1
                ? $currentStoredPath
                : $projectRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $currentStoredPath);
            $source = realpath($candidate);
            $storageRoot = realpath($inboxDir);
            $storagePrefix = $storageRoot !== false
                ? rtrim($storageRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR : '';
            if ($source === false || $storagePrefix === ''
                || strncasecmp($source, $storagePrefix, strlen($storagePrefix)) !== 0
                || !is_file($source)) {
                throw new RuntimeException('Không tìm thấy file XML để quét lại.');
            }

            $content = file_get_contents($source);
            if ($content === false) {
                throw new RuntimeException('Không thể đọc file XML để quét lại.');
            }
            $decoded = XmlFileDecoder::decodeDanhSachHoSo($content);
            $validation = call_user_func($validationCallback, $decoded);
            $errorCount = 0;
            foreach ($validation as $validationItem) {
                $errorCount += isset($validationItem['error_count'])
                    ? (int) $validationItem['error_count'] : 0;
            }
            $newStatus = $errorCount > 0 ? 'failed' : 'processed';
            $targetDirectory = $newStatus === 'failed' ? $failedDir : $processedDir;
            $finalPath = $source;
            if (strcasecmp(dirname($source), $targetDirectory) !== 0) {
                $finalPath = $uniqueDestination($targetDirectory, basename($source));
                if (!rename($source, $finalPath)) {
                    throw new RuntimeException('Không thể chuyển file sau khi quét lại.');
                }
            }

            $oldErrorPath = $source . '.error.txt';
            $newErrorPath = $finalPath . '.error.txt';
            if ($newStatus === 'failed') {
                if ($oldErrorPath !== $newErrorPath && is_file($oldErrorPath)) {
                    @rename($oldErrorPath, $newErrorPath);
                }
                file_put_contents(
                    $newErrorPath,
                    'Validation failed with ' . $errorCount . ' error(s).' . PHP_EOL,
                    LOCK_EX
                );
                $organizedPaths = XmlErrorArchiveOrganizer::organize(
                    $finalPath,
                    $failedDir,
                    $validation,
                    true,
                    $file['ten']
                );
                if (!empty($organizedPaths)) $finalPath = $organizedPaths[0];
            } else {
                if (is_file($oldErrorPath)) @unlink($oldErrorPath);
                if ($newErrorPath !== $oldErrorPath && is_file($newErrorPath)) @unlink($newErrorPath);
            }

            $normalizedRoot = str_replace('\\', '/', rtrim($projectRoot, '\\/')) . '/';
            $normalizedFinalPath = str_replace('\\', '/', $finalPath);
            $finalStoredPath = strpos($normalizedFinalPath, $normalizedRoot) === 0
                ? substr($normalizedFinalPath, strlen($normalizedRoot)) : $normalizedFinalPath;
            $filePeer->updateValidationResult($file['id'], $validation);
            $filePeer->updateStorageLocation($file['id'], $finalStoredPath, $newStatus);
            $log('Đã quét lại ' . $file['ten'] . ': ' . $newStatus . ' (' . $errorCount . ' lỗi).');
            if ($errorCount > 0) $logValidationErrors($file['ten'], $validation);
        } catch (Throwable $exception) {
            try {
                $filePeer->updateStorageLocation($file['id'], $currentStoredPath, 'failed');
            } catch (Throwable $ignored) {
            }
            $log('Quét lại thất bại ' . $file['ten'] . ': ' . $exception->getMessage(), 'ERROR');
            try {
                $resetDatabase();
            } catch (Throwable $ignored) {
            }
        }
    }
};

$log('Bắt đầu theo dõi ' . $inboxDir . '.');

do {
    $cleanupDeleted();
    // Recover files claimed before an earlier worker was interrupted.
    foreach ($xmlFiles($processingDir) as $processingPath) {
        $process($processingPath);
    }

    $now = time();
    $currentPaths = array();
    foreach ($xmlFiles($inboxDir) as $path) {
        $currentPaths[$path] = true;
        $size = filesize($path);
        $modified = filemtime($path);
        if ($size === false || $modified === false) {
            continue;
        }

        if (!isset($observed[$path]) || $observed[$path]['size'] !== $size
            || $observed[$path]['modified'] !== $modified) {
            $observed[$path] = array('size' => $size, 'modified' => $modified, 'since' => $now);
        }

        $stableSince = $observed[$path]['since'];
        $oldEnough = ($now - $modified) >= $options['settle'];
        $observedLongEnough = ($now - $stableSince) >= $options['settle'];
        if (!$oldEnough || (!$options['once'] && !$observedLongEnough)) {
            continue;
        }

        $claimedPath = $processingDir . DIRECTORY_SEPARATOR . basename($path);
        if (file_exists($claimedPath) || !rename($path, $claimedPath)) {
            continue;
        }
        unset($observed[$path]);
        $process($claimedPath);
    }

    foreach (array_keys($observed) as $path) {
        if (!isset($currentPaths[$path])) {
            unset($observed[$path]);
        }
    }

    $revalidatePending();

    if (!$options['once']) {
        sleep($options['interval']);
    }
} while (!$options['once']);

$log('Quét thư mục hoàn tất.');
flock($lockHandle, LOCK_UN);
fclose($lockHandle);
