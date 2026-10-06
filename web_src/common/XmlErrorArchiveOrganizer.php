<?php

class XmlErrorArchiveOrganizer
{
    public static function organize($sourcePath, $failedDirectory, array $validation, $moveSource = false, $originalName = null)
    {
        if (!is_file($sourcePath)) {
            throw new RuntimeException('Không tìm thấy file XML lỗi để phân loại.');
        }

        $originalName = is_string($originalName) && $originalName !== ''
            ? basename($originalName) : basename($sourcePath);
        $groups = self::groupErrors($validation);
        self::removePreviousCopies($failedDirectory, $originalName, $sourcePath);

        $created = array();
        $canonicalSource = $sourcePath;
        foreach ($groups as $group) {
            $prefix = 'PASS' . $group['count'] . '_'
                . self::slug($group['file_type'], 'XML') . '_'
                . self::slug($group['error_name'], 'LOI');
            $directory = $failedDirectory . DIRECTORY_SEPARATOR . $prefix;
            if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
                throw new RuntimeException('Không thể tạo thư mục lỗi: ' . $directory);
            }

            $destination = $directory . DIRECTORY_SEPARATOR . $prefix . '_' . $originalName;
            if ($moveSource && empty($created)) {
                if (!rename($sourcePath, $destination)) {
                    throw new RuntimeException('Không thể chuyển file vào thư mục lỗi: ' . $directory);
                }
                $canonicalSource = $destination;
                $oldReport = $sourcePath . '.error.txt';
                if (is_file($oldReport)) @rename($oldReport, $destination . '.error.txt');
            } elseif (!copy($canonicalSource, $destination)) {
                throw new RuntimeException('Không thể sao chép file vào thư mục lỗi: ' . $directory);
            }
            $details = array(
                'Số lỗi: ' . $group['count'],
                'File XML: ' . $group['file_type'],
                'Tên lỗi: ' . $group['error_name'],
                'File gốc: ' . $originalName,
                '',
                implode(PHP_EOL, $group['details'])
            );
            file_put_contents($destination . '.error.txt', implode(PHP_EOL, $details) . PHP_EOL, LOCK_EX);
            $created[] = $destination;
        }
        return $created;
    }

    private static function groupErrors(array $validation)
    {
        $groups = array();
        foreach ($validation as $item) {
            $fileType = isset($item['file_type']) && trim((string) $item['file_type']) !== ''
                ? trim((string) $item['file_type']) : 'XML';
            foreach (isset($item['errors']) && is_array($item['errors']) ? $item['errors'] : array() as $error) {
                if (isset($error['severity']) && $error['severity'] === 'warning') continue;
                $message = isset($error['message']) ? trim((string) $error['message']) : 'Lỗi kiểm tra dữ liệu';
                $errorName = $message;
                $key = $fileType . "\0" . $message;
                if (!isset($groups[$key])) {
                    $groups[$key] = array(
                        'count' => 0,
                        'file_type' => $fileType,
                        'error_name' => $errorName,
                        'details' => array()
                    );
                }
                $groups[$key]['count']++;
                $path = isset($error['path']) ? trim((string) $error['path']) : '';
                $groups[$key]['details'][] = ($path !== '' ? $path . ': ' : '') . $message;
            }
        }
        return array_values($groups);
    }

    private static function slug($value, $fallback)
    {
        $value = trim((string) $value);
        $value = strtr($value, array(
            'à'=>'a','á'=>'a','ạ'=>'a','ả'=>'a','ã'=>'a','â'=>'a','ầ'=>'a','ấ'=>'a','ậ'=>'a','ẩ'=>'a','ẫ'=>'a','ă'=>'a','ằ'=>'a','ắ'=>'a','ặ'=>'a','ẳ'=>'a','ẵ'=>'a',
            'è'=>'e','é'=>'e','ẹ'=>'e','ẻ'=>'e','ẽ'=>'e','ê'=>'e','ề'=>'e','ế'=>'e','ệ'=>'e','ể'=>'e','ễ'=>'e',
            'ì'=>'i','í'=>'i','ị'=>'i','ỉ'=>'i','ĩ'=>'i','ò'=>'o','ó'=>'o','ọ'=>'o','ỏ'=>'o','õ'=>'o','ô'=>'o','ồ'=>'o','ố'=>'o','ộ'=>'o','ổ'=>'o','ỗ'=>'o','ơ'=>'o','ờ'=>'o','ớ'=>'o','ợ'=>'o','ở'=>'o','ỡ'=>'o',
            'ù'=>'u','ú'=>'u','ụ'=>'u','ủ'=>'u','ũ'=>'u','ư'=>'u','ừ'=>'u','ứ'=>'u','ự'=>'u','ử'=>'u','ữ'=>'u','ỳ'=>'y','ý'=>'y','ỵ'=>'y','ỷ'=>'y','ỹ'=>'y','đ'=>'d',
            'À'=>'A','Á'=>'A','Ạ'=>'A','Ả'=>'A','Ã'=>'A','Â'=>'A','Ầ'=>'A','Ấ'=>'A','Ậ'=>'A','Ẩ'=>'A','Ẫ'=>'A','Ă'=>'A','Ằ'=>'A','Ắ'=>'A','Ặ'=>'A','Ẳ'=>'A','Ẵ'=>'A',
            'È'=>'E','É'=>'E','Ẹ'=>'E','Ẻ'=>'E','Ẽ'=>'E','Ê'=>'E','Ề'=>'E','Ế'=>'E','Ệ'=>'E','Ể'=>'E','Ễ'=>'E',
            'Ì'=>'I','Í'=>'I','Ị'=>'I','Ỉ'=>'I','Ĩ'=>'I','Ò'=>'O','Ó'=>'O','Ọ'=>'O','Ỏ'=>'O','Õ'=>'O','Ô'=>'O','Ồ'=>'O','Ố'=>'O','Ộ'=>'O','Ổ'=>'O','Ỗ'=>'O','Ơ'=>'O','Ờ'=>'O','Ớ'=>'O','Ợ'=>'O','Ở'=>'O','Ỡ'=>'O',
            'Ù'=>'U','Ú'=>'U','Ụ'=>'U','Ủ'=>'U','Ũ'=>'U','Ư'=>'U','Ừ'=>'U','Ứ'=>'U','Ự'=>'U','Ử'=>'U','Ữ'=>'U','Ỳ'=>'Y','Ý'=>'Y','Ỵ'=>'Y','Ỷ'=>'Y','Ỹ'=>'Y','Đ'=>'D'
        ));
        if (function_exists('iconv')) {
            $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
            if ($ascii !== false) $value = $ascii;
        }
        $value = preg_replace('/[^A-Za-z0-9]+/', '_', $value);
        $value = trim($value, '_');
        if ($value === '') $value = $fallback;
        return substr($value, 0, 32);
    }

    private static function removePreviousCopies($failedDirectory, $originalName, $sourcePath)
    {
        if (!is_dir($failedDirectory)) return;
        $suffix = '_' . $originalName;
        foreach (new DirectoryIterator($failedDirectory) as $directory) {
            if (!$directory->isDir() || $directory->isDot() || strpos($directory->getFilename(), 'PASS') !== 0) continue;
            foreach (new DirectoryIterator($directory->getPathname()) as $file) {
                if (!$file->isFile()) continue;
                if (strcasecmp($file->getPathname(), $sourcePath) === 0) continue;
                $name = $file->getFilename();
                if (substr($name, -strlen($suffix)) === $suffix
                    || substr($name, -strlen($suffix . '.error.txt')) === $suffix . '.error.txt') {
                    @unlink($file->getPathname());
                }
            }
            $remaining = array_diff(scandir($directory->getPathname()), array('.', '..'));
            if (empty($remaining)) @rmdir($directory->getPathname());
        }
    }
}
