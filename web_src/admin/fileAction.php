<?php

require_once __DIR__ . '/../common/Request.php';
require_once __DIR__ . '/../bean/FilePeer.php';
require_once __DIR__ . '/../common/XmlFileDecoder.php';
require_once __DIR__ . '/../common/XmlFileImportService.php';
require_once __DIR__ . '/../common/XmlRulesValidator.php';
require_once __DIR__ . '/../bean/XmlValidationRulePeer.php';
require_once __DIR__ . '/../bean/ApiValidationConfigPeer.php';
require_once __DIR__ . '/../common/ApiValidationService.php';
require_once __DIR__ . '/../common/TableLookupService.php';
require_once __DIR__ . '/../common/BhytLookupService.php';
require_once __DIR__ . '/../common/XmlErrorArchiveOrganizer.php';
require_once __DIR__ . '/../common/FileStorageService.php';
require_once __DIR__ . '/../common/UploadQueueService.php';
require_once __DIR__ . '/../common/PortalService.php';
require_once __DIR__ . '/../bean/HeThongPeer.php';

class fileAction
{
    protected $request;
    protected $filePeer;
    private $fileStorage;
    private $uploadQueue;
    private $portalPath;
    private $maxFileSize = 5242880; // 5 MB

    // ---------------------------------------------------------------------
    // Khởi tạo trang và ngữ cảnh dùng chung
    // ---------------------------------------------------------------------

    public function __construct()
    {
        $this->request = new Request();
        $this->filePeer = new FilePeer();
        $storageRoot = null;
        $this->portalPath = null;
        $additionalStorageRoots = array();
        $heThongPeer = null;
        try {
            $heThongPeer = new HeThongPeer();
            $pathConfig = $heThongPeer->getInputPath();
            if (is_array($pathConfig) && isset($pathConfig['path'])
                && trim((string) $pathConfig['path']) !== ''
            ) {
                $storageRoot = $pathConfig['path'];
            }
        } catch (Throwable $ignored) {
            $storageRoot = null;
        }
        try {
            if ($heThongPeer === null) {
                $heThongPeer = new HeThongPeer();
            }
            $portalPathConfig = $heThongPeer->getPortalPath();
            if (is_array($portalPathConfig) && isset($portalPathConfig['path'])
                && trim((string) $portalPathConfig['path']) !== ''
            ) {
                $this->portalPath = $portalPathConfig['path'];
            }
        } catch (Throwable $ignored) {
            $this->portalPath = null;
        }
        try {
            $additionalStorageRoots = $this->filePeer->getConfiguredKhoaOutputPaths();
        } catch (Throwable $ignored) {
            $additionalStorageRoots = array();
        }
        $this->fileStorage = new FileStorageService(
            dirname(__DIR__, 2),
            $storageRoot,
            $additionalStorageRoots
        );
        $this->uploadQueue = new UploadQueueService($this->filePeer, $this->fileStorage);
    }

    public function index()
    {
        $currentUserKhoa = $this->currentUserKhoa();
        $isAdmin = isset($_SESSION['AdminType']) && (int) $_SESSION['AdminType'] === 1;
        $this->request->setAttribute('currentUserKhoa', $currentUserKhoa);
        $this->request->setAttribute('canChooseUploadKhoa', $isAdmin);
        $this->request->setAttribute(
            'uploadKhoaOptions',
            $isAdmin ? $this->filePeer->getKhoaOptions() : array()
        );
        if (empty($_SESSION['xml_file_csrf'])) {
            $_SESSION['xml_file_csrf'] = bin2hex(random_bytes(32));
        }

        $this->request->setAttribute('xmlFileCsrf', $_SESSION['xml_file_csrf']);
        $this->request->setAttribute(
            'css',
            '<link href="' . _DEFAULT_URL_ . 'css/file.css?' . _DEFAULT_VERSION_JS_CSS_
            . '&amp;v=' . filemtime(dirname(__DIR__, 2) . '/css/file.css') . '" rel="stylesheet">'
        );
        $this->request->setAttribute(
            'script',
            '<script src="' . _DEFAULT_URL_ . 'js/file.js?' . _DEFAULT_VERSION_JS_CSS_
            . '&amp;v=' . filemtime(dirname(__DIR__, 2) . '/js/file.js') . '"></script>'
        );
        $this->request->setModel('www/file/index.php');
        return true;
    }

