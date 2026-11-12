<?php

/**
 * XLSX format reader/writer only. Catalog permissions, validation, and SQL
 * remain in the individual catalog Action classes.
 */
class CatalogExcel
{
    const MAX_ROWS = 500;
    const MAX_BYTES = 5242880;

    public static function fields($schema)
    {
        return array_values(array_filter($schema['fields'], function ($field) {
            return $field['editable'] && !$field['auto'];
        }));
    }

    private static function escape($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    private static function column($index)
    {
        $result = '';
        do {
            $result = chr(65 + $index % 26) . $result;
            $index = intdiv($index, 26) - 1;
        } while ($index >= 0);
        return $result;
    }

    private static function cell($index, $row, $value, $style = 1)
    {
        $coord = self::column($index) . $row;
        return '<c r="' . $coord . '" s="' . $style
            . '" t="inlineStr"><is><t xml:space="preserve">'
            . self::escape($value) . '</t></is></c>';
    }

    private static function sheetXml($rows, $columnCount, $filter = false)
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';
        if ($filter) {
            $xml .= '<sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>';
        }
        if ($columnCount > 0) {
            $xml .= '<cols>';
            for ($i = 1; $i <= $columnCount; $i++) {
                $xml .= '<col min="' . $i . '" max="' . $i
                    . '" width="27" customWidth="1" style="1"/>';
            }
            $xml .= '</cols>';
        }
        $xml .= '<sheetData>';
        foreach ($rows as $rowIndex => $values) {
            $number = $rowIndex + 1;
            $xml .= '<row r="' . $number . '">';
            foreach (array_values($values) as $i => $value) {
                $xml .= self::cell($i, $number, $value, $number === 1 ? 2 : 1);
            }
            $xml .= '</row>';
        }
        $xml .= '</sheetData>';
        if ($filter && $columnCount > 0) {
            $xml .= '<autoFilter ref="A1:' . self::column($columnCount - 1) . '1"/>';
        }
        return $xml . '</worksheet>';
    }

