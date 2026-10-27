<?php

class XmlFileDecoder
{
    // ---------------------------------------------------------------------
    // Decode XML hồ sơ thành cấu trúc chuẩn mà validator/import service dùng
    // ---------------------------------------------------------------------

    /** Decode a file incrementally; output matches decodeDanhSachHoSoWithMetadata(). */
    public static function decodeDanhSachHoSoPathWithMetadata($path)
    {
        if (!class_exists('XMLReader')) {
            throw new RuntimeException('May chu chua bat phan mo rong XMLReader.');
        }

        $previous = libxml_use_internal_errors(true);
        libxml_clear_errors();
        $reader = new XMLReader();
        if (!$reader->open($path, null, LIBXML_NONET | LIBXML_NOCDATA)) {
            libxml_use_internal_errors($previous);
            throw new RuntimeException('Khong the mo file XML.');
        }

        $foundDanhSach = false;
        $danhSachDepth = null;
        $hoSoDepth = null;
        $currentFiles = null;
        $decodedHoSo = array();
        $ngayLap = null;
        try {
            while ($reader->read()) {
                if ($reader->nodeType === XMLReader::ELEMENT) {
                    if ($reader->localName === 'DANHSACHHOSO') {
                        $foundDanhSach = true;
                        $danhSachDepth = $reader->depth;
                    } elseif ($foundDanhSach && $reader->localName === 'HOSO'
                        && $reader->depth === $danhSachDepth + 1) {
                        $hoSoDepth = $reader->depth;
                        $currentFiles = array();
                    } elseif ($hoSoDepth !== null && $reader->localName === 'FILEHOSO'
                        && $reader->depth === $hoSoDepth + 1) {
                        $currentFiles[] = self::decodeFileFromReader(
                            $reader, count($decodedHoSo) + 1, count($currentFiles) + 1
                        );
                    } elseif ($reader->localName === 'NGAYLAP' && $ngayLap === null) {
                        $value = trim($reader->readString());
                        $ngayLap = $value === '' ? null : $value;
                    }
                } elseif ($reader->nodeType === XMLReader::END_ELEMENT
                    && $hoSoDepth !== null && $reader->localName === 'HOSO'
                    && $reader->depth === $hoSoDepth) {
                    if (empty($currentFiles)) {
                        throw new RuntimeException('HOSO thu ' . (count($decodedHoSo) + 1) . ' khong co FILEHOSO.');
                    }
                    $decodedHoSo[] = array('FILEHOSO' => $currentFiles);
                    $currentFiles = null;
                    $hoSoDepth = null;
                }
            }
        } finally {
            $reader->close();
        }

        $errors = libxml_get_errors();
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        if (!empty($errors)) {
            throw new RuntimeException('XML ho so khong hop le: ' . trim($errors[0]->message) . '.');
        }
        if (!$foundDanhSach) {
            throw new RuntimeException('Khong tim thay DANHSACHHOSO trong file XML.');
        }
        if (empty($decodedHoSo)) {
            throw new RuntimeException('DANHSACHHOSO khong co phan tu HOSO.');
        }
        return array(
            'content' => array('DANHSACHHOSO' => array('HOSO' => $decodedHoSo)),
            'ngay_lap' => $ngayLap
        );
    }

    public static function decodeDanhSachHoSo($xmlContent)
    {
        $result = self::decodeDanhSachHoSoWithMetadata($xmlContent);
        return $result['content'];
    }

