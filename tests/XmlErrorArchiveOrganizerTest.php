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
    if (count($created) !== 1) {
        throw new RuntimeException('File chỉ được lưu trong thư mục lỗi đầu tiên.');
    }
    foreach ($created as $path) {
        if (!is_file($path) || !is_file($path . '.error.txt')) {
            throw new RuntimeException('Thiếu file XML hoặc báo cáo lỗi được phân loại.');
        }
        if (basename($path) !== 'hoso.xml'
            || strpos(basename(dirname($path)), 'PASS') === 0) {
            throw new RuntimeException('File phải giữ nguyên tên gốc và thư mục phải là tên lỗi.');
        }
    }

    // Re-import with the same name and the same validation error must keep
    // both physical XML versions and their individual error reports.
    $previousPath = $created[0];
    $previousReport = file_get_contents($previousPath . '.error.txt');
    $secondSource = $root . DIRECTORY_SEPARATOR . 'hoso-second.xml';
    file_put_contents($secondSource, '<xml version="2"/>');
    $second = XmlErrorArchiveOrganizer::organize(
        $secondSource, $failed, $validation, true, 'hoso.xml'
    );
    if (count($second) !== 1 || $second[0] === $previousPath
        || basename($second[0]) === 'hoso.xml'
        || file_get_contents($previousPath) !== '<xml/>'
        || file_get_contents($second[0]) !== '<xml version="2"/>'
        || file_get_contents($previousPath . '.error.txt') !== $previousReport
        || !is_file($second[0] . '.error.txt') || is_file($secondSource)) {
        throw new RuntimeException('Re-import đã ghi đè hoặc làm mất XML/báo cáo lỗi cũ.');
    }

    // Revalidation of an XML that is already in its target directory should
    // preserve that file instead of trying to link/copy it onto itself.
    $again = XmlErrorArchiveOrganizer::organize(
        $second[0], $failed, $validation, true, basename($second[0])
    );
    if ($again !== $second || !is_file($second[0]) || !is_file($previousPath)) {
        throw new RuntimeException('Quét lại file trong thư mục lỗi làm mất XML.');
    }

    // Changing the validation error must not remove an earlier version from
    // a different error directory.
    $changedValidation = $validation;
    $changedValidation[0]['errors'][0]['message'] = 'Another validation error';
    $thirdSource = $root . DIRECTORY_SEPARATOR . 'hoso-third.xml';
    file_put_contents($thirdSource, '<xml version="3"/>');
    $third = XmlErrorArchiveOrganizer::organize(
        $thirdSource, $failed, $changedValidation, true, 'hoso.xml'
    );
    if (count($third) !== 1 || !is_file($third[0])
        || !is_file($previousPath) || !is_file($second[0])
        || !is_file($previousPath . '.error.txt')) {
        throw new RuntimeException('Đổi loại lỗi khi import lại làm mất file cũ.');
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
