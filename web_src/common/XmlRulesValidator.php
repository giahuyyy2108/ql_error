<?php

class XmlRulesValidator
{
    public static function validate(array $decodedContent, array $rules, $apiService = null)
    {
        $rulesByType = array();
        foreach ($rules as $rule) {
            if ((int) $rule['is_active'] !== 1) {
                continue;
            }
            $fileType = strtoupper(trim($rule['file_type']));
            if (!isset($rulesByType[$fileType])) {
                $rulesByType[$fileType] = array();
            }
            $rulesByType[$fileType][] = $rule;
        }

        $results = array();
        $hoSoList = isset($decodedContent['DANHSACHHOSO']['HOSO'])
            ? $decodedContent['DANHSACHHOSO']['HOSO']
            : array();

        foreach ($hoSoList as $hoSoIndex => $hoSo) {
            $fileList = isset($hoSo['FILEHOSO']) ? $hoSo['FILEHOSO'] : array();
            foreach ($fileList as $fileIndex => $file) {
                $fileType = isset($file['LOAIHOSO']) ? strtoupper($file['LOAIHOSO']) : '';
                $errors = array();
                $content = isset($file['NOIDUNGFILE']) ? $file['NOIDUNGFILE'] : array();

                foreach (isset($rulesByType[$fileType]) ? $rulesByType[$fileType] : array() as $rule) {
                    $matches = array();
                    self::findFields($content, $rule['field_name'], '', $matches);
                    $ruleType = strtoupper(trim($rule['rule_type']));

                    if ($ruleType === 'FIELD_COMPARE') {
                        $errors = array_merge(
                            $errors,
                            self::validateFieldCompare($rule, $matches, $content, $fileList)
                        );
                        continue;
                    }

                    if ($ruleType === 'API') {
                        $errors = array_merge(
                            $errors,
                            self::validateApi($rule, $matches, $content, $fileList, $apiService)
                        );
                        continue;
                    }

                    if ($ruleType === 'SUBSTRING') {
                        $errors = array_merge($errors, self::validateSubstring($rule, $matches));
                        continue;
                    }

                    if ($ruleType === 'CCCD_GENDER_CENTURY') {
                        $errors = array_merge(
                            $errors,
                            self::validateCccdGenderCentury($rule, $matches, $content, $fileList)
                        );
                        continue;
                    }

                    if (empty($matches)) {
                        if ($ruleType === 'REQUIRED') {
                            $errors[] = self::makeError($rule, $rule['field_name'], null);
                        }
                        continue;
                    }

                    foreach ($matches as $match) {
                        if (!self::passes($match['value'], $ruleType, $rule['rule_value'])) {
                            $errors[] = self::makeError($rule, $match['path'], $match['value']);
                        }
                    }
                }

                $results[] = array(
                    'hoso_index' => (int) $hoSoIndex,
                    'file_index' => (int) $fileIndex,
                    'file_type' => $fileType,
                    'errors' => $errors,
                    'error_count' => count(array_filter($errors, function ($error) {
                        return !isset($error['severity']) || $error['severity'] === 'error';
                    })),
                    'warning_count' => count(array_filter($errors, function ($error) {
                        return isset($error['severity']) && $error['severity'] === 'warning';
                    }))
                );
            }
        }

        return $results;
    }

    private static function findFields($data, $fieldName, $path, array &$matches)
    {
        if (!is_array($data)) {
            return;
        }

        foreach ($data as $key => $value) {
            $key = (string) $key;
            $currentPath = $path === ''
                ? $key
                : ($key !== '' && ctype_digit($key) ? $path . '[' . $key . ']' : $path . '.' . $key);

            if ($key === $fieldName) {
                $matches[] = array('path' => $currentPath, 'value' => $value);
            }
            if (is_array($value)) {
                self::findFields($value, $fieldName, $currentPath, $matches);
            }
        }
    }