    public static function decodeDanhSachHoSoWithMetadata($xmlContent)
    {
        $document = self::loadXml($xmlContent, 'XML hồ sơ không hợp lệ');
        $danhSachNodes = $document->xpath('//*[local-name()="DANHSACHHOSO"]');

        if (!$danhSachNodes || !isset($danhSachNodes[0])) {
            throw new RuntimeException('Không tìm thấy DANHSACHHOSO trong file XML.');
        }

        $hoSoNodes = $danhSachNodes[0]->xpath('./*[local-name()="HOSO"]');
        if (!$hoSoNodes) {
            throw new RuntimeException('DANHSACHHOSO không có phần tử HOSO.');
        }

        $decodedHoSo = array();
        foreach ($hoSoNodes as $hoSoIndex => $hoSoNode) {
            $fileNodes = self::childrenByName($hoSoNode, 'FILEHOSO');
            if (!$fileNodes) {
                throw new RuntimeException('HOSO thứ ' . ($hoSoIndex + 1) . ' không có FILEHOSO.');
            }

            $decodedFiles = array();
            foreach ($fileNodes as $fileIndex => $fileNode) {
                $typeNodes = self::childrenByName($fileNode, 'LOAIHOSO');
                $contentNodes = self::childrenByName($fileNode, 'NOIDUNGFILE');
                $type = isset($typeNodes[0]) ? trim((string) $typeNodes[0]) : '';
                $base64 = isset($contentNodes[0]) ? preg_replace('/\s+/', '', (string) $contentNodes[0]) : '';

                if ($type === '' || $base64 === '') {
                    throw new RuntimeException(
                        'FILEHOSO thứ ' . ($fileIndex + 1) . ' trong HOSO thứ ' . ($hoSoIndex + 1)
                        . ' thiếu LOAIHOSO hoặc NOIDUNGFILE.'
                    );
                }

                $decodedXml = base64_decode($base64, true);
                if ($decodedXml === false || trim($decodedXml) === '') {
                    throw new RuntimeException('NOIDUNGFILE của loại ' . $type . ' không phải Base64 hợp lệ.');
                }

                $decodedDocument = self::loadXml(
                    $decodedXml,
                    'XML sau giải mã Base64 của loại ' . $type . ' không hợp lệ'
                );

                $decodedFiles[] = array(
                    'LOAIHOSO' => $type,
                    'NOIDUNGFILE' => array(
                        $decodedDocument->getName() => self::elementToArray($decodedDocument)
                    )
                );
                // Do not retain the SimpleXML tree or the Base64 payload once
                // this FILEHOSO has been converted to the validator shape.
                unset($decodedDocument, $decodedXml, $base64, $contentNodes, $typeNodes);
            }

            $decodedHoSo[] = array('FILEHOSO' => $decodedFiles);
            unset($decodedFiles, $fileNodes);
        }

        $ngayLapNodes = $document->xpath(
            '//*[local-name()="THONGTINHOSO"]/*[local-name()="NGAYLAP"]'
        );
        $ngayLap = null;
        if ($ngayLapNodes && isset($ngayLapNodes[0])) {
            $value = trim((string) $ngayLapNodes[0]);
            $ngayLap = $value === '' ? null : $value;
        }

        unset($danhSachNodes, $hoSoNodes, $document);

        return array(
            'content' => array('DANHSACHHOSO' => array('HOSO' => $decodedHoSo)),
            'ngay_lap' => $ngayLap
        );
    }

    public static function extractMaLk(array $decodedContent)
    {
        $values = array();
        self::collectValuesByKey($decodedContent, 'MA_LK', $values);
        $values = array_values(array_unique(array_filter(array_map('trim', $values), 'strlen')));

        if (empty($values)) {
            throw new RuntimeException('Không tìm thấy MA_LK trong nội dung hồ sơ đã giải mã.');
        }
        if (count($values) > 1) {
            throw new RuntimeException('Các FILEHOSO có MA_LK không đồng nhất.');
        }

        return $values[0];
    }

    // ---------------------------------------------------------------------
    // Helper lấy metadata/trường dữ liệu từ nội dung XML đã decode
    // ---------------------------------------------------------------------

    public static function extractNgayLap($xmlContent)
    {
        $document = self::loadXml($xmlContent, 'XML hồ sơ không hợp lệ');
        $nodes = $document->xpath(
            '//*[local-name()="THONGTINHOSO"]/*[local-name()="NGAYLAP"]'
        );

        if (!$nodes || !isset($nodes[0])) {
            return null;
        }

        $value = trim((string) $nodes[0]);
        unset($nodes, $document);
        return $value === '' ? null : $value;
    }

    public static function extractFirstValue(array $decodedContent, $fieldName)
    {
        $values = array();
        self::collectValuesByKey($decodedContent, (string) $fieldName, $values);
        foreach ($values as $value) {
            $value = trim((string) $value);
            if ($value !== '') return $value;
        }
        return null;
    }

    public static function extractValuesFromFileType(array $decodedContent, $fileType, array $fieldNames)
    {
        $result = array();
        foreach ($fieldNames as $fieldName) {
            $result[(string) $fieldName] = null;
        }

        $hoSoList = isset($decodedContent['DANHSACHHOSO']['HOSO'])
            && is_array($decodedContent['DANHSACHHOSO']['HOSO'])
            ? $decodedContent['DANHSACHHOSO']['HOSO'] : array();
        foreach ($hoSoList as $hoSo) {
            $files = isset($hoSo['FILEHOSO']) && is_array($hoSo['FILEHOSO'])
                ? $hoSo['FILEHOSO'] : array();
            foreach ($files as $file) {
                if (strtoupper(trim((string) ($file['LOAIHOSO'] ?? ''))) !== strtoupper($fileType)) continue;
                $content = isset($file['NOIDUNGFILE']) && is_array($file['NOIDUNGFILE'])
                    ? $file['NOIDUNGFILE'] : array();
                foreach ($fieldNames as $fieldName) {
                    if ($result[$fieldName] !== null) continue;
                    $result[$fieldName] = self::extractFirstValue($content, $fieldName);
                }
            }
        }
        return $result;
    }

