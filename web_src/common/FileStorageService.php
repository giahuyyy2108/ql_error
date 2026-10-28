<?php

/**
 * Các thao tác đường dẫn và lưu trữ file XML dùng chung.
 *
 * Service này chỉ quản lý file system, không truy cập database.
 */
class FileStorageService
{
    private $projectRoot;
    private $storageRoot;
    private $additionalStorageRoots = array();

    public function __construct($projectRoot = null, $storageRoot = null, array $additionalStorageRoots = array())
    {
        $this->projectRoot = $projectRoot !== null
            ? rtrim($projectRoot, '\\/')
            : dirname(__DIR__, 2);
        $configuredStorageRoot = $storageRoot !== null ? trim((string) $storageRoot) : '';
        if ($configuredStorageRoot === '') {
            $this->storageRoot = $this->projectRoot
                . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'xml';
        } else {
            $this->storageRoot = $this->normalizeRoot($configuredStorageRoot);
        }

        $this->addStorageRoots($additionalStorageRoots);
    }

    public function addStorageRoots(array $roots)
    {
        foreach ($roots as $root) {
            $root = trim((string) $root);
            if ($root === '') continue;

            $normalized = $this->normalizeRoot($root);
            if (strcasecmp($normalized, $this->storageRoot) === 0) continue;

            $exists = false;
            foreach ($this->additionalStorageRoots as $configuredRoot) {
                if (strcasecmp($normalized, $configuredRoot) === 0) {
                    $exists = true;
                    break;
                }
            }
            if (!$exists) {
                $this->additionalStorageRoots[] = $normalized;
            }
        }
    }

    public function resolveStoredFilePath($filePath)
    {
        if (!is_string($filePath) || trim($filePath) === '') {
            throw new RuntimeException('Đường dẫn file XML không hợp lệ.');
        }

        $allowedRoots = array();
        $storageRoot = realpath($this->storageRoot);
        if ($storageRoot !== false) {
            $allowedRoots[] = $storageRoot;
        }
        foreach ($this->additionalStorageRoots as $additionalRoot) {
            $resolvedAdditionalRoot = realpath($additionalRoot);
            if ($resolvedAdditionalRoot === false) continue;
            $alreadyAllowed = false;
            foreach ($allowedRoots as $allowedRoot) {
                if (strcasecmp($allowedRoot, $resolvedAdditionalRoot) === 0) {
                    $alreadyAllowed = true;
                    break;
                }
            }
            if (!$alreadyAllowed) {
                $allowedRoots[] = $resolvedAdditionalRoot;
            }
        }

        // Tương thích các hồ sơ cũ được lưu trước khi chuyển storage sang UNC.
        // Chỉ cho phép thêm đúng thư mục storage/xml cũ, không mở rộng ra toàn project.
        $legacyStorageRoot = realpath(
            $this->projectRoot . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'xml'
        );
        if ($legacyStorageRoot !== false) {
            $alreadyAllowed = false;
            foreach ($allowedRoots as $allowedRoot) {
                if (strcasecmp($allowedRoot, $legacyStorageRoot) === 0) {
                    $alreadyAllowed = true;
                    break;
                }
            }
            if (!$alreadyAllowed) {
                $allowedRoots[] = $legacyStorageRoot;
            }
        }

        $filePath = trim($filePath);
        $isUnc = substr($filePath, 0, 2) === '\\\\'
            || substr($filePath, 0, 2) === '//';
        $isAbsolute = $isUnc
            || preg_match('/^[A-Za-z]:[\\\\\/]/', $filePath) === 1
            || substr($filePath, 0, 1) === '/';

        if (DIRECTORY_SEPARATOR === '\\') {
            $filePath = str_replace('/', '\\', $filePath);
        } else {
            $filePath = str_replace('\\', '/', $filePath);
        }

        $candidate = $isAbsolute
            ? $filePath
            : $this->projectRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $filePath);
        $resolved = realpath($candidate);
        if ($resolved === false || !is_file($resolved)) {
            // A network share can be accessible to the CLI user but unavailable
            // to the Windows account running Apache. Report that separately
            // from an XML file that is actually missing.
            $storagePrefix = rtrim($this->storageRoot, '\\/') . DIRECTORY_SEPARATOR;
            if ($storageRoot === false
                && strncasecmp($candidate, $storagePrefix, strlen($storagePrefix)) === 0
            ) {
                throw new RuntimeException(
                    'Không thể truy cập thư mục lưu trữ XML. '
                    . 'Kiểm tra kết nối và quyền truy cập thư mục của tài khoản chạy Apache/PHP.'
                );
            }
            throw new RuntimeException('Không tìm thấy file XML trong thư mục lưu trữ.');
        }