    private static function passes($value, $ruleType, $ruleValue)
    {
        $text = is_scalar($value) ? trim((string) $value) : '';
        $length = function_exists('mb_strlen') ? mb_strlen($text, 'UTF-8') : strlen($text);

        switch ($ruleType) {
            case 'REQUIRED':
                return $text !== '';
            case 'LENGTH':
                return $length === (int) $ruleValue;
            case 'MIN_LENGTH':
                return $length >= (int) $ruleValue;
            case 'MAX_LENGTH':
                return $length <= (int) $ruleValue;
            case 'REGEX':
                return $ruleValue !== null && $ruleValue !== '' && @preg_match($ruleValue, $text) === 1;
            case 'NUMERIC':
                return $text !== '' && is_numeric($text);
            case 'MIN':
                return is_numeric($text) && is_numeric($ruleValue) && (float) $text >= (float) $ruleValue;
            case 'MAX':
                return is_numeric($text) && is_numeric($ruleValue) && (float) $text <= (float) $ruleValue;
            case 'RANGE':
                $range = preg_split('/\s*[,;|]\s*/', (string) $ruleValue);
                return count($range) === 2 && is_numeric($text) && is_numeric($range[0]) && is_numeric($range[1])
                    && (float) $text >= (float) $range[0] && (float) $text <= (float) $range[1];
            case 'IN':
                $allowed = preg_split('/\s*[,;|]\s*/', (string) $ruleValue);
                return in_array($text, $allowed, true);
            case 'DATE':
                $format = trim((string) $ruleValue) !== '' ? trim((string) $ruleValue) : 'Ymd';
                $date = DateTime::createFromFormat('!' . $format, $text);
                return $date !== false && $date->format($format) === $text;
            default:
                return true;
        }
    }

    private static function validateFieldCompare(array $rule, array $matches, array $content, array $fileList)
    {
        $config = json_decode($rule['rule_value'], true);
        if (!is_array($config) || empty($config['other_field']) || empty($config['operator'])) {
            return array(self::makeError($rule, $rule['field_name'], null, 'error', 'Cấu hình FIELD_COMPARE không hợp lệ.'));
        }
        if (empty($matches)) {
            return array(self::makeError($rule, $rule['field_name'], null));
        }

        $otherContent = $content;
        if (!empty($config['other_file_type'])) {
            $otherContent = self::findFileContent($fileList, strtoupper($config['other_file_type']));
        }
        $otherMatches = array();
        self::findFields($otherContent, $config['other_field'], '', $otherMatches);
        if (empty($otherMatches)) {
            return array(self::makeError(
                $rule,
                $matches[0]['path'],
                $matches[0]['value'],
                'error',
                'Không tìm thấy trường đối chiếu ' . $config['other_field'] . '.'
            ));
        }

        $errors = array();
        foreach ($matches as $match) {
            if (!self::compareValues(
                $match['value'],
                $otherMatches[0]['value'],
                $config['operator'],
                isset($config['data_type']) ? $config['data_type'] : 'string',
                isset($config['format']) ? $config['format'] : null
            )) {
                $errors[] = self::makeError($rule, $match['path'], $match['value']);
            }
        }
        return $errors;
    }

    private static function validateSubstring(array $rule, array $matches)
    {
        $config = json_decode($rule['rule_value'], true);
        if (!is_array($config) || !array_key_exists('start', $config)
            || !array_key_exists('length', $config) || empty($config['operator'])
            || !array_key_exists('expected', $config)) {
            return array(self::makeError($rule, $rule['field_name'], null, 'error', 'Cấu hình SUBSTRING không hợp lệ.'));
        }
        if (empty($matches)) {
            return array(self::makeError($rule, $rule['field_name'], null));
        }

        $start = (int) $config['start'];
        $length = (int) $config['length'];
        $operator = strtoupper(trim($config['operator']));
        $expected = $config['expected'];
        $errors = array();

        foreach ($matches as $match) {
            $source = is_scalar($match['value']) ? (string) $match['value'] : '';
            $part = function_exists('mb_substr')
                ? mb_substr($source, $start, $length, 'UTF-8')
                : substr($source, $start, $length);
            $passed = false;

            if ($operator === 'IN' || $operator === 'NOT_IN') {
                $allowed = is_array($expected)
                    ? array_map('strval', $expected)
                    : preg_split('/\s*[,;|]\s*/', (string) $expected);
                $passed = in_array($part, $allowed, true);
                if ($operator === 'NOT_IN') $passed = !$passed;
            } elseif ($operator === 'REGEX') {
                $passed = is_string($expected) && @preg_match($expected, $part) === 1;
            } else {
                $passed = self::compareValues($part, $expected, $operator, 'string', null);
            }

            if (!$passed) {
                $error = self::makeError($rule, $match['path'], $match['value']);
                $error['substring'] = $part;
                $errors[] = $error;
            }
        }
        return $errors;
    }

