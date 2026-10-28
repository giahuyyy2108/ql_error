<?php

// -----------------------------------------------------------------------------
// Khởi động worker, đọc cấu hình CLI và chuẩn bị thư mục runtime
// -----------------------------------------------------------------------------

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Script này chỉ được chạy từ dòng lệnh.\n");
    exit(1);
}

date_default_timezone_set('Asia/Saigon');

$projectRoot = dirname(__DIR__);
$_SERVER['HTTPS'] = 'off';
$_SERVER['SERVER_PORT'] = 80;
$_SERVER['HTTP_HOST'] = 'localhost';
require $projectRoot . '/conf/config.php';

$resolveProjectPath = function ($configuredPath, $fallbackPath) use ($projectRoot) {
    $configuredPath = trim((string) $configuredPath);
    if ($configuredPath === '') $configuredPath = $fallbackPath;
    if (substr($configuredPath, 0, 2) === '\\\\' || substr($configuredPath, 0, 2) === '//') {
        $configuredPath = str_replace('/', '\\', $configuredPath);
        return rtrim($configuredPath, "\\/");
    }
    if (preg_match('/^[A-Za-z]:[\\\\\/]*$/', $configuredPath) === 1) {
        return strtoupper(substr($configuredPath, 0, 1)) . ':\\';
    }
    if (preg_match('/^[A-Za-z]:[\\\\\/]/', $configuredPath) === 1 || substr($configuredPath, 0, 1) === '/') {
        return rtrim($configuredPath, "\\/");
    }
    return $projectRoot . DIRECTORY_SEPARATOR . trim(str_replace('/', DIRECTORY_SEPARATOR, $configuredPath), "\\/");
};
$configuredInboxPath = '';
if (class_exists('mysqli')) {
    try {
        $pathConnection = @mysqli_connect(
            _DATABASE_HOST_,
            _DATABASE_USERNAME_,
            _DATABASE_PASSWORD_,
            _DATABASE_NAME_
        );
        if ($pathConnection instanceof mysqli) {
            $pathResult = @$pathConnection->query('SELECT path FROM path WHERE id = 1 LIMIT 1');
            if ($pathResult && ($pathRow = $pathResult->fetch_assoc())) {
                $configuredInboxPath = trim((string) $pathRow['path']);
                $pathResult->free();
            }
            $pathConnection->close();
        }
    } catch (Throwable $ignored) {
        $configuredInboxPath = '';
    }
}
$defaultInboxDir = $resolveProjectPath(
    $configuredInboxPath !== ''
        ? $configuredInboxPath
        : (defined('_XML_WATCHER_STORAGE_PATH_') ? _XML_WATCHER_STORAGE_PATH_ : ''),
    'storage/xml'
);
$defaultRuntimeDir = $resolveProjectPath(
    defined('_XML_WATCHER_RUNTIME_PATH_') ? _XML_WATCHER_RUNTIME_PATH_ : '',
    'storage/watcher'
);

$options = array(
    'once' => false,
    'interval' => 1,
    'settle' => 2,
    'worker_id' => 1,
    'retention_days' => 30,
    'dir' => $defaultInboxDir
);

foreach (array_slice($argv, 1) as $argument) {
    if ($argument === '--once') {
        $options['once'] = true;
    } elseif ($argument === '--help' || $argument === '-h') {
        echo "Theo dõi thư mục và tự động nhập file XML.\n\n";
        echo "Cách dùng:\n";
        echo "  php scripts/watch_xml.php [--once] [--worker-id=1] [--interval=2] [--settle=3] [--retention-days=30] [--dir=PATH]\n\n";
        echo "  --once       Quét một lần rồi thoát.\n";
        echo "  --interval   Số giây nghỉ giữa hai lần quét.\n";
        echo "  --settle     File phải ổn định bao nhiêu giây trước khi xử lý.\n";
        echo "  --worker-id  Mã worker dương; mỗi worker dùng thư mục claim và file lock riêng.\n";
        echo "  --retention-days  Số ngày giữ file trong deleted.\n";
        echo "  --dir        Thư mục nhận file; mặc định lấy từ bảng path (ID 1).\n";
        exit(0);
    } elseif (strpos($argument, '--interval=') === 0) {
        $options['interval'] = max(1, (int) substr($argument, 11));
    } elseif (strpos($argument, '--settle=') === 0) {
        $options['settle'] = max(1, (int) substr($argument, 9));
    } elseif (strpos($argument, '--worker-id=') === 0) {
        $options['worker_id'] = max(1, (int) substr($argument, 12));
    } elseif (strpos($argument, '--retention-days=') === 0) {
        $options['retention_days'] = max(1, (int) substr($argument, 17));
    } elseif (strpos($argument, '--dir=') === 0) {
        $options['dir'] = substr($argument, 6);
    } else {
        fwrite(STDERR, "Tham số không hợp lệ: " . $argument . "\n");
        exit(1);
    }
}

