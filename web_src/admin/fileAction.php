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
require_once __DIR__ . '/../bean/HeThongPeer.php';
require_once __DIR__ . '/../bean/LogPeer.php';

class fileAction
{
    protected $request;
    protected $filePeer;
    private $maxFileSize = 5242880; // 5 MB

    public function __construct()
    {
        $this->request = new Request();
        $this->filePeer = new FilePeer();
    }

    public function index()
    {
        $currentKhoa = null;
        if (!empty($_SESSION['sUserName'])) {
            global $connect;
            $db = new db_mysql();
            $db->connect();
            $db->selectdb();
            $statement = $connect->prepare(
                'SELECT id_khoa FROM `user` WHERE username = ? LIMIT 1'
            );
            $username = $_SESSION['sUserName'];
            $statement->bind_param('s', $username);
            $statement->execute();
            if ($row = $statement->get_result()->fetch_assoc()) {
                $currentKhoa = $row['id_khoa'];
            }
            $statement->close();
        }
        $this->request->setAttribute('currentUserKhoa', $currentKhoa);
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

        try {
            $queuedName = $this->queueUploadedXml(
                $upload['tmp_name'],
                $fileName,
                $this->request->getParameter('id_khoa')
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

    public function pushPortal()
    {
        $ids = json_decode((string) $this->request->getParameter('ids', false), true);
        if (!is_array($ids) || !$ids) return $this->json(array('success' => false, 'message' => 'Vui lòng chọn file cần đẩy cổng.'));
        $done = 0; $skipped = 0;
        try {
            $root = dirname(__DIR__, 2);
            // Files successfully pushed to the portal are archived by the
            // operation date, and are no longer part of the active file list.
            $archiveDate = date('Ymd');
            $archiveDirectory = $root . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR
                . 'xml' . DIRECTORY_SEPARATOR . 'portal' . DIRECTORY_SEPARATOR . $archiveDate;
            if (!is_dir($archiveDirectory) && !mkdir($archiveDirectory, 0775, true) && !is_dir($archiveDirectory)) {
                throw new RuntimeException('Không thể tạo thư mục lưu file đã đẩy cổng.');
            }
            foreach ($this->filePeer->getPortalFilesByIds($ids) as $file) {
                $path = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $file['file_path']);
                if (!is_file($path)) { $skipped++; continue; }
                $content = file_get_contents($path); $decoded = XmlFileDecoder::decodeDanhSachHoSo($content);
                $decodedContent = json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                if ($decodedContent === false) { $skipped++; continue; }
                $values = XmlFileDecoder::extractValuesFromFileType($decoded, 'XML1', array('CCCD','MA_BN','HO_TEN','NGAY_SINH','MA_THE_BHYT'));
                $cccd = trim((string) ($values['MA_BN'] ?? ''));
                if ($cccd === '') { $skipped++; continue; }
                global $connect; $connect->begin_transaction();
                $q = $connect->prepare('INSERT IGNORE INTO cong_benhnhan (cccd, hoten, namsinh, ma_the_bhyt) VALUES (?, ?, ?, ?)');
                $name = (string) ($values['HO_TEN'] ?? ''); $birth = (string) ($values['NGAY_SINH'] ?? ''); $card = (string) ($values['MA_THE_BHYT'] ?? '');
                $q->bind_param('ssss', $cccd, $name, $birth, $card); $q->execute(); $q->close();
                $q = $connect->prepare('INSERT IGNORE INTO cong_hoso (cccd, malk, ten_file, noidung, nguoi_day, id_khoa) VALUES (?, ?, ?, ?, ?, ?)');
                $user = isset($_SESSION['sUserName']) ? $_SESSION['sUserName'] : ''; $khoa = isset($_SESSION['id_khoa']) ? $_SESSION['id_khoa'] : null;
                $q->bind_param('ssssss', $cccd, $file['ma_lk'], $file['ten'], $decodedContent, $user, $khoa); $q->execute(); $inserted = $q->affected_rows; $q->close();
                if ($inserted > 0) {
                    $archiveName = basename($path);
                    $archivePath = $archiveDirectory . DIRECTORY_SEPARATOR . $archiveName;
                    if (is_file($archivePath)) {
                        $archiveName = pathinfo($archiveName, PATHINFO_FILENAME) . '_' . $file['id'] . '.' . pathinfo($archiveName, PATHINFO_EXTENSION);
                        $archivePath = $archiveDirectory . DIRECTORY_SEPARATOR . $archiveName;
                    }
                    if (!rename($path, $archivePath)) {
                        throw new RuntimeException('Không thể chuyển file vào thư mục lưu theo ngày.');
                    }
                    $relativePath = 'storage/xml/portal/' . $archiveDate . '/' . $archiveName;
                    $this->filePeer->updateStorageLocation($file['id'], $relativePath, 'processed');
                    $this->filePeer->markPortalPushed($file['id']);
                    $log = new Log();
                    $log->set('chucnang', 'Đẩy cổng');
                    $log->set(
                        'noidung',
                        'File: ' . $file['ten'] . ' | MALK: ' . $file['ma_lk']
                        . ' | Đường dẫn mới: ' . $relativePath
                    );
                    $log->set('noidungcu', 'Đường dẫn cũ: ' . $file['file_path']);
                    (new LogPeer())->ghiLog($log);
                    $done++;
                } else $skipped++;
                $connect->commit();
            }
            return $this->json(array('success' => true, 'done' => $done, 'skipped' => $skipped));
        } catch (Throwable $e) { if (isset($connect) && $connect instanceof mysqli) @$connect->rollback(); return $this->json(array('success'=>false,'message'=>$e->getMessage())); }
    }

    public function getAuditLogs()
    {
        $maLk = trim((string) $this->request->getParameter('ma_lk', false));
        if ($maLk === '') return $this->json(array('success' => false, 'message' => 'Thiếu MALK.'));
        try { return $this->json(array('success' => true, 'data' => $this->filePeer->getAuditLogs($maLk))); }
        catch (Throwable $exception) { return $this->json(array('success' => false, 'message' => $exception->getMessage())); }
    }

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
        $month = $period === 'month' ? DateTime::createFromFormat('!Y-m', $value) : false;
        $date = $month ? $month->format('Y-m-01') : $this->selectedDate();
        if ($date === null) {
            return $this->json(array('success' => false, 'message' => 'Ngày được chọn không hợp lệ.'));
        }
        try {
            $to = $month ? (clone $month)->modify('+1 month')->format('Y-m-01') : null;
            $rows = $this->filePeer->getErrorReportRows($date, $to);
            $files = $this->filePeer->getAllFileReportRows($date, $to);
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
            $queued = $this->filePeer->markAllForRevalidation($date);
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
            $files = $this->filePeer->claimPendingRevalidation(2, $date);
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

        $projectRoot = dirname(__DIR__, 2);
        $targetDirectory = $projectRoot . DIRECTORY_SEPARATOR . 'storage'
            . DIRECTORY_SEPARATOR . 'xml' . DIRECTORY_SEPARATOR . $newStatus
            . DIRECTORY_SEPARATOR . date('Ymd');
        $currentDirectory = realpath(dirname($storedPath));
        $resolvedTarget = realpath($targetDirectory);
        $newPath = $file['file_path'];
        if ($currentDirectory === false || $resolvedTarget === false
            || strcasecmp($currentDirectory, $resolvedTarget) !== 0) {
            $newPath = $this->moveValidatedFile($storedPath, $newStatus, $validation);
        } else {
            $newPath = str_replace('\\', '/', substr($storedPath, strlen($projectRoot) + 1));
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
                $newPath = str_replace('\\', '/', substr($organized[0], strlen(dirname(__DIR__, 2)) + 1));
            }
        }
        $this->filePeer->updateStorageLocation($file['id'], $newPath, $newStatus);
    }

    private function deleteFiles(array $ids)
    {
        $moves = array();
        try {
            foreach ($ids as $id) {
                $file = $this->filePeer->getById((int) $id);
                if ($file === false) {
                    continue;
                }
                try {
                    $source = $this->resolveStoredFilePath($file['file_path']);
                    $move = $this->moveFileToDeleted($source);
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

    private function moveFileToDeleted($source)
    {
        $directory = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage'
            . DIRECTORY_SEPARATOR . 'xml' . DIRECTORY_SEPARATOR . 'deleted';
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException('Không thể tạo thư mục lưu file đã xóa.');
        }
        $destination = $directory . DIRECTORY_SEPARATOR . basename($source);
        if (file_exists($destination)) {
            $destination = $directory . DIRECTORY_SEPARATOR . pathinfo($source, PATHINFO_FILENAME)
                . '_' . date('Ymd_His') . '_' . substr(uniqid('', true), -6) . '.xml';
        }
        if (!rename($source, $destination)) {
            throw new RuntimeException('Không thể di chuyển file vào thư mục deleted.');
        }
        touch($destination);

        $errorSource = $source . '.error.txt';
        $errorDestination = null;
        if (is_file($errorSource)) {
            $errorDestination = $destination . '.error.txt';
            if (!rename($errorSource, $errorDestination)) {
                @rename($destination, $source);
                throw new RuntimeException('Không thể di chuyển thông tin lỗi của file.');
            }
            touch($errorDestination);
        }
        return array(
            'source' => $source,
            'destination' => $destination,
            'error_source' => $errorSource,
            'error_destination' => $errorDestination
        );
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

    private function queueUploadedXml($temporaryUploadPath, $fileName, $postedKhoa = null)
    {
        global $connect;
        $directory = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'xml';
        if (!is_dir($directory) || !is_writable($directory)) {
            throw new RuntimeException('Thư mục hàng đợi XML không thể ghi dữ liệu.');
        }
        $destination = $directory . DIRECTORY_SEPARATOR . $fileName;
        if (file_exists($destination)) {
            $destination = $directory . DIRECTORY_SEPARATOR . pathinfo($fileName, PATHINFO_FILENAME)
                . '_' . date('Ymd_His') . '_' . substr(uniqid('', true), -6) . '.xml';
        }
        $stagingPath = $destination . '.uploading.' . substr(uniqid('', true), -8);
        if (!move_uploaded_file($temporaryUploadPath, $stagingPath)) {
            throw new RuntimeException('Không thể ghi file tải lên vào hàng đợi.');
        }
        if (!rename($stagingPath, $destination)) {
            @unlink($stagingPath);
            throw new RuntimeException('Không thể hoàn tất việc đưa file vào hàng đợi.');
        }

        $userKhoa = ($postedKhoa !== null && $postedKhoa !== '')
            ? (int) $postedKhoa : null;
        $currentUsername = !empty($_SESSION['sUserName'])
            ? $_SESSION['sUserName']
            : (isset($_SESSION['username']) ? $_SESSION['username'] : '');
        if ($currentUsername !== '') {
            $db = new db_mysql();
            $db->connect();
            $result = $db->query(
                "SELECT id_khoa FROM `user` WHERE username='"
                . addslashes($currentUsername) . "' LIMIT 1"
            );
            if ($row = $db->fetch_Array($result)) {
                $userKhoa = $row['id_khoa'];
            }
        }
        $queueStatement = $connect->prepare(
            'INSERT INTO `xml_upload_queue` (file_name, id_khoa, username) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE id_khoa = VALUES(id_khoa), username = VALUES(username)'
        );
        $queuedFileName = basename($destination);
        $queueKhoa = $userKhoa === null ? null : (string) $userKhoa;
        $queueStatement->bind_param('sss', $queuedFileName, $queueKhoa, $currentUsername);
        $queueStatement->execute();
        $queueStatement->close();

        return basename($destination);
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

    private function moveValidatedFile($source, $status, array $validation)
    {
        $projectRoot = dirname(__DIR__, 2);
        $directory = $projectRoot . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR
            . 'xml' . DIRECTORY_SEPARATOR . $status . DIRECTORY_SEPARATOR . date('Ymd');
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException('Không thể tạo thư mục trạng thái file XML.');
        }

        $destination = $directory . DIRECTORY_SEPARATOR . basename($source);
        if (file_exists($destination)) {
            $destination = $directory . DIRECTORY_SEPARATOR . pathinfo($source, PATHINFO_FILENAME)
                . '_' . date('Ymd_His') . '_' . substr(uniqid('', true), -6) . '.xml';
        }
        if (!rename($source, $destination)) {
            throw new RuntimeException('Không thể chuyển file sang trạng thái ' . $status . '.');
        }

        $oldErrorPath = $source . '.error.txt';
        if ($status === 'failed') {
            $errorCount = 0;
            foreach ($validation as $result) {
                $errorCount += isset($result['error_count']) ? (int) $result['error_count'] : 0;
            }
            if (is_file($oldErrorPath)) {
                @unlink($oldErrorPath);
            }
            file_put_contents(
                $destination . '.error.txt',
                'Validation failed with ' . $errorCount . ' error(s).' . PHP_EOL,
                LOCK_EX
            );
        } elseif (is_file($oldErrorPath)) {
            @unlink($oldErrorPath);
        }

        return str_replace('\\', '/', substr($destination, strlen($projectRoot) + 1));
    }

    private function resolveStoredFilePath($filePath)
    {
        $projectRoot = dirname(__DIR__, 2);
        $storageRoot = realpath($projectRoot . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'xml');
        if ($storageRoot === false || !is_string($filePath) || trim($filePath) === '') {
            throw new RuntimeException('Đường dẫn file XML không hợp lệ.');
        }

        $isAbsolute = preg_match('/^[A-Za-z]:[\\\\\/]/', $filePath) === 1
            || substr($filePath, 0, 1) === '/';
        $candidate = $isAbsolute
            ? $filePath
            : $projectRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $filePath);
        $resolved = realpath($candidate);
        $storagePrefix = rtrim($storageRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if ($resolved === false || strncasecmp($resolved, $storagePrefix, strlen($storagePrefix)) !== 0
            || !is_file($resolved)) {
            throw new RuntimeException('Không tìm thấy file XML trong thư mục lưu trữ.');
        }
        return $resolved;
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