    // ---------------------------------------------------------------------
    // Danh sách file, tải lên và xem nội dung XML
    // ---------------------------------------------------------------------

    public function getData()
    {
        $date = $this->selectedDate();
        if ($date === null) {
            return $this->json(array('data' => array(), 'error' => 'Ngày được chọn không hợp lệ.'));
        }
        try {
            return $this->json(array('data' => $this->filePeer->getList($date)));
        } catch (RuntimeException $exception) {
            return $this->json(array('data' => array(), 'error' => $exception->getMessage()));
        }
    }

    public function upload()
    {
        if (!$this->validCsrf()) {
            return $this->json(array('success' => false, 'message' => 'Phiên làm việc không hợp lệ. Vui lòng tải lại trang.'));
        }

        if (!isset($_FILES['xmlFile']) || !is_array($_FILES['xmlFile'])) {
            return $this->json(array('success' => false, 'message' => 'Vui lòng chọn file XML.'));
        }

        $upload = $_FILES['xmlFile'];
        if ($upload['error'] !== UPLOAD_ERR_OK) {
            return $this->json(array('success' => false, 'message' => $this->uploadErrorMessage($upload['error'])));
        }
        if ($upload['size'] <= 0 || $upload['size'] > $this->maxFileSize) {
            return $this->json(array('success' => false, 'message' => 'File XML phải có dung lượng từ 1 byte đến 5 MB.'));
        }

        $fileName = $this->safeFileName($upload['name']);
        if ($fileName === false) {
            return $this->json(array('success' => false, 'message' => 'Tên file không hợp lệ hoặc file không có đuôi .xml.'));
        }

        $requestedKhoa = $this->request->getParameter('id_khoa');
        $isAdmin = isset($_SESSION['AdminType']) && (int) $_SESSION['AdminType'] === 1;
        if (!$isAdmin) {
            $requestedKhoa = $this->currentUserKhoa();
        }

        try {
            $queuedName = $this->uploadQueue->enqueue(
                $upload['tmp_name'],
                $fileName,
                $requestedKhoa,
                $this->currentUploadUsername()
            );
        } catch (Throwable $exception) {
            return $this->json(array('success' => false, 'message' => $exception->getMessage()));
        }

        return $this->json(array(
            'success' => true,
            'queued' => true,
            'name' => $queuedName,
            'message' => 'Đã đưa file XML vào hàng đợi xử lý.'
        ));
    }

    public function getContent()
    {
        $id = (int) $this->request->getParameter('id', false);
        if ($id <= 0) {
            return $this->json(array('success' => false, 'message' => 'Mã file không hợp lệ.'));
        }

        try {
            $file = $this->filePeer->getById($id);
        } catch (Throwable $exception) {
            return $this->json(array('success' => false, 'message' => $exception->getMessage()));
        }
        if ($file === false) {
            return $this->json(array('success' => false, 'message' => 'File không tồn tại.'));
        }

        try {
            $storedPath = $this->resolveStoredFilePath($file['file_path']);
            $content = file_get_contents($storedPath);
            if ($content === false) {
                throw new RuntimeException('Không thể đọc file XML trên ổ đĩa.');
            }
            $decodedContent = XmlFileDecoder::decodeDanhSachHoSo($content);
            $validation = json_decode((string) $file['validation_result'], true);
            if (!is_array($validation)) $validation = array();
        } catch (RuntimeException $exception) {
            return $this->json(array('success' => false, 'message' => $exception->getMessage()));
        }

        return $this->json(array(
            'success' => true,
            'name' => $file['ten'],
            'decoded' => $decodedContent,
            'validation' => $validation,
            'display_settings' => (new HeThongPeer())->getXmlDisplaySettings()
        ));
    }

