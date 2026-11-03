<?php

require_once 'web_src/bean/UserPeer.php';
require_once 'web_src/bean/BackupRestorePeer.php';

class backupAction
{
    public static $listRole = 'backup';

    public function index()
    {
        $request = new Request();
        $request->setModel('www/admin/backuprestore/viewBackupRestore.htm');
        return true;
    }

    public function backup()
    {
        $request = new Request();
        $function = (string) $request->getParameter('function');

        try {
            $peer = new BackupRestorePeer();
            if ($function === 'backup') {
                return $this->downloadBackup($peer);
            }
            if ($function === 'restore') {
                $message = $this->restoreUpload($peer);
            } else {
                $message = 'Chức năng backup hoặc restore không hợp lệ.';
            }
        } catch (Throwable $e) {
            $message = $e->getMessage();
        }

        $request->setAttribute('msg', htmlspecialchars($message, ENT_QUOTES, 'UTF-8'));
        $request->setModel('www/admin/backuprestore/viewBackupRestore.htm');
        return true;
    }

    private function downloadBackup(BackupRestorePeer $peer)
    {
        $backup = $peer->backup();
        $filename = _DATABASE_NAME_ . '-' . date('Y-m-d-H-i-s') . '.sql';
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Content-Type: application/sql; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($backup));
        header('Cache-Control: private, no-store');
        header('X-Content-Type-Options: nosniff');
        echo $backup;
        return null;
    }

    private function restoreUpload(BackupRestorePeer $peer)
    {
        if (!isset($_FILES['file']) || !is_array($_FILES['file'])) {
            throw new RuntimeException('Vui lòng chọn file SQL cần phục hồi.');
        }
        $file = $_FILES['file'];
        if (!isset($file['error']) || (int) $file['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Upload file phục hồi thất bại.');
        }
        if (!isset($file['size']) || (int) $file['size'] <= 0
            || (int) $file['size'] > (int) _BACKUP_SIZE_) {
            throw new RuntimeException('File phục hồi rỗng hoặc vượt quá dung lượng cho phép.');
        }
        $originalName = isset($file['name']) ? basename((string) $file['name']) : '';
        if ($originalName === '' || strtolower(pathinfo($originalName, PATHINFO_EXTENSION)) !== 'sql') {
            throw new RuntimeException('Chỉ chấp nhận file có phần mở rộng .sql.');
        }
        if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            throw new RuntimeException('File upload không hợp lệ.');
        }

        $restoreRoot = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'restore';
        if (!is_dir($restoreRoot) && !mkdir($restoreRoot, 0775, true) && !is_dir($restoreRoot)) {
            throw new RuntimeException('Không thể tạo thư mục phục hồi.');
        }
        $storedName = 'restore-' . date('Ymd-His') . '-' . bin2hex(random_bytes(4)) . '.sql';
        $storedPath = $restoreRoot . DIRECTORY_SEPARATOR . $storedName;
        if (!move_uploaded_file($file['tmp_name'], $storedPath)) {
            throw new RuntimeException('Không thể lưu file phục hồi.');
        }

        $safetyBackup = $peer->backup();
        $safetyPath = $restoreRoot . DIRECTORY_SEPARATOR
            . _DATABASE_NAME_ . '-before-restore-' . date('Ymd-His') . '.sql';
        if (file_put_contents($safetyPath, $safetyBackup, LOCK_EX) === false) {
            throw new RuntimeException('Không thể tạo bản sao lưu an toàn trước khi phục hồi.');
        }

        return $peer->restore($storedName);
    }
}