    private static function validateCccdGenderCentury(array $rule, array $cccdMatches, array $content, array $fileList)
    {
        $config = json_decode($rule['rule_value'], true);
        if (!is_array($config) || empty($config['birth_field']) || empty($config['gender_field'])) {
            return array(self::makeError(
                $rule, $rule['field_name'], null, 'error', 'Cấu hình CCCD_GENDER_CENTURY không hợp lệ.'
            ));
        }
        if (empty($cccdMatches)) {
            return array(self::makeError($rule, $rule['field_name'], null));
        }

        $birthContent = !empty($config['birth_file_type'])
            ? self::findFileContent($fileList, strtoupper($config['birth_file_type'])) : $content;
        $genderContent = !empty($config['gender_file_type'])
            ? self::findFileContent($fileList, strtoupper($config['gender_file_type'])) : $content;
        $birthMatches = array();
        $genderMatches = array();
        self::findFields($birthContent, $config['birth_field'], '', $birthMatches);
        self::findFields($genderContent, $config['gender_field'], '', $genderMatches);

        $path = $cccdMatches[0]['path'];
        $cccd = trim((string) $cccdMatches[0]['value']);
        if (empty($birthMatches) || empty($genderMatches)) {
            return array(self::makeError(
                $rule, $path, $cccd, 'error',
                'Không tìm thấy ' . $config['birth_field'] . ' hoặc ' . $config['gender_field'] . ' để kiểm tra CCCD.'
            ));
        }

        $birthValue = trim((string) $birthMatches[0]['value']);
        $genderValue = trim((string) $genderMatches[0]['value']);
        $yearText = function_exists('mb_substr')
            ? mb_substr($birthValue, 0, 4, 'UTF-8') : substr($birthValue, 0, 4);
        $cccdStart = isset($config['cccd_start']) ? (int) $config['cccd_start'] : 3;
        $actualCode = self::substringValue($cccd, $cccdStart, 1);
        $maleValues = isset($config['male_values']) && is_array($config['male_values'])
            ? array_map('strval', $config['male_values']) : array('1');
        $femaleValues = isset($config['female_values']) && is_array($config['female_values'])
            ? array_map('strval', $config['female_values']) : array('2');

        if (!preg_match('/^\d{4}$/', $yearText)) {
            return array(self::makeError($rule, $path, $cccd, 'error', 'Năm sinh không hợp lệ để kiểm tra CCCD.'));
        }
        $year = (int) $yearText;
        $century = intdiv($year, 100);
        if ($century < 19 || $century > 23) {
            return array(self::makeError($rule, $path, $cccd, 'error', 'Năm sinh nằm ngoài thế kỷ CCCD được hỗ trợ.'));
        }
        if (in_array($genderValue, $maleValues, true)) {
            $genderOffset = 0;
        } elseif (in_array($genderValue, $femaleValues, true)) {
            $genderOffset = 1;
        } else {
            return array(self::makeError($rule, $path, $cccd, 'error', 'Giá trị giới tính không hợp lệ để kiểm tra CCCD.'));
        }

        $expectedCode = (string) ((($century - 19) * 2) + $genderOffset);
        if ($actualCode !== $expectedCode) {
            return array(self::makeError(
                $rule,
                $path,
                $cccd,
                'error',
                $rule['error_message'] . ' (mã thực tế: ' . $actualCode . ', mã đúng: ' . $expectedCode . ')'
            ));
        }
        return array();
    }