    public function getCongHosoData()
    {
        $date = trim((string) $this->request->getParameter('date', false));
        if ($date !== '') {
            $parsedDate = DateTime::createFromFormat('!Y-m-d', $date);
            if (!$parsedDate || $parsedDate->format('Y-m-d') !== $date) {
                return $this->json(array('data' => array(), 'error' => 'Ngày không hợp lệ.'));
            }
        } else {
            $date = null;
        }
        $maLk = trim((string) $this->request->getParameter('malk', false));
        $limit = (int) $this->request->getParameter('limit', false);
        try {
            return $this->json(array(
                'data' => $this->filePeer->getCongHosoData($date, $maLk !== '' ? $maLk : null, $limit ?: 1000)
            ));
        } catch (Throwable $exception) {
            return $this->json(array('data' => array(), 'error' => $exception->getMessage()));
        }
    }

    // ---------------------------------------------------------------------
    // Đẩy cổng và lịch sử đẩy hồ sơ
    // ---------------------------------------------------------------------

    public function pushPortal()
    {
        $ids = json_decode((string) $this->request->getParameter('ids', false), true);
        $confirmedWarning = $this->request->getParameter('confirm_warning', false) === '1';
        if (!is_array($ids) || !$ids) {
            return $this->json(array('success' => false, 'message' => 'Vui lòng chọn file cần đẩy cổng.'));
        }

        try {
            $result = (new PortalService($this->filePeer, $this->fileStorage, $this->portalPath))->push(
                $ids,
                $confirmedWarning,
                $this->currentUsername(),
                $this->currentUserKhoa(true)
            );
            return $this->json(array(
                'success' => true,
                'done' => $result['done'],
                'skipped' => $result['skipped']
            ));
        } catch (Throwable $e) {
            return $this->json(array('success' => false, 'message' => $e->getMessage()));
        }
    }

    public function getAuditLogs()
    {
        $maLk = trim((string) $this->request->getParameter('ma_lk', false));
        if ($maLk === '') {
            return $this->json(array('success' => false, 'message' => 'Thiếu MALK.'));
        }
        try {
            return $this->json(array('success' => true, 'data' => $this->filePeer->getAuditLogs($maLk)));
        } catch (Throwable $exception) {
            return $this->json(array('success' => false, 'message' => $exception->getMessage()));
        }
    }

    // ---------------------------------------------------------------------
    // Báo cáo, xóa file và hàng đợi quét lại
    // ---------------------------------------------------------------------

    public function download()
    {
        $id = (int) $this->request->getParameter('id', false);
        if ($id <= 0) {
            http_response_code(400);
            echo 'Mã file không hợp lệ.';
            return null;
        }

        try {
            $file = $this->filePeer->getById($id);
            if ($file === false) throw new RuntimeException('File không tồn tại.');
            $storedPath = $this->resolveStoredFilePath($file['file_path']);
        } catch (RuntimeException $exception) {
            http_response_code(404);
            echo $exception->getMessage();
            return null;
        }

        $downloadName = basename((string) $file['ten']);
        if ($downloadName === '' || strtolower(pathinfo($downloadName, PATHINFO_EXTENSION)) !== 'xml') {
            $downloadName = 'file-' . $id . '.xml';
        }
        $asciiName = preg_replace('/[^A-Za-z0-9_.()-]/', '_', $downloadName);
        if ($asciiName === '') $asciiName = 'file-' . $id . '.xml';

        while (ob_get_level() > 0) ob_end_clean();
        header('Content-Type: application/xml; charset=utf-8');
        header('Content-Length: ' . filesize($storedPath));
        header('Content-Disposition: attachment; filename="' . $asciiName
            . '"; filename*=UTF-8\'\'' . rawurlencode($downloadName));
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, no-store, max-age=0');
        readfile($storedPath);
        exit;
    }

