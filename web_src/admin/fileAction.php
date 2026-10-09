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
require_once __DIR__ . '/../common/XmlErrorArchiveOrganizer.php';
require_once __DIR__ . '/../bean/HeThongPeer.php';

class fileAction
{
    private $request;
    private $filePeer;
    private $maxFileSize = 5242880; // 5 MB

    public function __construct()
    {
        $this->request = new Request();
        $this->filePeer = new FilePeer();
    }

    public function index()
    {
        if (empty($_SESSION['xml_file_csrf'])) {
            $_SESSION['xml_file_csrf'] = bin2hex(random_bytes(32));
        }

        $this->request->setAttribute('xmlFileCsrf', $_SESSION['xml_file_csrf']);
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
        try {
            return $this->json(array('data' => $this->filePeer->getList()));
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

        $content = file_get_contents($upload['tmp_name']);
        // The decoder validates the envelope and every decoded XML document.
        // Do not parse the same envelope with DOMDocument first.
        $xmlError = $content === false ? 'Không thể đọc nội dung file XML.' : '';
        if ($xmlError !== '') {
            return $this->json(array('success' => false, 'message' => 'Nội dung XML không hợp lệ: ' . $xmlError));
        }

        $storedFile = null;
        try {
            $storedFile = $this->storeXmlContent($fileName, $content, 'processed');
            $importService = new XmlFileImportService(
                $this->filePeer,
                $this->maxFileSize,
                $this->createValidationCallback()
            );
            $result = $importService->importContent(
                $fileName,
                $content,
                $upload['size'],
                $storedFile['relative_path']
            );
            if ($result['status'] === 'duplicate') {
                return $this->json(array(
                    'success' => false,
                    'message' => 'Hồ sơ có MA_LK ' . $result['ma_lk'] . ' đã tồn tại.'
                ));
            }
            if (isset($result['processing_status']) && $result['processing_status'] === 'failed') {
                $failedFile = $this->moveStoredFileToFailed(
                    $storedFile['absolute_path'],
                    'File không đạt validation.'
                );
                $organized = XmlErrorArchiveOrganizer::organize(
                    $failedFile['absolute_path'],
                    dirname($failedFile['absolute_path']),
                    $result['validation'],
                    true,
                    $result['name']
                );
                $canonicalPath = !empty($organized) ? $organized[0] : $failedFile['absolute_path'];
                $canonicalRelative = str_replace('\\', '/', substr($canonicalPath, strlen(dirname(__DIR__, 2)) + 1));
                $this->filePeer->updateStorageLocation($result['id'], $canonicalRelative, 'failed');
                $this->archiveReplacedFile($result, $canonicalPath);
                return $this->json(array(
                    'success' => false,
                    'message' => 'File đã được lưu vào thư mục failed vì không đạt validation.'
                ));
            }
            $this->archiveReplacedFile($result, $storedFile['absolute_path']);
        } catch (RuntimeException $exception) {
            if ($storedFile !== null && is_file($storedFile['absolute_path'])) {
                $this->moveStoredFileToFailed($storedFile['absolute_path'], $exception->getMessage());
            }
            return $this->json(array('success' => false, 'message' => $exception->getMessage()));
        }

        return $this->json(array('success' => true, 'message' => 'Đã lưu file XML vào cơ sở dữ liệu.'));
    }

    public function getContent()
    {
        $id = (int) $this->request->getParameter('id', false);
        if ($id <= 0) {
            return $this->json(array('success' => false, 'message' => 'Mã file không hợp lệ.'));
        }

        try {
            $file = $this->filePeer->getById($id);
        } catch (RuntimeException $exception) {
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
            $rulePeer = new XmlValidationRulePeer();
            $apiService = new ApiValidationService(new ApiValidationConfigPeer());
            $tableLookupService = new TableLookupService();
            $validation = XmlRulesValidator::validate(
                $decodedContent,
                $rulePeer->getRulesForValidation(),
                $apiService,
                $tableLookupService
            );
            $this->filePeer->updateValidationResult($id, $validation);
            $newStatus = $this->validationHasErrors($validation) ? 'failed' : 'processed';
            if ($newStatus !== $file['processing_status']) {
                $newPath = $this->moveValidatedFile($storedPath, $newStatus, $validation);
                if ($newStatus === 'failed') {
                    $absoluteNewPath = $this->resolveStoredFilePath($newPath);
                    $organized = XmlErrorArchiveOrganizer::organize(
                        $absoluteNewPath,
                        dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'xml' . DIRECTORY_SEPARATOR . 'failed',
                        $validation,
                        true,
                        $file['ten']
                    );
                    if (!empty($organized)) {
                        $newPath = str_replace('\\', '/', substr($organized[0], strlen(dirname(__DIR__, 2)) + 1));
                    }
                }
                $this->filePeer->updateStorageLocation($id, $newPath, $newStatus);
            }
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
        try {
            $rows = $this->filePeer->getErrorReportRows();
            return $this->json(array('success' => true, 'data' => $rows));
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
        } catch (RuntimeException $exception) {
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
        try {
            $queued = $this->filePeer->markAllForRevalidation();
        } catch (RuntimeException $exception) {
            return $this->json(array('success' => false, 'message' => $exception->getMessage()));
        }
        return $this->json(array(
            'success' => true,
            'queued' => max(0, $queued),
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

        try {
            $files = $this->filePeer->claimPendingRevalidation(2);
            $errors = array();
            foreach ($files as $file) {
                try {
                    $this->revalidateQueuedFile($file);
                } catch (Throwable $exception) {
                    $this->filePeer->updateStorageLocation($file['id'], $file['file_path'], 'failed');
                    $errors[] = $file['ten'] . ': ' . $exception->getMessage();
                }
            }
            return $this->json(array(
                'success' => true,
                'processed' => count($files),
                'remaining' => $this->filePeer->countPendingRevalidation(),
                'errors' => $errors
            ));
        } catch (Throwable $exception) {
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
            . DIRECTORY_SEPARATOR . 'xml' . DIRECTORY_SEPARATOR . $newStatus;
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
                dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'xml' . DIRECTORY_SEPARATOR . 'failed',
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

    private function storeXmlContent($fileName, $content, $bucket)
    {
        $projectRoot = dirname(__DIR__, 2);
        $directory = $projectRoot . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR
            . 'xml' . DIRECTORY_SEPARATOR . $bucket;
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException('Không thể tạo thư mục lưu file XML.');
        }

        $destination = $directory . DIRECTORY_SEPARATOR . $fileName;
        if (file_exists($destination)) {
            $base = pathinfo($fileName, PATHINFO_FILENAME);
            $extension = pathinfo($fileName, PATHINFO_EXTENSION);
            $destination = $directory . DIRECTORY_SEPARATOR . $base . '_' . date('Ymd_His')
                . '_' . substr(uniqid('', true), -6) . '.' . $extension;
        }
        if (file_put_contents($destination, $content, LOCK_EX) === false) {
            throw new RuntimeException('Không thể lưu file XML vào ổ đĩa.');
        }

        return array(
            'absolute_path' => $destination,
            'relative_path' => str_replace('\\', '/', substr($destination, strlen($projectRoot) + 1))
        );
    }

    private function moveStoredFileToFailed($path, $message)
    {
        $failed = $this->storeXmlContent(basename($path), file_get_contents($path), 'failed');
        unlink($path);
        file_put_contents($failed['absolute_path'] . '.error.txt', $message . PHP_EOL, LOCK_EX);
        return $failed;
    }

    private function archiveReplacedFile(array $result, $newPath)
    {
        if ($result['status'] !== 'reimported' || empty($result['old_file_path'])) {
            return;
        }
        try {
            $oldPath = $this->resolveStoredFilePath($result['old_file_path']);
            if (strcasecmp($oldPath, $newPath) !== 0) {
                $this->moveFileToDeleted($oldPath);
            }
        } catch (RuntimeException $ignored) {
            // The DB already points to the new file; a missing old file is harmless.
        }
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
            . 'xml' . DIRECTORY_SEPARATOR . $status;
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