    private static function collectValuesByKey($data, $targetKey, array &$values)
    {
        if (!is_array($data)) {
            return;
        }

        foreach ($data as $key => $value) {
            if ($key === $targetKey && is_scalar($value)) {
                $values[] = (string) $value;
            }
            if (is_array($value)) {
                self::collectValuesByKey($value, $targetKey, $values);
            }
        }
    }

    // ---------------------------------------------------------------------
    // Helper parser XMLReader/SimpleXML
    // ---------------------------------------------------------------------

    private static function decodeFileFromReader(XMLReader $reader, $hoSoNumber, $fileNumber)
    {
        $fileDepth = $reader->depth;
        $type = '';
        $base64 = '';
        if (!$reader->isEmptyElement) {
            while ($reader->read()) {
                if ($reader->nodeType === XMLReader::ELEMENT && $reader->depth === $fileDepth + 1) {
                    if ($reader->localName === 'LOAIHOSO') {
                        $type = trim($reader->readString());
                    } elseif ($reader->localName === 'NOIDUNGFILE') {
                        $base64 = preg_replace('/\s+/', '', $reader->readString());
                    }
                } elseif ($reader->nodeType === XMLReader::END_ELEMENT
                    && $reader->localName === 'FILEHOSO' && $reader->depth === $fileDepth) {
                    break;
                }
            }
        }
        if ($type === '' || $base64 === '') {
            throw new RuntimeException('FILEHOSO thu ' . $fileNumber . ' trong HOSO thu ' . $hoSoNumber . ' thieu du lieu.');
        }
        $decodedXml = base64_decode($base64, true);
        unset($base64);
        if ($decodedXml === false || trim($decodedXml) === '') {
            throw new RuntimeException('NOIDUNGFILE cua loai ' . $type . ' khong phai Base64 hop le.');
        }
        $decodedDocument = self::loadXml($decodedXml, 'XML sau giai ma Base64 cua loai ' . $type . ' khong hop le');
        unset($decodedXml);
        $file = array(
            'LOAIHOSO' => $type,
            'NOIDUNGFILE' => array($decodedDocument->getName() => self::elementToArray($decodedDocument))
        );
        unset($decodedDocument);
        return $file;
    }

    private static function loadXml($content, $errorPrefix)
    {
        $previous = libxml_use_internal_errors(true);
        libxml_clear_errors();
        $xml = simplexml_load_string($content, 'SimpleXMLElement', LIBXML_NONET | LIBXML_NOCDATA);
        $errors = libxml_get_errors();
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if ($xml !== false) {
            return $xml;
        }

        $detail = !empty($errors)
            ? trim($errors[0]->message) . ' (dòng ' . $errors[0]->line . ')'
            : 'không thể phân tích XML';
        throw new RuntimeException($errorPrefix . ': ' . $detail . '.');
    }

    private static function elementToArray(SimpleXMLElement $element)
    {
        // children() avoids creating/evaluating an XPath expression for every
        // node. The XPath fallback keeps compatibility with documents whose
        // child elements use a non-default namespace.
        $children = self::directChildren($element);
        $attributes = array();
        foreach ($element->attributes() as $name => $value) {
            $attributes[$name] = (string) $value;
        }

        if (!$children) {
            $value = trim((string) $element);
            return empty($attributes)
                ? $value
                : array('@attributes' => $attributes, '_text' => $value);
        }

        $result = array();
        if (!empty($attributes)) {
            $result['@attributes'] = $attributes;
        }

        $counts = array();
        foreach ($children as $child) {
            $name = $child->getName();
            $counts[$name] = isset($counts[$name]) ? $counts[$name] + 1 : 1;
        }

        foreach ($children as $child) {
            $name = $child->getName();
            $value = self::elementToArray($child);
            if ($counts[$name] > 1) {
                if (!isset($result[$name])) {
                    $result[$name] = array();
                }
                $result[$name][] = $value;
            } else {
                $result[$name] = $value;
            }
        }

        return $result;
    }

    private static function childrenByName(SimpleXMLElement $element, $name)
    {
        $children = self::directChildren($element);
        $result = array();
        foreach ($children as $child) {
            if ($child->getName() === $name) {
                $result[] = $child;
            }
        }
        return $result;
    }

    private static function directChildren(SimpleXMLElement $element)
    {
        $children = array();
        foreach ($element->children() as $child) {
            $children[] = $child;
        }
        if (!empty($children)) {
            return $children;
        }

        // Only pay the XPath cost for namespace layouts that SimpleXML's
        // default children() view cannot expose.
        $fallback = $element->xpath('./*');
        return $fallback ?: array();
    }
}