    public function getErrorReport()
    {
        $period = trim((string) $this->request->getParameter('period', false));
        $value = trim((string) $this->request->getParameter('date', false));
        $fromValue = trim((string) $this->request->getParameter('from_date', false));
        $toValue = trim((string) $this->request->getParameter('to_date', false));
        $reportScope = trim((string) $this->request->getParameter('report_scope', 'hospital'));
        $reportKhoa = (int) $this->request->getParameter('report_khoa', 0);
        $month = $period === 'month' ? DateTime::createFromFormat('!Y-m', $value) : false;
        $date = $month ? $month->format('Y-m-01') : ($fromValue !== '' ? $fromValue : $this->selectedDate());
        if ($date === null) {
            return $this->json(array('success' => false, 'message' => 'Ngày được chọn không hợp lệ.'));
        }
        try {
            $to = $month ? (clone $month)->modify('+1 month')->format('Y-m-01') : null;
            if (!$month && $toValue !== '') {
                $end = DateTime::createFromFormat('!Y-m-d', $toValue);
                if (!$end) return $this->json(array('success' => false, 'message' => 'Khoảng ngày không hợp lệ.'));
                $to = $end->modify('+1 day')->format('Y-m-d');
            }
            $idKhoa = null;
            if ($reportScope === 'khoa' && $reportKhoa > 0 && isset($_SESSION['AdminType']) && (int) $_SESSION['AdminType'] === 1) {
                $idKhoa = $reportKhoa;
            } elseif (!isset($_SESSION['AdminType']) || (int) $_SESSION['AdminType'] !== 1) {
                $username = isset($_SESSION['sUserName']) ? (string) $_SESSION['sUserName'] : '';
                $idKhoa = $this->filePeer->getCurrentUserKhoa($username);
            }
            $rows = $this->filePeer->getErrorReportRows($date, $to, $idKhoa);
            $files = $this->filePeer->getAllFileReportRows($date, $to, $idKhoa);
            return $this->json(array('success' => true, 'data' => $rows, 'files' => $files));
        } catch (RuntimeException $exception) {
            return $this->json(array('success' => false, 'message' => $exception->getMessage()));
        }
    }

    public function delete()
    {
        if (!$this->validCsrf()) {
            return $this->json(array('success' => false, 'message' => 'Phiên làm việc không hợp lệ. Vui lòng tải lại trang.'));
        }

        $id = (int) $this->request->getParameter('id', false);
        if ($id <= 0) {
            return $this->json(array('success' => false, 'message' => 'Mã file không hợp lệ.'));
        }

        try {
            if ($this->deleteFiles(array($id)) === 0) {
                return $this->json(array('success' => false, 'message' => 'File không tồn tại.'));
            }
        } catch (RuntimeException $exception) {
            return $this->json(array('success' => false, 'message' => $exception->getMessage()));
        }

        return $this->json(array('success' => true, 'message' => 'Xóa file thành công.'));
    }

