<?php

require_once dirname(__FILE__) . '/XmlFileDecoder.php';

class XmlFileImportService
{
    private $filePeer;
    private $maxFileSize;
    private $validationCallback;
    private $bhytLookupCallback;

    public function __construct($filePeer, $maxFileSize = 5242880, $validationCallback = null, $bhytLookupCallback = null)
    {
        $this->filePeer = $filePeer;
        $this->maxFileSize = (int) $maxFileSize;
        $this->validationCallback = $validationCallback;
        $this->bhytLookupCallback = $bhytLookupCallback;
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
        $size = (int) $size;
        if ($size <= 0 || $size > $this->maxFileSize) {
            throw new RuntimeException('File XML phải có dung lượng từ 1 byte đến 5 MB.');
        }

        $decodedResult = XmlFileDecoder::decodeDanhSachHoSoPathWithMetadata($path);
        return $this->importContent(
            basename($path),
            null,
            $size,
            $storedPath !== null ? $storedPath : $path,
            $decodedResult
        );
    }

    public function importContent($fileName, $content, $size, $filePath, $decodedResult = null)
    {
        $fileName = $this->normalizeFileName($fileName);
        $size = (int) $size;
        if ($size <= 0 || $size > $this->maxFileSize) {
            throw new RuntimeException('File XML phải có dung lượng từ 1 byte đến 5 MB.');
        }
        if ($decodedResult === null && (!is_string($content) || $content === '')) {
            throw new RuntimeException('Nội dung file XML rỗng.');
        }
        if (!is_string($filePath) || trim($filePath) === '') {
            throw new RuntimeException('Đường dẫn lưu file XML không hợp lệ.');
        }

        if ($decodedResult === null) {
            $decodedResult = XmlFileDecoder::decodeDanhSachHoSoWithMetadata($content);
        }
        $decodedContent = $decodedResult['content'];
        $maLk = XmlFileDecoder::extractMaLk($decodedContent);
        $xml1Patient = XmlFileDecoder::extractValuesFromFileType(
            $decodedContent,
            'XML1',
            array('MA_BN', 'MA_THE_BHYT', 'HO_TEN', 'NGAY_SINH')
        );
        $maBn = $xml1Patient['MA_BN'];
        $hoTen = $xml1Patient['HO_TEN'];
        $ngayLap = $decodedResult['ngay_lap'];
        // The original XML string is no longer needed after decoding. Release
        // it before validation/BHYT lookup, which may keep the decoded tree.
        unset($decodedResult, $content);
        $validationResult = $this->validationCallback === null
            ? null
            : call_user_func($this->validationCallback, $decodedContent);
        $thongTinBn = null;
        if (is_callable($this->bhytLookupCallback)) {
            try {
                $response = call_user_func(
                    $this->bhytLookupCallback,
                    $xml1Patient['MA_THE_BHYT'],
                    $xml1Patient['HO_TEN'],
                    $xml1Patient['NGAY_SINH']
                );
                $thongTinBn = is_array($response) ? $this->filterBhytResponse($response) : null;
            } catch (Throwable $ignored) {
                $thongTinBn = null;
            }
        }
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
                $status,
                $ngayLap,
                $maBn,
                $hoTen,
                $thongTinBn
            );
            return array(
                'status' => 'reimported',
                'processing_status' => $status,
                'id' => (int) $existing['id'],
                'ma_lk' => $maLk,
                'name' => $fileName,
                'validation' => $validationResult,
                'old_file_path' => $existing['file_path'],
                'old_file' => $existing
            );
        }
        $id = $this->filePeer->insert(
            $fileName,
            $maLk,
            $size,
            $filePath,
            $validationResult,
            $status,
            $ngayLap,
            $maBn,
            $hoTen,
            $thongTinBn
        );
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

    private function filterBhytResponse(array $response)
    {
        $fields = array('maThe', 'ngaySinh', 'gioiTinh', 'diaChi', 'maDKBD', 'gtTheTu', 'gtTheDen', 'maSoBHXH');
        $filtered = array();
        foreach ($fields as $field) {
            $filtered[$field] = $this->bhytResponseValue($response, $field);
        }
        return $filtered;
    }

    private function bhytResponseValue(array $data, $target)
    {
        foreach ($data as $key => $value) {
            if (strcasecmp((string) $key, $target) === 0 && is_scalar($value)) {
                return $value;
            }
        }
        foreach ($data as $value) {
            if (!is_array($value)) continue;
            $found = $this->bhytResponseValue($value, $target);
            if ($found !== null) return $found;
        }
        return null;
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