$inboxDir = trim((string) $options['dir']);
if (preg_match('/^[A-Za-z]:[\\\\\/]*$/', $inboxDir) === 1) {
    $inboxDir = strtoupper(substr($inboxDir, 0, 1)) . ':\\';
} else {
    $inboxDir = rtrim($inboxDir, "\\/");
}
if ($inboxDir === '') {
    fwrite(STDERR, "Thư mục theo dõi không hợp lệ.\n");
    exit(1);
}

$processingRootDir = $inboxDir . DIRECTORY_SEPARATOR . 'processing';
$processingDir = $processingRootDir . DIRECTORY_SEPARATOR . 'worker-' . $options['worker_id'];
$processedDir = $inboxDir . DIRECTORY_SEPARATOR . 'processed';
$failedDir = $inboxDir . DIRECTORY_SEPARATOR . 'failed';
$deletedDir = $inboxDir . DIRECTORY_SEPARATOR . 'deleted';
$archiveDir = $inboxDir . DIRECTORY_SEPARATOR . 'archive';
$runtimeDir = $defaultRuntimeDir;
$turnPath = $runtimeDir . DIRECTORY_SEPARATOR . '.xml_worker_turn';
$runtimeLogsRoot = $runtimeDir . DIRECTORY_SEPARATOR . 'logs';
$runtimeLogDir = $runtimeLogsRoot . DIRECTORY_SEPARATOR . date('Ymd');
foreach (array($inboxDir, $processingRootDir, $processingDir, $processedDir, $failedDir, $deletedDir, $archiveDir, $runtimeDir, $runtimeLogsRoot, $runtimeLogDir) as $directory) {
    if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
        fwrite(STDERR, "Không thể tạo thư mục: " . $directory . "\n");
        exit(1);
    }
}

$lockHandle = fopen(
    $runtimeDir . DIRECTORY_SEPARATOR . '.watch_xml_' . $options['worker_id'] . '.lock',
    'c'
);
if ($lockHandle === false || !flock($lockHandle, LOCK_EX | LOCK_NB)) {
    fwrite(STDERR, "Đã có một XML watcher khác đang chạy cho thư mục này.\n");
    exit(1);
}

$connect = '';
require $projectRoot . '/web_src/common/mysql.php';
require $projectRoot . '/web_src/bean/FilePeer.php';
require $projectRoot . '/web_src/common/XmlFileImportService.php';
require $projectRoot . '/web_src/common/XmlRulesValidator.php';
require $projectRoot . '/web_src/bean/XmlValidationRulePeer.php';
require $projectRoot . '/web_src/bean/ApiValidationConfigPeer.php';
require $projectRoot . '/web_src/common/ApiValidationService.php';
require $projectRoot . '/web_src/common/TableLookupService.php';
require $projectRoot . '/web_src/common/BhytLookupService.php';
require $projectRoot . '/web_src/common/XmlErrorArchiveOrganizer.php';
require $projectRoot . '/web_src/common/FileStorageService.php';

$filePeer = null;
$validationCallback = null;
$fileStorage = null;