    public function deleteMany()
    {
        if (!$this->validCsrf()) {
            return $this->json(array('success' => false, 'message' => 'Phiên làm việc không hợp lệ. Vui lòng tải lại trang.'));
        }

        $ids = $this->request->getParameter('ids', false);
        if (!is_array($ids)) {
            $ids = preg_split('/\s*,\s*/', (string) $ids, -1, PREG_SPLIT_NO_EMPTY);
        }
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), function ($id) {
            return $id > 0;
        })));
        if (empty($ids) || count($ids) > 500) {
            return $this->json(array('success' => false, 'message' => 'Danh sách file cần xóa không hợp lệ.'));
        }

        try {
            $deleted = $this->deleteFiles($ids);
        } catch (Throwable $exception) {
            return $this->json(array('success' => false, 'message' => $exception->getMessage()));
        }
        return $this->json(array(
            'success' => true,
            'deleted' => $deleted,
            'message' => 'Đã xóa ' . $deleted . ' file.'
        ));
    }

    public function revalidateAll()
    {
        if (!$this->validCsrf()) {
            return $this->json(array('success' => false, 'message' => 'Phiên làm việc không hợp lệ. Vui lòng tải lại trang.'));
        }
        $date = $this->selectedDate();
        if ($date === null) {
            return $this->json(array('success' => false, 'message' => 'Ngày được chọn không hợp lệ.'));
        }
        try {
            $idKhoa = null;
            if (!isset($_SESSION['AdminType']) || (int) $_SESSION['AdminType'] !== 1) {
                $idKhoa = $this->filePeer->getCurrentUserKhoa((string) ($_SESSION['sUserName'] ?? ''));
            }
            $queued = $this->filePeer->markAllForRevalidation($date, $idKhoa);
            $progress = $this->filePeer->getRevalidationProgress($date);
        } catch (RuntimeException $exception) {
            return $this->json(array('success' => false, 'message' => $exception->getMessage()));
        }
        return $this->json(array(
            'success' => true,
            'queued' => max(0, $queued),
            'total' => $queued >= 0 ? (int) $queued : $progress['remaining'],
            'progress' => $progress,
            'message' => $queued === -1
                ? 'Hàng đợi quét lại đang được xử lý.'
                : ($queued > 0
                ? 'Đã đưa ' . $queued . ' file vào hàng đợi quét lại.'
                : 'Không có file mới cần đưa vào hàng đợi.')
        ));
    }

    public function processRevalidationBatch()
    {
        if (!$this->validCsrf()) {
            return $this->json(array('success' => false, 'message' => 'Phiên làm việc không hợp lệ. Vui lòng tải lại trang.'));
        }

        $date = $this->selectedDate();
        if ($date === null) {
            return $this->json(array('success' => false, 'message' => 'Ngày được chọn không hợp lệ.'));
        }
        try {
            $idKhoa = null;
            if (!isset($_SESSION['AdminType']) || (int) $_SESSION['AdminType'] !== 1) {
                $idKhoa = $this->filePeer->getCurrentUserKhoa((string) ($_SESSION['sUserName'] ?? ''));
            }
            $files = $this->filePeer->claimPendingRevalidation(1, $date, $idKhoa);
            $errors = array();
            foreach ($files as $file) {
                try {
                    $this->revalidateQueuedFile($file);
                } catch (Throwable $exception) {
                    $this->filePeer->updateStorageLocation($file['id'], $file['file_path'], 'failed');
                    $errors[] = $file['ten'] . ': ' . $exception->getMessage();
                }
            }
            $progress = $this->filePeer->getRevalidationProgress($date);
            return $this->json(array(
                'success' => true,
                'processed' => count($files),
                'remaining' => $progress['remaining'],
                'progress' => $progress,
                'errors' => $errors
            ));
        } catch (Throwable $exception) {
            return $this->json(array('success' => false, 'message' => $exception->getMessage()));
        }
    }

    public function revalidationProgress()
    {
        $date = $this->selectedDate();
        if ($date === null) return $this->json(array('success' => false, 'message' => 'Ngày được chọn không hợp lệ.'));
        try {
            return $this->json(array('success' => true, 'progress' => $this->filePeer->getRevalidationProgress($date)));
        } catch (RuntimeException $exception) {
            return $this->json(array('success' => false, 'message' => $exception->getMessage()));
        }
    }

    public function uploadProgress()
    {
        $raw = $this->request->getParameter('files', false);
        $files = null;
        if ($raw !== false && $raw !== null && trim((string) $raw) !== '') {
            $files = json_decode((string) $raw, true);
            if (!is_array($files)) {
                return $this->json(array('success' => false, 'message' => 'Danh sách file không hợp lệ.'));
            }
        }
        try {
            $date = $this->selectedDate();
            if ($date === null) {
                return $this->json(array('success' => false, 'message' => 'Ngày được chọn không hợp lệ.'));
            }
            $remaining = $files !== null
                ? $this->filePeer->countQueuedUploadFiles($files)
                : $this->filePeer->countActiveQueuedUploadFiles($date);
            if ($files === null) {
                return $this->json(array(
                    'success' => true,
                    'remaining' => $remaining,
                    'statistics' => array('processing' => $remaining)
                ));
            }
            $statistics = $date !== null ? $this->filePeer->getRevalidationProgress($date) : array();
            $statistics['processing'] = $remaining;
            $statistics['total'] = (isset($statistics['total']) ? (int) $statistics['total'] : 0) + $remaining;
            return $this->json(array('success' => true, 'remaining' => $remaining, 'statistics' => $statistics));
        } catch (RuntimeException $exception) {
            return $this->json(array('success' => false, 'message' => $exception->getMessage()));
        }
    }

    public function revalidationStream()
    {
        $date = $this->selectedDate();
        if ($date === null) return $this->json(array('success' => false, 'message' => 'Ngày không hợp lệ.'));
        if (session_status() === PHP_SESSION_ACTIVE) session_write_close();
        @set_time_limit(35);
        @ini_set('zlib.output_compression', '0');
        @ini_set('output_buffering', '0');
        if (function_exists('apache_setenv')) @apache_setenv('no-gzip', '1');
        header('Content-Type: text/event-stream; charset=utf-8');
        header('Cache-Control: no-cache, no-store');
        header('X-Accel-Buffering: no');
        header('Connection: keep-alive');
        header('Content-Encoding: none');
        while (ob_get_level() > 0) @ob_end_flush();
        echo ':' . str_repeat(' ', 4096) . "\n\n";
        flush();
        for ($i = 0; $i < 30; $i++) {
            try {
                $progress = $this->filePeer->getRevalidationProgress($date);
                echo "event: progress\n";
                echo 'data: ' . json_encode(array('success' => true, 'progress' => $progress), JSON_UNESCAPED_UNICODE) . "\n\n";
                @ob_flush();
                flush();
                if ((int) ($progress['remaining'] ?? 0) <= 0) break;
            } catch (Throwable $exception) {
                echo "event: error\n";
                echo 'data: ' . json_encode(array('success' => false, 'message' => $exception->getMessage()), JSON_UNESCAPED_UNICODE) . "\n\n";
                flush();
                break;
            }
            sleep(1);
            echo ": heartbeat\n\n";
            flush();
        }
        exit;
    }

    private function revalidateQueuedFile(array $file)
    {
        $storedPath = $this->resolveStoredFilePath($file['file_path']);
        $content = file_get_contents($storedPath);
        if ($content === false) {
            throw new RuntimeException('Không thể đọc file XML để quét lại.');
        }

        $decodedContent = XmlFileDecoder::decodeDanhSachHoSo($content);
        $validation = call_user_func($this->createValidationCallback(), $decodedContent);
        $newStatus = $this->validationHasErrors($validation) ? 'failed' : 'processed';

        $idKhoa = !empty($file['id_khoa']) ? (string) $file['id_khoa'] : 'KHONG_XAC_DINH';
        $khoaOutputPath = $this->filePeer->getKhoaOutputPath($idKhoa);
        $targetDirectory = $this->fileStorage->khoaStatusDateDirectory(
            $idKhoa,
            $newStatus,
            null,
            $khoaOutputPath
        );
        $currentDirectory = realpath(dirname($storedPath));
        $resolvedTarget = realpath($targetDirectory);
        $newPath = $file['file_path'];
        if ($currentDirectory === false || $resolvedTarget === false
            || strcasecmp($currentDirectory, $resolvedTarget) !== 0) {
            $newPath = $this->moveValidatedFile(
                $storedPath,
                $newStatus,
                $validation,
                isset($file['id_khoa']) ? $file['id_khoa'] : null,
                $khoaOutputPath
            );
        } else {
            $newPath = $this->fileStorage->relativeToProject($storedPath);
            $errorPath = $storedPath . '.error.txt';
            if ($newStatus === 'failed') {
                $errorCount = 0;
                foreach ($validation as $result) {
                    $errorCount += isset($result['error_count']) ? (int) $result['error_count'] : 0;
                }
                file_put_contents($errorPath, 'Validation failed with ' . $errorCount . ' error(s).' . PHP_EOL, LOCK_EX);
            } elseif (is_file($errorPath)) {
                @unlink($errorPath);
            }
        }

        $this->filePeer->updateValidationResult($file['id'], $validation);
        if ($newStatus === 'failed') {
            $absoluteFinalPath = $this->resolveStoredFilePath($newPath);
            $organized = XmlErrorArchiveOrganizer::organize(
                $absoluteFinalPath,
                dirname($absoluteFinalPath),
                $validation,
                true,
                $file['ten']
            );
            if (!empty($organized)) {
                $newPath = $this->fileStorage->relativeToProject($organized[0]);
            }
        }
        $this->filePeer->updateStorageLocation($file['id'], $newPath, $newStatus);
    }

    private function deleteFiles(array $ids)
    {
        $moves = array();
        try {
            $files = array();
            foreach ($ids as $id) {
                $file = $this->filePeer->getById((int) $id);
                if ($file === false) {
                    continue;
                }
                if (!empty($file['da_day_cong'])) {
                    throw new RuntimeException('File đã đẩy cổng chỉ được xem, không thể xóa.');
                }
                $files[] = $file;
            }

            foreach ($files as $file) {
                try {
                    $source = $this->resolveStoredFilePath($file['file_path']);
                    $deleteOutputPath = !empty($file['id_khoa'])
                        ? $this->filePeer->getKhoaOutputPath($file['id_khoa'])
                        : null;
                    $move = $this->fileStorage->moveToDeleted($source, $deleteOutputPath);
                    $moves[] = $move;
                } catch (RuntimeException $ignored) {
                    // A missing physical file must not prevent removal of a stale DB row.
                }
            }
            $deleted = $this->filePeer->deleteMany($ids);
            return $deleted;
        } catch (RuntimeException $exception) {
            foreach (array_reverse($moves) as $move) {
                if (is_file($move['destination']) && !file_exists($move['source'])) {
                    @rename($move['destination'], $move['source']);
                }
                if ($move['error_destination'] !== null && is_file($move['error_destination'])) {
                    @rename($move['error_destination'], $move['error_source']);
                }
            }
            throw $exception;
        }
    }

    private function safeFileName($fileName)
    {
        $fileName = trim((string) $fileName);
        if ($fileName === '' || $fileName !== basename($fileName) || strlen($fileName) > 180) {
            return false;
        }
        if (!preg_match('/^[\p{L}\p{N}][\p{L}\p{N} _.()-]*\.xml$/iu', $fileName)) {
            return false;
        }
        return $fileName;
    }

    private function createValidationCallback()
    {
        $rulePeer = new XmlValidationRulePeer();
        $apiService = new ApiValidationService(new ApiValidationConfigPeer());
        $tableLookupService = new TableLookupService();
        return function (array $decodedContent) use ($rulePeer, $apiService, $tableLookupService) {
            return XmlRulesValidator::validate(
                $decodedContent,
                $rulePeer->getRulesForValidation(),
                $apiService,
                $tableLookupService
            );
        };
    }

    private function validationHasErrors(array $validation)
    {
        foreach ($validation as $result) {
            if (isset($result['error_count']) && (int) $result['error_count'] > 0) {
                return true;
            }
        }
        return false;
    }

    private function moveValidatedFile(
        $source,
        $status,
        array $validation,
        $idKhoa = null,
        $configuredOutputPath = null
    )
    {
        return $this->fileStorage->moveValidatedFile(
            $source,
            $status,
            $validation,
            $idKhoa,
            $configuredOutputPath
        );
    }

    private function resolveStoredFilePath($filePath)
    {
        return $this->fileStorage->resolveStoredFilePath($filePath);
    }

    // ---------------------------------------------------------------------
    // Helper dùng chung cho session, request và response
    // ---------------------------------------------------------------------

    private function currentUsername()
    {
        return !empty($_SESSION['sUserName']) ? (string) $_SESSION['sUserName'] : '';
    }

    private function currentUploadUsername()
    {
        $username = $this->currentUsername();
        return $username !== ''
            ? $username
            : (isset($_SESSION['username']) ? (string) $_SESSION['username'] : '');
    }

    private function currentUserKhoa($fromSession = false)
    {
        if ($fromSession) {
            return isset($_SESSION['id_khoa']) && $_SESSION['id_khoa'] !== ''
                ? (string) $_SESSION['id_khoa']
                : null;
        }

        $username = $this->currentUsername();
        return $username === '' ? null : $this->filePeer->getCurrentUserKhoa($username);
    }

    private function validCsrf()
    {
        $token = $this->request->getParameter('csrf_token', false);
        return !empty($_SESSION['xml_file_csrf']) && is_string($token)
            && hash_equals($_SESSION['xml_file_csrf'], $token);
    }

    private function selectedDate()
    {
        $value = trim((string) $this->request->getParameter('date', false));
        $date = DateTime::createFromFormat('!Y-m-d', $value);
        return $date && $date->format('Y-m-d') === $value ? $value : null;
    }

    private function uploadErrorMessage($code)
    {
        if ($code === UPLOAD_ERR_INI_SIZE || $code === UPLOAD_ERR_FORM_SIZE) {
            return 'File vượt quá dung lượng cho phép.';
        }
        if ($code === UPLOAD_ERR_NO_FILE) {
            return 'Vui lòng chọn file XML.';
        }
        return 'Tải file thất bại (mã lỗi ' . (int) $code . ').';
    }

    private function json($data)
    {
        return $this->request->json_response(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}