    private static function validateApi(array $rule, array $matches, array $content, array $fileList, $apiService)
    {
        $config = json_decode($rule['rule_value'], true);
        if (!is_array($config) || empty($config['api_config_id']) || !isset($config['request_mapping'])) {
            return array(self::makeError($rule, $rule['field_name'], null, 'error', 'Cấu hình API rule không hợp lệ.'));
        }
        $path = !empty($matches) ? $matches[0]['path'] : $rule['field_name'];
        $value = !empty($matches) ? $matches[0]['value'] : null;
        $onError = isset($config['on_error']) ? strtoupper($config['on_error']) : 'FAIL';
        if ($apiService === null) {
            return $onError === 'SKIP' ? array() : array(self::makeError(
                $rule, $path, $value, $onError === 'WARNING' ? 'warning' : 'error', 'Dịch vụ API chưa được cấu hình.'
            ));
        }

        $payload = array('path' => array(), 'query' => array(), 'body' => array(), 'auto' => array());
        foreach ($config['request_mapping'] as $requestKey => $fieldConfig) {
            $targetField = is_array($fieldConfig) ? (isset($fieldConfig['field']) ? $fieldConfig['field'] : '') : $fieldConfig;
            $targetType = is_array($fieldConfig) && !empty($fieldConfig['file_type'])
                ? strtoupper($fieldConfig['file_type']) : null;
            if (is_array($fieldConfig) && array_key_exists('value', $fieldConfig)) {
                $mappedValue = $fieldConfig['value'];
            } else {
                $targetContent = $targetType ? self::findFileContent($fileList, $targetType) : $content;
                $fieldMatches = array();
                self::findFields($targetContent, $targetField, '', $fieldMatches);
                $mappedValue = !empty($fieldMatches) ? $fieldMatches[0]['value'] : null;
            }
            if (is_array($fieldConfig) && array_key_exists('start', $fieldConfig)) {
                $mappedValue = self::substringValue(
                    $mappedValue,
                    (int) $fieldConfig['start'],
                    array_key_exists('length', $fieldConfig) ? (int) $fieldConfig['length'] : null
                );
            }
            $location = is_array($fieldConfig) && isset($fieldConfig['in'])
                ? strtolower($fieldConfig['in']) : 'auto';
            if (!isset($payload[$location])) $location = 'auto';
            $payload[$location][$requestKey] = $mappedValue;
        }

        try {
            $result = $apiService->call((int) $config['api_config_id'], $payload);
            $responsePath = isset($config['response_field'])
                ? $config['response_field'] : $result['config']['response_field'];
            $actual = self::getValueByPath($result['response'], $responsePath);
            if (isset($config['response_substring']) && is_array($config['response_substring'])
                && array_key_exists('start', $config['response_substring'])) {
                $actual = self::substringValue(
                    $actual,
                    (int) $config['response_substring']['start'],
                    array_key_exists('length', $config['response_substring'])
                        ? (int) $config['response_substring']['length'] : null
                );
            }
            $expected = isset($config['expected']) ? $config['expected'] : true;
            $operator = isset($config['operator']) ? $config['operator'] : '=';
            $dataType = isset($config['data_type']) ? $config['data_type'] : 'string';
            if (!self::compareValues($actual, $expected, $operator, $dataType, null)) {
                return array(self::makeError($rule, $path, $value));
            }
            return array();
        } catch (RuntimeException $exception) {
            if ($onError === 'SKIP') {
                return array();
            }
            return array(self::makeError(
                $rule,
                $path,
                $value,
                $onError === 'WARNING' ? 'warning' : 'error',
                $rule['error_message'] . ' (' . $exception->getMessage() . ')'
            ));
        }
    }

    private static function findFileContent(array $fileList, $fileType)
    {
        foreach ($fileList as $file) {
            if (isset($file['LOAIHOSO']) && strtoupper($file['LOAIHOSO']) === $fileType) {
                return isset($file['NOIDUNGFILE']) ? $file['NOIDUNGFILE'] : array();
            }
        }
        return array();
    }

    private static function compareValues($left, $right, $operator, $dataType, $format)
    {
        $dataType = strtolower((string) $dataType);
        if ($dataType === 'number') {
            if (!is_numeric($left) || !is_numeric($right)) return false;
            $left = (float) $left;
            $right = (float) $right;
        } elseif ($dataType === 'date') {
            $format = $format ?: 'Ymd';
            $leftDate = DateTime::createFromFormat('!' . $format, (string) $left);
            $rightDate = DateTime::createFromFormat('!' . $format, (string) $right);
            if (!$leftDate || !$rightDate) return false;
            $left = $leftDate->getTimestamp();
            $right = $rightDate->getTimestamp();
        } elseif ($dataType === 'boolean') {
            $left = filter_var($left, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            $right = filter_var($right, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($left === null || $right === null) return false;
        } else {
            $left = (string) $left;
            $right = (string) $right;
        }

        switch ($operator) {
            case '=':
            case '==': return $left == $right;
            case '!=':
            case '<>': return $left != $right;
            case '>': return $left > $right;
            case '>=': return $left >= $right;
            case '<': return $left < $right;
            case '<=': return $left <= $right;
            default: return false;
        }
    }

    private static function getValueByPath(array $data, $path)
    {
        if ($path === null || trim($path) === '') return $data;
        $value = $data;
        foreach (explode('.', $path) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) return null;
            $value = $value[$segment];
        }
        return $value;
    }

    private static function substringValue($value, $start, $length = null)
    {
        $text = is_scalar($value) ? (string) $value : '';
        if (function_exists('mb_substr')) {
            return $length === null
                ? mb_substr($text, $start, null, 'UTF-8')
                : mb_substr($text, $start, $length, 'UTF-8');
        }
        return $length === null ? substr($text, $start) : substr($text, $start, $length);
    }

    private static function makeError(array $rule, $path, $value, $severity = 'error', $message = null)
    {
        return array(
            'field_name' => $rule['field_name'],
            'display_name' => $rule['display_name'],
            'rule_type' => $rule['rule_type'],
            'path' => $path,
            'value' => is_scalar($value) ? (string) $value : null,
            'message' => $message !== null ? $message : $rule['error_message'],
            'severity' => $severity
        );
    }
}
