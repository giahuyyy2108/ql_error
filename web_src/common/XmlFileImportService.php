<?php

require_once dirname(__FILE__) . '/XmlFileDecoder.php';

class XmlFileImportService
{
    private $filePeer;
    private $maxFileSize;
    private $validationCallback;

    public function __construct($filePeer, $maxFileSize = 5242880, $validationCallback = null)
    {
        $this->filePeer = $filePeer;
        $this->maxFileSize = (int) $maxFileSize;
        $this->validationCallback = $validationCallback;
    }

    public function importPath($path, $storedPath = null)
    {
        if (!is_file($path) || !is_readable($path)) {
            throw new RuntimeException('Không thể đọc file XML.');
        }

        $size = filesize($path);
        if ($size === false) {
            throw new RuntimeException('Không thể xác định dung lượng file XML.');
        }

        $content = file_get_contents($path);
        if ($content === false) {
            throw new RuntimeException('Không thể đọc nội dung file XML.');
        }

        return $this->importContent(
            basename($path),
            $content,
            $size,
            $storedPath !== null ? $storedPath : $path
        );
    }

    public function importContent($fileName, $content, $size, $filePath)
    {
        $fileName = $this->normalizeFileName($fileName);
        $size = (int) $size;
        if ($size <= 0 || $size > $this->maxFileSize) {
            throw new RuntimeException('File XML phải có dung lượng từ 1 byte đến 5 MB.');
        }
        if (!is_string($content) || $content === '') {
            throw new RuntimeException('Nội dung file XML rỗng.');
        }
        if (!is_string($filePath) || trim($filePath) === '') {
            throw new RuntimeException('Đường dẫn lưu file XML không hợp lệ.');
        }

        $decodedContent = XmlFileDecoder::decodeDanhSachHoSo($content);
        $maLk = XmlFileDecoder::extractMaLk($decodedContent);
        $validationResult = $this->validationCallback === null
            ? null
            : call_user_func($this->validationCallback, $decodedContent);
        $status = $this->hasValidationErrors($validationResult) ? 'failed' : 'processed';
        $existing = method_exists($this->filePeer, 'getByMaLk')
            ? $this->filePeer->getByMaLk($maLk) : false;
        if ($existing !== false) {
            $this->filePeer->replaceImportedFile(
                (int) $existing['id'],
                $fileName,
                $size,
                $filePath,
                is_array($validationResult) ? $validationResult : array(),
                $status
            );
            return array(
                'status' => 'reimported',
                'processing_status' => $status,
                'id' => (int) $existing['id'],
                'ma_lk' => $maLk,
                'name' => $fileName,
                'validation' => $validationResult,
                'old_file_path' => $existing['file_path']
            );
        }
        $id = $this->filePeer->insert($fileName, $maLk, $size, $filePath, $validationResult, $status);
        return array(
            'status' => 'imported',
            'processing_status' => $status,
            'id' => $id,
            'ma_lk' => $maLk,
            'name' => $fileName,
            'validation' => $validationResult
        );
    }

    private function hasValidationErrors($validationResult)
    {
        if (!is_array($validationResult)) {
            return false;
        }
        foreach ($validationResult as $result) {
            if (isset($result['error_count']) && (int) $result['error_count'] > 0) {
                return true;
            }
        }
        return false;
    }

    private function normalizeFileName($fileName)
    {
        $fileName = trim((string) $fileName);
        if ($fileName === '' || $fileName !== basename($fileName) || strlen($fileName) > 180) {
            throw new RuntimeException('Tên file không hợp lệ hoặc file không có đuôi .xml.');
        }
        if (!preg_match('/^[\p{L}\p{N}][\p{L}\p{N} _.()-]*\.xml$/iu', $fileName)) {
            throw new RuntimeException('Tên file không hợp lệ hoặc file không có đuôi .xml.');
        }
        return $fileName;
    }
}