    public static function sendTemplate($schema)
    {
        if (!class_exists('ZipArchive')) {
            throw new RuntimeException('Máy chủ chưa bật extension zip.');
        }
        $fields = self::fields($schema);
        if (!$fields) {
            throw new InvalidArgumentException('Danh mục không có cột cho phép nhập Excel.');
        }
        $headers = array_column($fields, 'name');
        $guide = array(
            array('Tên cột', 'Kiểu dữ liệu', 'Bắt buộc', 'Ghi chú'),
        );
        foreach ($fields as $field) {
            $guide[] = array($field['name'], $field['type'],
                (!$field['nullable'] && $field['default'] === null) ? 'Có' : 'Không',
                $field['primary'] ? 'Mã định danh; không trùng dữ liệu đã có' : '');
        }
        $guide[] = array('Hướng dẫn', '', '', 'Nhập dữ liệu tại sheet DuLieu, từ dòng 2; giữ nguyên dòng tiêu đề.');
        $guide[] = array('Định dạng', '', '', 'Mã có số 0 đầu phải ở dạng Text; ngày YYYY-MM-DD, ngày giờ YYYY-MM-DD HH:MM:SS.');
        $guide[] = array('Giới hạn', '', '', 'Tối đa ' . self::MAX_ROWS . ' dòng; không sử dụng công thức.');
        $guide[] = array('Lưu ý', '', '', 'File có lỗi sẽ không nhập dòng nào. Chỉ hỗ trợ .xlsx.');

        $path = tempnam(sys_get_temp_dir(), 'catalog_xlsx_');
        if ($path === false) {
            throw new RuntimeException('Không tạo được file Excel tạm.');
        }
        try {
            $zip = new ZipArchive();
            if ($zip->open($path, ZipArchive::OVERWRITE) !== true) {
                throw new RuntimeException('Không tạo được file .xlsx.');
            }
            $files = array(
                '[Content_Types].xml' => '<?xml version="1.0" encoding="UTF-8"?>'
                    . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
                    . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
                    . '<Default Extension="xml" ContentType="application/xml"/>'
                    . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
                    . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
                    . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
                    . '<Override PartName="/xl/worksheets/sheet2.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
                    . '</Types>',
                '_rels/.rels' => '<?xml version="1.0" encoding="UTF-8"?>'
                    . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
                    . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
                    . '</Relationships>',
                'xl/workbook.xml' => '<?xml version="1.0" encoding="UTF-8"?>'
                    . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
                    . 'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
                    . '<sheets><sheet name="DuLieu" sheetId="1" r:id="rId1"/>'
                    . '<sheet name="HuongDan" sheetId="2" r:id="rId2"/></sheets></workbook>',
                'xl/_rels/workbook.xml.rels' => '<?xml version="1.0" encoding="UTF-8"?>'
                    . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
                    . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
                    . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet2.xml"/>'
                    . '<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
                    . '</Relationships>',
                'xl/styles.xml' => '<?xml version="1.0" encoding="UTF-8"?>'
                    . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
                    . '<fonts count="2"><font><sz val="11"/><name val="Calibri"/></font>'
                    . '<font><b/><sz val="11"/><name val="Calibri"/></font></fonts>'
                    . '<fills count="2"><fill><patternFill patternType="none"/></fill>'
                    . '<fill><patternFill patternType="gray125"/></fill></fills>'
                    . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
                    . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
                    . '<cellXfs count="3"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
                    . '<xf numFmtId="49" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
                    . '<xf numFmtId="49" fontId="1" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/></cellXfs>'
                    . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
                    . '</styleSheet>',
                'xl/worksheets/sheet1.xml' => self::sheetXml(array($headers), count($headers), true),
                'xl/worksheets/sheet2.xml' => self::sheetXml($guide, 4),
            );
            foreach ($files as $name => $content) {
                if (!$zip->addFromString($name, $content)) {
                    $zip->close();
                    throw new RuntimeException('Không ghi được nội dung file Excel.');
                }
            }
            if (!$zip->close()) {
                throw new RuntimeException('Không thể hoàn tất file Excel.');
            }
            $filename = 'mau_' . $schema['module'] . '_' . date('Ymd') . '.xlsx';
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . filesize($path));
            header('Cache-Control: no-store');
            readfile($path);
        } finally {
            @unlink($path);
        }
    }

    private static function xml($value)
    {
        if ($value === false || stripos($value, '<!DOCTYPE') !== false) {
            throw new InvalidArgumentException('Nội dung Excel không hợp lệ.');
        }
        $previous = libxml_use_internal_errors(true);
        try {
            $document = new DOMDocument();
            if (!$document->loadXML($value, LIBXML_NONET)) {
                throw new InvalidArgumentException('Không đọc được dữ liệu XML trong Excel.');
            }
            return $document;
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }

    private static function nodeText(DOMXPath $xpath, DOMNode $node, $expression)
    {
        $found = $xpath->query($expression, $node);
        return $found->length ? $found->item(0)->textContent : '';
    }

    private static function cellValue(DOMXPath $xpath, DOMElement $cell, $shared)
    {
        if ($xpath->query('./*[local-name()="f"]', $cell)->length) {
            throw new InvalidArgumentException('File Excel không được chứa công thức.');
        }
        $type = $cell->getAttribute('t');
        if ($type === 'e') {
            throw new InvalidArgumentException('Ô Excel chứa giá trị lỗi.');
        }
        if ($type === 'inlineStr') {
            $texts = $xpath->query('./*[local-name()="is"]//*[local-name()="t"]', $cell);
            $value = '';
            foreach ($texts as $text) {
                $value .= $text->textContent;
            }
            return $value;
        }
        $value = self::nodeText($xpath, $cell, './*[local-name()="v"]');
        if ($type === 's') {
            if (!ctype_digit($value) || !array_key_exists((int) $value, $shared)) {
                throw new InvalidArgumentException('Bảng shared strings không hợp lệ.');
            }
            return $shared[(int) $value];
        }
        if ($type === 'b') {
            return $value === '1' ? '1' : '0';
        }
        return $value;
    }

    public static function read($path, $schema)
    {
        $expected = array_column(self::fields($schema), 'name');
        if (!$expected || !is_file($path) || filesize($path) > self::MAX_BYTES) {
            throw new InvalidArgumentException('File Excel không hợp lệ hoặc vượt quá 5 MB.');
        }
        $zip = new ZipArchive();
        if ($zip->open($path, ZipArchive::CHECKCONS) !== true) {
            throw new InvalidArgumentException('Không mở được file .xlsx.');
        }
        try {
            if ($zip->numFiles > 200) {
                throw new InvalidArgumentException('File Excel có quá nhiều thành phần.');
            }
            $total = 0;
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $stats = $zip->statIndex($i);
                $total += $stats['size'];
                if ($total > 15728640) {
                    throw new InvalidArgumentException('Dữ liệu giải nén Excel quá lớn.');
                }
            }
            $shared = array();
            $sharedXml = $zip->getFromName('xl/sharedStrings.xml');
            if ($sharedXml !== false) {
                $document = self::xml($sharedXml);
                $xpath = new DOMXPath($document);
                foreach ($xpath->query('//*[local-name()="si"]') as $item) {
                    $text = '';
                    foreach ($xpath->query('.//*[local-name()="t"]', $item) as $part) {
                        $text .= $part->textContent;
                    }
                    $shared[] = $text;
                }
            }
            $document = self::xml($zip->getFromName('xl/worksheets/sheet1.xml'));
            $xpath = new DOMXPath($document);
            $rows = array();
            $header = array();
            foreach ($xpath->query('//*[local-name()="sheetData"]/*[local-name()="row"]') as $row) {
                $number = (int) $row->getAttribute('r');
                if ($number < 1 || $number > self::MAX_ROWS + 1) {
                    throw new InvalidArgumentException('Excel chỉ được nhập tối đa ' . self::MAX_ROWS . ' dòng dữ liệu.');
                }
                $values = array_fill(0, count($expected), '');
                $seen = array();
                foreach ($xpath->query('./*[local-name()="c"]', $row) as $cell) {
                    $ref = strtoupper($cell->getAttribute('r'));
                    if (!preg_match('/^([A-Z]+)([1-9][0-9]*)$/D', $ref, $matches) || (int) $matches[2] !== $number) {
                        throw new InvalidArgumentException('Tọa độ ô Excel không hợp lệ.');
                    }
                    $position = 0;
                    foreach (str_split($matches[1]) as $char) {
                        $position = $position * 26 + ord($char) - 64;
                    }
                    $position--;
                    if ($position >= count($expected) || isset($seen[$position])) {
                        throw new InvalidArgumentException('Excel có cột ngoài mẫu hoặc ô trùng.');
                    }
                    $seen[$position] = true;
                    $values[$position] = self::cellValue($xpath, $cell, $shared);
                }
                if ($number === 1) {
                    $header = array_map('trim', $values);
                } elseif (count(array_filter($values, function ($value) { return trim($value) !== ''; })) > 0) {
                    $rows[$number] = array_combine($expected, $values);
                }
            }
            if ($header !== $expected) {
                throw new InvalidArgumentException('Tên hoặc thứ tự cột không khớp mẫu mới nhất. Hãy tải lại Excel mẫu.');
            }
            if (!$rows) {
                throw new InvalidArgumentException('File Excel chưa có dữ liệu từ dòng 2.');
            }
            return $rows;
        } finally {
            $zip->close();
        }
    }
}
