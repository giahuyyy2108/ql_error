<?php

class XmlFileDecoder
{
    public static function decodeDanhSachHoSo($xmlContent)
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
            $fileNodes = $hoSoNode->xpath('./*[local-name()="FILEHOSO"]');
            if (!$fileNodes) {
                throw new RuntimeException('HOSO thứ ' . ($hoSoIndex + 1) . ' không có FILEHOSO.');
            }

            $decodedFiles = array();
            foreach ($fileNodes as $fileIndex => $fileNode) {
                $typeNodes = $fileNode->xpath('./*[local-name()="LOAIHOSO"]');
                $contentNodes = $fileNode->xpath('./*[local-name()="NOIDUNGFILE"]');
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
            }

            $decodedHoSo[] = array('FILEHOSO' => $decodedFiles);
        }

        return array('DANHSACHHOSO' => array('HOSO' => $decodedHoSo));
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
        $children = $element->xpath('./*');
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
}
