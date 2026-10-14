<?php

require_once dirname(__DIR__) . '/web_src/common/XmlErrorArchiveOrganizer.php';

$root = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'xml_error_organizer_' . uniqid('', true);
$failed = $root . DIRECTORY_SEPARATOR . 'failed';
$source = $root . DIRECTORY_SEPARATOR . 'hoso.xml';
if (!mkdir($root, 0775, true) || file_put_contents($source, '<xml/>') === false) {
    throw new RuntimeException('Không tạo được dữ liệu test tạm.');
}

$baseMessage = 'Đối tượng KCB 1.3 có giấy chuyển tuyến nhưng thiếu ';
$validation = array(array(
    'file_type' => 'XML1',
    'errors' => array(
        array(
            'rule_id' => 87, 'rule_type' => 'CONDITIONAL_FIELD', 'rule_value' => '{}',
            'path' => 'TONG_HOP.MA_DOITUONG_KCB', 'value' => '1.3',
            'message' => $baseMessage . 'MA_NOI_DI', 'severity' => 'error'
        ),
        array(
            'rule_id' => 88, 'rule_type' => 'CONDITIONAL_FIELD', 'rule_value' => '{}',
            'path' => 'TONG_HOP.MA_DOITUONG_KCB', 'value' => '1.3',
            'message' => $baseMessage . 'GIAY_CHUYEN_TUYEN', 'severity' => 'error'
        )
    )
));

try {
    $created = XmlErrorArchiveOrganizer::organize($source, $failed, $validation, false, 'hoso.xml');
    if (count($created) !== 2 || $created[0] === $created[1]) {
        throw new RuntimeException('Hai rule phải tạo hai đường dẫn khác nhau.');
    }
    foreach ($created as $path) {
        if (!is_file($path) || !is_file($path . '.error.txt')) {
            throw new RuntimeException('Thiếu file XML hoặc báo cáo lỗi được phân loại.');
        }
    }
    echo "XmlErrorArchiveOrganizerTest: OK\n";
} finally {
    if (is_dir($failed)) {
        foreach (new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($failed, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        ) as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }
        rmdir($failed);
    }
    if (is_file($source)) unlink($source);
    if (is_dir($root)) rmdir($root);
}