// Tạo lại toàn bộ service phụ thuộc database. Closure này cũng được dùng khi
// kết nối DB lỗi để worker có thể tự nối lại ở vòng xử lý kế tiếp.
$buildImportService = function () use (&$filePeer, &$validationCallback) {
    $filePeer = new FilePeer();
    $rulePeer = new XmlValidationRulePeer();
    $apiService = new ApiValidationService(new ApiValidationConfigPeer());
    $tableLookupService = new TableLookupService();
    $bhytService = new BhytLookupService();
    $rulesCache = null;
    $rulesLoadedAt = 0;
    $validationCallback = function (array $decodedContent) use (
        $rulePeer,
        $apiService,
        $tableLookupService,
        &$rulesCache,
        &$rulesLoadedAt
    ) {
        if ($rulesCache === null || (time() - $rulesLoadedAt) >= 30) {
            $rulesCache = $rulePeer->getRulesForValidation();
            $rulesLoadedAt = time();
        }
        return XmlRulesValidator::validate(
            $decodedContent,
            $rulesCache,
            $apiService,
            $tableLookupService
        );
    };
    return new XmlFileImportService(
        $filePeer,
        5242880,
        $validationCallback,
        function ($maThe, $hoTen, $ngaySinh) use ($bhytService) {
            return $bhytService->traCuuThongTin($maThe, $hoTen, $ngaySinh);
        }
    );
};
$importService = $buildImportService();
$fileStorage = new FileStorageService(
    $projectRoot,
    $inboxDir,
    $filePeer->getConfiguredKhoaOutputPaths()
);
$refreshStorageRoots = function () use (&$filePeer, $fileStorage) {
    $fileStorage->addStorageRoots($filePeer->getConfiguredKhoaOutputPaths());
};
$observed = array();

$workerLogPath = $runtimeLogDir . DIRECTORY_SEPARATOR . 'watcher.log';
$workerErrorLogPath = $runtimeLogDir . DIRECTORY_SEPARATOR . 'watcher.error.log';

// -----------------------------------------------------------------------------
// Ghi log và cập nhật tiến độ dùng chung giữa các worker
// -----------------------------------------------------------------------------

$log = function ($message, $level = 'INFO') use ($workerLogPath, $workerErrorLogPath, $options) {
    $level = strtoupper((string) $level);
    $line = '[' . date('Y-m-d H:i:s') . '] [' . $level . '] [WORKER:'
        . $options['worker_id'] . '] ' . $message . PHP_EOL;
    echo $line;
    file_put_contents($workerLogPath, $line, FILE_APPEND | LOCK_EX);
    if ($level === 'ERROR') {
        file_put_contents($workerErrorLogPath, $line, FILE_APPEND | LOCK_EX);
    }
};

// Keep one progress state for all workers. The state is locked while it is
// updated so the percentage represents the whole XML queue, not one worker.
$progressPath = $runtimeDir . DIRECTORY_SEPARATOR . '.xml_import_progress.json';
$updateProgress = function ($queuedPaths, $completedPaths, $currentPath = null) use ($progressPath, $options) {
    $handle = @fopen($progressPath, 'c+');
    if ($handle === false || !flock($handle, LOCK_EX)) {
        if ($handle !== false) fclose($handle);
        return array('total' => 0, 'completed' => 0);
    }

    rewind($handle);
    $contents = stream_get_contents($handle);
    $state = json_decode($contents, true);
    if (!is_array($state) || !isset($state['items']) || !is_array($state['items'])) {
        $state = array('items' => array());
    }
    if (!isset($state['workers']) || !is_array($state['workers'])) {
        $state['workers'] = array();
    }

    $isComplete = !empty($state['items']);
    foreach ($state['items'] as $status) {
        if ($status !== 'completed') {
            $isComplete = false;
            break;
        }
    }
    // A new file after a completed queue starts a fresh progress batch.
    if ($isComplete && !empty($queuedPaths)) {
        $state['items'] = array();
    }

    foreach ($queuedPaths as $path) {
        if (!isset($state['items'][$path])) {
            $state['items'][$path] = 'pending';
        }
    }
    foreach ($completedPaths as $path) {
        if (isset($state['items'][$path])) {
            $state['items'][$path] = 'completed';
        }
    }
    if ($currentPath !== null) {
        $state['workers'][(string) $options['worker_id']] = basename($currentPath);
    }

    $total = count($state['items']);
    $completed = 0;
    foreach ($state['items'] as $status) {
        if ($status === 'completed') $completed++;
    }
    $state['updated_at'] = time();
    rewind($handle);
    ftruncate($handle, 0);
    fwrite($handle, json_encode($state));
    fflush($handle);
    flock($handle, LOCK_UN);
    fclose($handle);

    return array('total' => $total, 'completed' => $completed, 'workers' => $state['workers']);
};

