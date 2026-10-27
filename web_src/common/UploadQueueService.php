<?php

require_once __DIR__ . '/FileStorageService.php';

/**
 * Quản lý bước nhận file upload trước khi watcher xử lý.
 *
 * File staging không có đuôi .xml nên watcher chỉ nhìn thấy file sau khi
 * metadata người upload/khoa đã được lưu vào xml_upload_queue.
 */
class UploadQueueService
{
    private $filePeer;
    private $storage;
    private $queueDirectory;
    private $lockDirectory;

    public function __construct(FilePeer $filePeer, FileStorageService $storage = null)
    {
        $this->filePeer = $filePeer;
        $this->storage = $storage !== null ? $storage : new FileStorageService();
        $this->queueDirectory = $this->storage->storageDirectory();
        $this->lockDirectory = dirname($this->queueDirectory) . DIRECTORY_SEPARATOR . 'watcher';
    }

    public function enqueue($temporaryUploadPath, $fileName, $postedKhoa = null, $username = '')
    {
        if (!is_dir($this->queueDirectory) || !is_writable($this->queueDirectory)) {
            throw new RuntimeException('Thư mục hàng đợi XML không thể ghi dữ liệu.');
        }
        if (!is_dir($this->lockDirectory)
            && !mkdir($this->lockDirectory, 0775, true)
            && !is_dir($this->lockDirectory)
        ) {
            throw new RuntimeException('Không thể tạo thư mục khóa upload.');
        }

        $destination = $this->queueDirectory . DIRECTORY_SEPARATOR . basename($fileName);
        $lockPath = $this->lockDirectory . DIRECTORY_SEPARATOR
            . 'upload_' . sha1(basename($fileName)) . '.lock';
        $lock = fopen($lockPath, 'c');
        if ($lock === false || !flock($lock, LOCK_EX)) {
            if ($lock !== false) fclose($lock);
            throw new RuntimeException('Không thể khóa tên file để tải lên.');
        }

        $stagingPath = null;
        $metadataSaved = false;
        try {
            $deadline = microtime(true) + 30;
            while (file_exists($destination) && microtime(true) < $deadline) {
                usleep(100000);
            }
            if (file_exists($destination)) {
                throw new RuntimeException('File cùng tên đang được xử lý, vui lòng thử lại sau.');
            }

            $stagingPath = $destination . '.uploading.' . substr(uniqid('', true), -8);
            if (!move_uploaded_file($temporaryUploadPath, $stagingPath)) {
                throw new RuntimeException('Không thể ghi file tải lên vào hàng đợi.');
            }

            $resolvedKhoa = ($postedKhoa !== null && $postedKhoa !== '')
                ? (int) $postedKhoa : null;
            $username = trim((string) $username);
            if ($resolvedKhoa === null && $username !== '') {
                // Chỉ dùng khoa của tài khoản làm giá trị dự phòng khi request
                // không gửi id_khoa. Nếu đã gửi id_khoa thì phải giữ nguyên.
                $userKhoa = $this->filePeer->findUserKhoa($username);
                if ($userKhoa['found']) {
                    $resolvedKhoa = $userKhoa['id_khoa'];
                }
            }

            $queuedFileName = basename($destination);
            $this->filePeer->upsertUploadQueue($queuedFileName, $resolvedKhoa, $username);
            $metadataSaved = true;

            if (!rename($stagingPath, $destination)) {
                throw new RuntimeException('Không thể hoàn tất việc đưa file vào hàng đợi.');
            }
            $stagingPath = null;

            return $queuedFileName;
        } catch (Throwable $exception) {
            if ($stagingPath !== null && is_file($stagingPath)) {
                @unlink($stagingPath);
            }
            if ($metadataSaved && !is_file($destination)) {
                try {
                    $this->filePeer->removeUploadQueue(basename($destination));
                } catch (Throwable $ignored) {
                }
            }
            throw $exception;
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }
}