        $isAllowed = false;
        foreach ($allowedRoots as $allowedRoot) {
            $allowedPrefix = rtrim($allowedRoot, '\\/') . DIRECTORY_SEPARATOR;
            if (strncasecmp($resolved, $allowedPrefix, strlen($allowedPrefix)) === 0) {
                $isAllowed = true;
                break;
            }
        }
        if (!$isAllowed) {
            throw new RuntimeException('Không tìm thấy file XML trong thư mục lưu trữ.');
        }

        return $resolved;
    }

    public function datedDirectory($baseDirectory, $date = null)
    {
        $date = $date !== null ? $date : date('Ymd');
        $directory = rtrim($baseDirectory, '\\/') . DIRECTORY_SEPARATOR . $date;
        $this->ensureDirectory($directory, 'Không thể tạo thư mục lưu theo ngày: ' . $directory);
        return $directory;
    }

    public function storageDirectory($subDirectory = null)
    {
        if ($subDirectory === null || trim((string) $subDirectory) === '') {
            return $this->storageRoot;
        }

        return $this->storageRoot . DIRECTORY_SEPARATOR . trim((string) $subDirectory, '\\/');
    }

    public function statusDirectory($status, $date = null)
    {
        return $this->datedDirectory($this->storageDirectory($status), $date);
    }

    public function khoaDateDirectory($idKhoa, $date = null)
    {
        return $this->khoaStatusDateDirectory($idKhoa, 'processed', $date);
    }

    public function khoaStatusDateDirectory($idKhoa, $status, $date = null, $configuredOutputPath = null)
    {
        $idKhoa = trim((string) $idKhoa);
        if ($idKhoa === '' || strpos($idKhoa, '/') !== false || strpos($idKhoa, '\\') !== false
            || $idKhoa === '.' || $idKhoa === '..') {
            throw new RuntimeException('Mã khoa gốc không hợp lệ để tạo thư mục lưu XML.');
        }

        $status = trim((string) $status);
        if (!in_array($status, array('processed', 'failed'), true)) {
            throw new RuntimeException('Invalid XML storage status.');
        }

        $configuredOutputPath = trim((string) $configuredOutputPath);
        if ($configuredOutputPath !== '') {
            $configuredRoot = $this->normalizeRoot($configuredOutputPath);
            $normalizedConfiguredRoot = str_replace('\\', '/', rtrim($configuredRoot, '\\/'));
            $segments = preg_split('#/+#', $normalizedConfiguredRoot);
            $lastSegment = $segments ? (string) end($segments) : '';
            $khoaRoot = strcasecmp($lastSegment, $idKhoa) === 0
                ? $configuredRoot
                : rtrim($configuredRoot, '\\/') . DIRECTORY_SEPARATOR . $idKhoa;
        } else {
            $khoaRoot = $this->storageDirectory($idKhoa);
        }

        return $this->datedDirectory($khoaRoot . DIRECTORY_SEPARATOR . $status, $date);
    }

    public function uniqueDestination($directory, $fileName)
    {
        $destination = rtrim($directory, '\\/') . DIRECTORY_SEPARATOR . basename($fileName);
        if (!file_exists($destination)) {
            return $destination;
        }

        $base = pathinfo($fileName, PATHINFO_FILENAME);
        $extension = pathinfo($fileName, PATHINFO_EXTENSION);
        $suffix = date('Ymd_His') . '_' . substr(uniqid('', true), -6);
        return rtrim($directory, '\\/') . DIRECTORY_SEPARATOR . $base . '_' . $suffix
            . ($extension !== '' ? '.' . $extension : '');
    }

    public function relativeToProject($absolutePath)
    {
        $normalizedRoot = str_replace('\\', '/', rtrim($this->projectRoot, '\\/')) . '/';
        $normalizedPath = str_replace('\\', '/', $absolutePath);
        return strpos($normalizedPath, $normalizedRoot) === 0
            ? substr($normalizedPath, strlen($normalizedRoot))
            : $normalizedPath;
    }

    public function moveValidatedFile(
        $source,
        $status,
        array $validation,
        $idKhoa = null,
        $configuredOutputPath = null
    )
    {
        $directory = $this->khoaStatusDateDirectory(
            trim((string) $idKhoa) !== '' ? $idKhoa : 'KHONG_XAC_DINH',
            $status === 'failed' ? 'failed' : 'processed',
            null,
            $configuredOutputPath
        );
        $destination = $this->uniqueDestination($directory, basename($source));
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

        return $this->relativeToProject($destination);
    }

    public function moveToDeleted($source, $configuredOutputPath = null)
    {
        $configuredOutputPath = trim((string) $configuredOutputPath);
        $deleteRoot = $configuredOutputPath !== ''
            ? $this->normalizeRoot($configuredOutputPath)
            : $this->storageRoot;
        $directory = $deleteRoot . DIRECTORY_SEPARATOR . 'deleted';
        $this->ensureDirectory($directory, 'Không thể tạo thư mục lưu file đã xóa.');

        $destination = $this->uniqueDestination($directory, basename($source));
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

    public function copyToPortal($source, $fileId, $archiveDate = null, $portalRoot = null)
    {
        $archiveDate = $archiveDate !== null ? $archiveDate : date('Ymd');
        $portalRoot = trim((string) $portalRoot);
        $baseDirectory = $portalRoot !== ''
            ? $this->normalizeRoot($portalRoot)
            : $this->storageRoot . DIRECTORY_SEPARATOR . 'portal';
        $directory = $this->datedDirectory(
            $baseDirectory,
            $archiveDate
        );

        $archiveName = basename($source);
        $archivePath = $directory . DIRECTORY_SEPARATOR . $archiveName;
        if (is_file($archivePath)) {
            $extension = pathinfo($archiveName, PATHINFO_EXTENSION);
            $archiveName = pathinfo($archiveName, PATHINFO_FILENAME) . '_' . (int) $fileId
                . ($extension !== '' ? '.' . $extension : '');
            $archivePath = $directory . DIRECTORY_SEPARATOR . $archiveName;
        }
        if (!copy($source, $archivePath)) {
            throw new RuntimeException('Không thể chuyển file vào thư mục lưu theo ngày.');
        }

        return array(
            'name' => $archiveName,
            'absolute_path' => $archivePath,
            'relative_path' => $this->relativeToProject($archivePath)
        );
    }

    private function ensureDirectory($directory, $errorMessage)
    {
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException($errorMessage);
        }
    }

    private function normalizeRoot($root)
    {
        $root = trim((string) $root);
        $isUnc = substr($root, 0, 2) === '\\\\' || substr($root, 0, 2) === '//';
        $isAbsolute = $isUnc
            || preg_match('/^[A-Za-z]:[\\\\\/]/', $root) === 1
            || substr($root, 0, 1) === '/';

        if (DIRECTORY_SEPARATOR === '\\') {
            $root = str_replace('/', '\\', $root);
        } else {
            $root = str_replace('\\', '/', $root);
        }
        if (!$isAbsolute) {
            $root = $this->projectRoot . DIRECTORY_SEPARATOR . trim($root, '\\/');
        }
        if (preg_match('/^[A-Za-z]:[\\\\\/]*$/', $root) === 1) {
            return strtoupper(substr($root, 0, 1)) . ':' . DIRECTORY_SEPARATOR;
        }
        return rtrim($root, '\\/');
    }
}