// Progress is deliberately written only to the console. Keeping it out of the
// worker log avoids producing a log entry for every XML in a large batch.
$showProgress = function (array $progress, $path) use ($options) {
    if ($progress['total'] < 1) {
        return;
    }
    $percent = (int) floor(($progress['completed'] * 100) / $progress['total']);
    $barWidth = 30;
    $filled = (int) floor(($percent * $barWidth) / 100);
    $bar = str_repeat("\xE2\x94\x81", $filled) . str_repeat("\xE2\x94\x80", $barWidth - $filled);
    $line = function ($workerId) use ($progress, $bar, $percent) {
        $fileName = isset($progress['workers'][(string) $workerId])
            ? $progress['workers'][(string) $workerId] : 'Waiting for file...';
        $text = '[WORKER:' . $workerId . '] [' . $bar . '] ' . $percent . '% ('
            . $progress['completed'] . '/' . $progress['total'] . ') ' . $fileName;
        if (function_exists('mb_strimwidth')) {
            return mb_strimwidth($text, 0, 78, '', 'UTF-8');
        }
        return substr($text, 0, 78);
    };

    if (getenv('XML_WATCHER_ANSI') === '1') {
        $panelTop = (int) getenv('XML_WATCHER_PROGRESS_TOP');
        $panelLeft = 1;
        $layoutPath = getenv('XML_WATCHER_PROGRESS_LAYOUT_PATH');
        if ($layoutPath !== false && is_file($layoutPath)) {
            $configuredTop = @file_get_contents($layoutPath);
            $layout = explode(',', trim((string) $configuredTop));
            if (isset($layout[0]) && is_numeric($layout[0])) $panelTop = (int) $layout[0];
            if (isset($layout[1]) && is_numeric($layout[1])) $panelLeft = (int) $layout[1];
        }
        $panelTop = max(1, $panelTop);
        $panelLeft = max(1, $panelLeft);
        $clearPanel = '';
        for ($row = $panelTop; $row < ($panelTop + 4); $row++) {
            $clearPanel .= "\x1B[" . $row . ";1H\x1B[2K";
        }
        $panel = "\x1B[s" . $clearPanel
            . "\x1B[" . $panelTop . ";" . $panelLeft . "H" . '+' . str_repeat('-', 78) . '+'
            . "\x1B[" . ($panelTop + 1) . ";" . $panelLeft . "H" . '|' . str_pad($line(1), 78) . '|'
            . "\x1B[" . ($panelTop + 2) . ";" . $panelLeft . "H" . '|' . str_pad($line(2), 78) . '|'
            . "\x1B[" . ($panelTop + 3) . ";" . $panelLeft . "H" . '+' . str_repeat('-', 78) . '+'
            . "\x1B[u";
        fwrite(STDOUT, $panel);
        fflush(STDOUT);
        return;
    }
    fwrite(
        STDOUT,
        '[' . date('Y-m-d H:i:s') . '] [WORKER:' . $options['worker_id'] . '] '
        . basename($path) . '  ' . $bar . '  ' . $percent . '% '
        . $progress['completed'] . '/' . $progress['total'] . ' files' . PHP_EOL
    );
    fflush(STDOUT);
};

$claimTurn = function ($path) use ($processingDir) {
    $destination = $processingDir . DIRECTORY_SEPARATOR . basename($path);
    if (file_exists($destination)) return false;
    return @rename($path, $destination);
};

// -----------------------------------------------------------------------------
// Helper cho claim file, log validation, dọn dữ liệu cũ và lưu trữ file
// -----------------------------------------------------------------------------

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
$cleanupDeleted = function () use ($deletedDir, $runtimeLogsRoot, $options, $log, &$lastCleanup) {
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
    foreach (new DirectoryIterator($runtimeLogsRoot) as $logDirectory) {
        if (!$logDirectory->isDir() || $logDirectory->isDot()
            || !preg_match('/^\d{8}$/', $logDirectory->getFilename())) continue;
        $date = DateTime::createFromFormat('!Ymd', $logDirectory->getFilename());
        if ($date === false || $date->getTimestamp() > $cutoff) continue;
        foreach (new DirectoryIterator($logDirectory->getPathname()) as $logFile) {
            if ($logFile->isFile()) @unlink($logFile->getPathname());
        }
        $remaining = array_diff(scandir($logDirectory->getPathname()), array('.', '..'));
        if (empty($remaining)) @rmdir($logDirectory->getPathname());
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

$uniqueDestination = function ($directory, $fileName) use ($fileStorage) {
    return $fileStorage->uniqueDestination($directory, $fileName);
};

$archive = function ($source, $directory) use ($uniqueDestination) {
    $destination = $uniqueDestination($directory, basename($source));
    if (!rename($source, $destination)) {
        throw new RuntimeException('Không thể chuyển file sang ' . $directory . '.');
    }
    return $destination;
};

$datedDirectory = function ($baseDirectory) use ($fileStorage) {
    return $fileStorage->datedDirectory($baseDirectory);
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

// -----------------------------------------------------------------------------
// Import file mới: decode -> validate -> lưu DB -> chuyển processed/failed
// -----------------------------------------------------------------------------

$process = function ($path) use (
    &$importService,
    &$filePeer,
    $projectRoot,
    $inboxDir,
    $processedDir,
    $failedDir,
    $deletedDir,
    $archiveDir,
    $uniqueDestination,
    $archive,
    $datedDirectory,
    $fileStorage,
    $log,
    $logValidationErrors,
    $resetDatabase
) {
    $processStartedAt = microtime(true);
    $datedFailedDir = $datedDirectory($failedDir);
    // Chỉ cần một đường dẫn tạm có đúng basename để import lấy metadata hàng đợi.
    // Đường dẫn chính thức được xác định sau import từ id_khoa đã lưu trong DB.
    $storedPath = $fileStorage->relativeToProject($path);
    try {
        $result = $importService->importPath($path, $storedPath);
    } catch (Throwable $exception) {
        try {
            $failureKhoa = 'KHONG_XAC_DINH';
            $failureOutputPath = null;
            try {
                $failureDecoded = XmlFileDecoder::decodeDanhSachHoSoPathWithMetadata($path);
                $failureXml1 = XmlFileDecoder::extractValuesFromFileType(
                    $failureDecoded['content'],
                    'XML1',
                    array('MA_KHOA')
                );
                $resolvedFailureKhoa = $filePeer->resolveRootKhoaCode($failureXml1['MA_KHOA']);
                if ($resolvedFailureKhoa !== null && $resolvedFailureKhoa !== '') {
                    $failureKhoa = $resolvedFailureKhoa;
                    $failureOutputPath = $filePeer->getKhoaOutputPath($failureKhoa);
                }
            } catch (Throwable $ignored) {
            }
            $failureDirectory = $fileStorage->khoaStatusDateDirectory(
                $failureKhoa,
                'failed',
                null,
                $failureOutputPath
            );
            $failedPath = $archive($path, $failureDirectory);
            $failureValidation = array(array(
                'file_type' => 'XML',
                'error_count' => 1,
                'errors' => array(array(
                    'severity' => 'error',
                    'message' => $exception->getMessage()
                ))
            ));
            $organizedFailure = XmlErrorArchiveOrganizer::organize(
                $failedPath,
                $failureDirectory,
                $failureValidation,
                true,
                basename($path)
            );
            if (empty($organizedFailure)) {
                file_put_contents($failedPath . '.error.txt', $exception->getMessage() . PHP_EOL, LOCK_EX);
            }
        } catch (Throwable $archiveException) {
            $log('Không thể lưu file lỗi ' . basename($path) . ': ' . $archiveException->getMessage(), 'ERROR');
        }
        $log('File lỗi ' . basename($path) . ': ' . $exception->getMessage()
            . ' (thời gian ' . number_format(microtime(true) - $processStartedAt, 3) . 's)', 'ERROR');
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
        $storedFile = $isImport ? $filePeer->getById($result['id']) : false;
        $idKhoa = $storedFile && !empty($storedFile['id_khoa'])
            ? (string) $storedFile['id_khoa'] : 'KHONG_XAC_DINH';
        $khoaOutputPath = $filePeer->getKhoaOutputPath($idKhoa);
        $datedKhoaDir = $fileStorage->khoaStatusDateDirectory(
            $idKhoa,
            $validationFailed ? 'failed' : 'processed',
            null,
            $khoaOutputPath
        );
        $finalPath = $uniqueDestination($datedKhoaDir, basename($path));
        if (!rename($path, $finalPath)) {
            throw new RuntimeException('Không thể chuyển file sang thư mục kết quả.');
        }
        if ($isImport) {
            $finalStoredPath = $fileStorage->relativeToProject($finalPath);
            $filePeer->updateStorageLocation(
                $result['id'],
                $finalStoredPath,
                $validationFailed ? 'failed' : 'processed'
            );
        }
        if ($result['status'] === 'reimported' && !empty($result['old_file_path'])) {
            $oldErrors = isset($result['old_file']['validation_result'])
                ? $result['old_file']['validation_result'] : null;
            $oldErrorList = array();
            $oldValidation = json_decode((string) $oldErrors, true);
            if (is_array($oldValidation)) {
                foreach ($oldValidation as $validationItem) {
                    foreach ((isset($validationItem['errors']) && is_array($validationItem['errors']) ? $validationItem['errors'] : array()) as $error) {
                        $message = isset($error['message']) ? (string) $error['message'] : 'Lỗi validation';
                        $field = isset($error['field_name']) ? (string) $error['field_name'] : '';
                        $oldErrorList[] = $field !== '' ? $field . ': ' . $message : $message;
                    }
                }
            }
            $oldErrors = $oldErrorList ? implode(PHP_EOL, $oldErrorList) : (string) $oldErrors;
            $filePeer->saveAuditLog($result['id'], $result['ma_lk'], $result['name'], 'UPDATE', $result['old_file_path'], $finalStoredPath, $oldErrors);
            $oldPath = preg_match('/^[A-Za-z]:[\\\\\/]/', $result['old_file_path']) === 1
                ? $result['old_file_path']
                : $projectRoot . DIRECTORY_SEPARATOR
                    . str_replace('/', DIRECTORY_SEPARATOR, $result['old_file_path']);
            $resolvedOldPath = realpath($oldPath);
            if ($resolvedOldPath === false) {
                $search = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($inboxDir, FilesystemIterator::SKIP_DOTS)
                );
                foreach ($search as $candidate) {
                    if ($candidate->isFile() && strcasecmp($candidate->getFilename(), basename($oldPath)) === 0) {
                        $resolvedOldPath = $candidate->getRealPath();
                        break;
                    }
                }
            }
            if ($resolvedOldPath !== false && is_file($resolvedOldPath)
                && strcasecmp($resolvedOldPath, $finalPath) !== 0) {
                $archiveDateDir = $archiveDir . DIRECTORY_SEPARATOR . date('Ymd');
                if (!is_dir($archiveDateDir) && !mkdir($archiveDateDir, 0775, true) && !is_dir($archiveDateDir)) {
                    throw new RuntimeException('Không thể tạo thư mục lưu phiên bản cũ.');
                }
                $archiveBase = pathinfo(basename($resolvedOldPath), PATHINFO_FILENAME)
                    . '_' . date('Ymd_His') . '.xml';
                $deletedPath = $uniqueDestination($archiveDateDir, $archiveBase);
                if (rename($resolvedOldPath, $deletedPath)) {
                    touch($deletedPath);
                    $filePeer->saveFileHistory($result['old_file'], $deletedPath);
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
                $datedKhoaDir,
                $result['validation'],
                true,
                $result['name']
            );
            if (!empty($organizedPaths)) {
                $organizedStoredPath = $fileStorage->relativeToProject($organizedPaths[0]);
                $filePeer->updateStorageLocation($result['id'], $organizedStoredPath, 'failed');
            }
            $log('File không đạt validation (' . $errorCount . ' lỗi): ' . $result['name']
                . ' (thời gian ' . number_format(microtime(true) - $processStartedAt, 3) . 's)', 'WARNING');
            $logValidationErrors($result['name'], $result['validation']);
            return;
        }
        $staleErrorPath = $datedFailedDir . DIRECTORY_SEPARATOR . $result['name'] . '.error.txt';
        if (is_file($staleErrorPath)) {
            unlink($staleErrorPath);
        }
        if ($result['status'] === 'reimported') {
            $log('Đã import và quét lại MA_LK ' . $result['ma_lk'] . ': ' . $result['name']
                . ' (thời gian ' . number_format(microtime(true) - $processStartedAt, 3) . 's)');
        } else {
            $log('Đã nhập ' . $result['name'] . ' (MA_LK ' . $result['ma_lk'] . ')'
                . ' trong ' . number_format(microtime(true) - $processStartedAt, 3) . 's.');
        }
    } catch (Throwable $exception) {
        // Keep the imported file in processing. On restart it is detected as a
        // duplicate and archiving is retried without inserting another record.
        $log('Đã nhập dữ liệu nhưng chưa thể lưu trữ file ' . basename($path) . ': ' . $exception->getMessage(), 'ERROR');
    }
};

// -----------------------------------------------------------------------------
// Quét lại hồ sơ đã có trong DB khi người dùng tạo hàng đợi revalidation
// -----------------------------------------------------------------------------

$revalidatePending = function () use (
    &$filePeer,
    &$validationCallback,
    $projectRoot,
    $inboxDir,
    $processingDir,
    $processedDir,
    $failedDir,
    $uniqueDestination,
    $datedDirectory,
    $fileStorage,
    $log,
    $logValidationErrors,
    $resetDatabase
) {
    try {
        // Revalidation is shared by every watcher and the web UI. If another
        // process owns the queue mutex, skip this cycle and try again later.
        $pendingFiles = $filePeer->claimPendingRevalidation(1, null, null, 0);
    } catch (RevalidationQueueBusyException $exception) {
        return;
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
        $workingPath = null;
        try {
            $source = $fileStorage->resolveStoredFilePath($currentStoredPath);

            $workingPath = $uniqueDestination($processingDir, basename($source));
            if (!function_exists('link') || !@link($source, $workingPath)) {
                $workingPath = null;
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
            $revalidateKhoa = !empty($file['id_khoa'])
                ? (string) $file['id_khoa'] : 'KHONG_XAC_DINH';
            $revalidateOutputPath = $filePeer->getKhoaOutputPath($revalidateKhoa);
            $targetDirectory = $fileStorage->khoaStatusDateDirectory(
                $revalidateKhoa,
                $newStatus,
                null,
                $revalidateOutputPath
            );
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
                    $targetDirectory,
                    $validation,
                    true,
                    $file['ten']
                );
                if (!empty($organizedPaths)) $finalPath = $organizedPaths[0];
            } else {
                if (is_file($oldErrorPath)) @unlink($oldErrorPath);
                if ($newErrorPath !== $oldErrorPath && is_file($newErrorPath)) @unlink($newErrorPath);
            }

            $finalStoredPath = $fileStorage->relativeToProject($finalPath);
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
        } finally {
            if ($workingPath !== null && is_file($workingPath)) {
                @unlink($workingPath);
            }
        }
    }
};

// -----------------------------------------------------------------------------
// Vòng lặp chính của worker
// -----------------------------------------------------------------------------

$log('Worker ' . $options['worker_id'] . ' bắt đầu theo dõi ' . $inboxDir . '.');

do {
    try {
        // Khoa output paths can be changed from the web UI while this long-running
        // worker is active. Add newly configured roots without requiring a restart.
        $refreshStorageRoots();
    } catch (Throwable $exception) {
        $log('Khong the cap nhat cau hinh dau ra khoa: ' . $exception->getMessage(), 'ERROR');
    }
    $cleanupDeleted();
    // Recover files claimed before an earlier worker was interrupted.
    $recoveryPaths = $xmlFiles($processingDir);
    if (!empty($recoveryPaths)) {
        $updateProgress($recoveryPaths, array());
    }
    foreach ($recoveryPaths as $processingPath) {
        $showProgress($updateProgress(array(), array(), $processingPath), $processingPath);
        $process($processingPath);
        $completedPaths = is_file($processingPath) ? array() : array($processingPath);
        $showProgress($updateProgress(array(), $completedPaths, $processingPath), $processingPath);
    }

    $now = time();
    $currentPaths = array();
    $readyPaths = array();
    foreach ($xmlFiles($inboxDir) as $path) {
        $currentPaths[$path] = true;
        // Another worker (or an external process) may move the file after the
        // directory snapshot was built. Treat that as a normal claim race.
        $stat = @stat($path);
        if ($stat === false) {
            continue;
        }
        $size = $stat['size'];
        $modified = $stat['mtime'];

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

        $readyPaths[] = $path;
    }

    if (!empty($readyPaths)) {
        $updateProgress($readyPaths, array());
    }
    foreach ($readyPaths as $path) {

        $claimedPath = $processingDir . DIRECTORY_SEPARATOR . basename($path);
        if (file_exists($claimedPath) || !$claimTurn($path)) {
            $showProgress($updateProgress(array(), array(), $path), $path);
            continue;
        }
        unset($observed[$path]);
        $showProgress($updateProgress(array(), array(), $path), $path);
        $process($claimedPath);
        $completedPaths = is_file($claimedPath) ? array() : array($path);
        $showProgress($updateProgress(array(), $completedPaths, $path), $path);
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
