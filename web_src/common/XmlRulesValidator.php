<?php

class XmlRulesValidator
{
    private static $multiValueFields = array(
        'MA_BENH_KT' => true
    );

    public static function validate(array $decodedContent, array $rules, $apiService = null, $tableLookupService = null)
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
        $requiredFieldsByType = self::collectRequiredFields($rulesByType);
        $nullableFieldsByType = self::collectNullableFields($rulesByType);

        $results = array();
        $hoSoList = isset($decodedContent['DANHSACHHOSO']['HOSO'])
            ? $decodedContent['DANHSACHHOSO']['HOSO']
            : array();

        foreach ($hoSoList as $hoSoIndex => $hoSo) {
            $fileList = isset($hoSo['FILEHOSO']) ? $hoSo['FILEHOSO'] : array();
            $fileContexts = self::buildFileContexts($fileList, $requiredFieldsByType);
            foreach ($fileList as $fileIndex => $file) {
                $fileType = isset($file['LOAIHOSO']) ? strtoupper($file['LOAIHOSO']) : '';
                $errors = array();
                $validFields = array();
                $fieldIndex = isset($fileContexts[$fileIndex]['fields'])
                    ? $fileContexts[$fileIndex]['fields'] : array();

                foreach (isset($rulesByType[$fileType]) ? $rulesByType[$fileType] : array() as $rule) {
                    $matches = isset($fieldIndex[$rule['field_name']])
                        ? $fieldIndex[$rule['field_name']] : array();
                    $ruleType = strtoupper(trim($rule['rule_type']));
                    $isNullable = isset($nullableFieldsByType[$fileType][$rule['field_name']]);

                    if ($ruleType === 'NULLABLE') {
                        foreach ($matches as $match) {
                            $validFields[] = self::makeValid($rule, $match['path'], $match['value']);
                        }
                        continue;
                    }

                    if ($isNullable) {
                        $matches = array_values(array_filter($matches, function ($match) {
                            return !self::isNullValue(isset($match['value']) ? $match['value'] : null);
                        }));
                        if (empty($matches)) {
                            continue;
                        }
                    }

                    if ($ruleType === 'FIELD_COMPARE') {
                        $ruleErrors = self::validateFieldCompare($rule, $matches, $fileContexts, $fileIndex);
                        $errors = array_merge($errors, $ruleErrors);
                        self::appendValidMatches($validFields, $rule, $matches, $ruleErrors);
                        continue;
                    }

                    if ($ruleType === 'CONDITIONAL_FIELD') {
                        $ruleErrors = self::validateConditionalField($rule, $matches, $fileContexts, $fileIndex);
                        $errors = array_merge($errors, $ruleErrors);
                        self::appendValidMatches($validFields, $rule, $matches, $ruleErrors);
                        continue;
                    }

                    if ($ruleType === 'SEQUENTIAL_FORMULA') {
                        $ruleErrors = self::validateSequentialFormula($rule, $fieldIndex);
                        $errors = array_merge($errors, $ruleErrors);
                        continue;
                    }

                    if ($ruleType === 'CROSS_FILE_ANY_MATCH') {
                        $applicable = false;
                        $ruleErrors = self::validateCrossFileAnyMatch(
                            $rule,
                            $matches,
                            $fileContexts,
                            $fileIndex,
                            $applicable
                        );
                        $errors = array_merge($errors, $ruleErrors);
                        if ($applicable) {
                            self::appendValidMatches($validFields, $rule, $matches, $ruleErrors);
                        }
                        continue;
                    }

                    if ($ruleType === 'CROSS_FILE_REQUIRED_BY_KEY') {
                        $ruleErrors = self::validateCrossFileRequiredByKey(
                            $rule,
                            $matches,
                            $fileContexts,
                            $fileIndex
                        );
                        $errors = array_merge($errors, $ruleErrors);
                        self::appendValidMatches($validFields, $rule, $matches, $ruleErrors);
                        continue;
                    }

                    if ($ruleType === 'VTYT_HAS_DVKT') {
                        $ruleErrors = self::validateVtytHasDvkt(
                            $rule,
                            $matches,
                            $fileContexts,
                            $fileIndex
                        );
                        $errors = array_merge($errors, $ruleErrors);
                        self::appendValidMatches($validFields, $rule, $matches, $ruleErrors);
                        continue;
                    }

                    if ($ruleType === 'TABLE_EXISTS') {
                        $lookupResults = array();
                        $ruleErrors = self::validateTableExists(
                            $rule,
                            $matches,
                            $tableLookupService,
                            $lookupResults,
                            $fileContexts,
                            $fileIndex
                        );
                        $errors = array_merge($errors, $ruleErrors);
                        self::appendValidMatches(
                            $validFields,
                            $rule,
                            $matches,
                            $ruleErrors,
                            false,
                            $lookupResults
                        );
                        continue;
                    }

                    if ($ruleType === 'API') {
                        $apiPassed = false;
                        $ruleErrors = self::validateApi(
                            $rule,
                            $matches,
                            $fileContexts,
                            $fileIndex,
                            $apiService,
                            $apiPassed
                        );
                        $errors = array_merge($errors, $ruleErrors);
                        if ($apiPassed) {
                            self::appendValidMatches($validFields, $rule, $matches, $ruleErrors, true);
                        }
                        continue;
                    }

                    if ($ruleType === 'SUBSTRING') {
                        $substringApplicable = false;
                        $ruleErrors = self::validateSubstring(
                            $rule,
                            $matches,
                            $fileContexts,
                            $fileIndex,
                            $substringApplicable
                        );
                        $errors = array_merge($errors, $ruleErrors);
                        if ($substringApplicable) {
                            self::appendValidMatches($validFields, $rule, $matches, $ruleErrors);
                        }
                        continue;
                    }

                    if ($ruleType === 'CCCD_GENDER_CENTURY') {
                        $ruleErrors = self::validateCccdGenderCentury($rule, $matches, $fileContexts, $fileIndex);
                        $errors = array_merge($errors, $ruleErrors);
                        self::appendValidMatches($validFields, $rule, $matches, $ruleErrors, true);
                        continue;
                    }

                    if ($ruleType === 'MAX_ITEMS') {
                        $maxItems = (int) $rule['rule_value'];
                        if ($maxItems < 1) {
                            $errors[] = self::makeError(
                                $rule,
                                $rule['field_name'],
                                null,
                                'error',
                                'Cấu hình MAX_ITEMS phải là số nguyên lớn hơn 0.'
                            );
                        } elseif (count($matches) > $maxItems) {
                            $values = array_map(function ($match) {
                                return isset($match['value']) && is_scalar($match['value'])
                                    ? (string) $match['value'] : '';
                            }, $matches);
                            $path = isset($matches[0]['path']) ? $matches[0]['path'] : $rule['field_name'];
                            $error = self::makeError($rule, $path, implode(';', $values));
                            $error['item_count'] = count($matches);
                            $error['max_items'] = $maxItems;
                            $errors[] = $error;
                        }
                        continue;
                    }

                    if ($ruleType === 'FORMULA') {
                        $content = isset($file['NOIDUNGFILE']) && is_array($file['NOIDUNGFILE'])
                            ? $file['NOIDUNGFILE'] : array();
                        $ruleErrors = self::validateFormula($rule, $content);
                        $errors = array_merge($errors, $ruleErrors);
                        if (!$ruleErrors) self::appendValidMatches($validFields, $rule, $matches, array());
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
                        } else {
                            $validFields[] = self::makeValid($rule, $match['path'], $match['value']);
                        }
                    }
                }

                $content = isset($file['NOIDUNGFILE']) && is_array($file['NOIDUNGFILE'])
                    ? $file['NOIDUNGFILE'] : array();
                self::interpolateErrorMessages($errors, $content);

                $result = array(
                    'hoso_index' => (int) $hoSoIndex,
                    'file_index' => (int) $fileIndex,
                    'file_type' => $fileType,
                    'errors' => $errors,
                    'valid_fields' => $validFields,
                    'valid_count' => count($validFields),
                    'error_count' => count(array_filter($errors, function ($error) {
                        return !isset($error['severity']) || $error['severity'] === 'error';
                    })),
                    'warning_count' => count(array_filter($errors, function ($error) {
                        return isset($error['severity']) && $error['severity'] === 'warning';
                    }))
                );
                $results[] = $result;
            }
        }

        return $results;
    }

    private static function interpolateErrorMessages(array &$errors, array $content)
    {
        foreach ($errors as &$error) {
            if (empty($error['message']) || strpos($error['message'], '{') === false) continue;
            $context = self::recordAtErrorPath(
                $content,
                isset($error['path']) ? (string) $error['path'] : ''
            );
            $errorValue = isset($error['value']) && is_scalar($error['value'])
                ? (string) $error['value'] : '';
            $error['message'] = preg_replace_callback(
                '/\{([A-Za-z0-9_]+)\}/',
                function ($match) use ($context, $errorValue) {
                    if (strtolower($match[1]) === 'value') return $errorValue;
                    $field = strtoupper($match[1]);
                    foreach ($context as $key => $value) {
                        if (strtoupper((string) $key) !== $field || !is_scalar($value)) continue;
                        return trim((string) $value);
                    }
                    return $match[0];
                },
                (string) $error['message']
            );
        }
        unset($error);
    }

    private static function recordAtErrorPath(array $content, $path)
    {
        preg_match_all('/(?:^|\.)([^.\[\]]+)|\[(\d+)\]/', (string) $path, $matches, PREG_SET_ORDER);
        if (count($matches) > 0) array_pop($matches); // Bá» tÃªn trÆ°á»ng Ä‘ang lá»—i.
        $current = $content;
        foreach ($matches as $match) {
            $key = isset($match[1]) && $match[1] !== '' ? $match[1] : (int) $match[2];
            if (!is_array($current) || !array_key_exists($key, $current)) return array();
            $current = $current[$key];
        }
        return is_array($current) ? $current : array();
    }

    private static function collectNullableFields(array $rulesByType)
    {
        $nullable = array();
        foreach ($rulesByType as $fileType => $rules) {
            foreach ($rules as $rule) {
                if (strtoupper(trim($rule['rule_type'])) !== 'NULLABLE') {
                    continue;
                }
                if (!isset($nullable[$fileType])) {
                    $nullable[$fileType] = array();
                }
                $nullable[$fileType][$rule['field_name']] = true;
            }
        }
        return $nullable;
    }

    private static function isNullValue($value)
    {
        return $value === null || (is_string($value) && trim($value) === '');
    }

    private static function collectRequiredFields(array $rulesByType)
    {
        $required = array();
        foreach ($rulesByType as $fileType => $rules) {
            foreach ($rules as $rule) {
                self::addRequiredField($required, $fileType, $rule['field_name']);
                $ruleType = strtoupper(trim($rule['rule_type']));
                if (!in_array($ruleType, array(
                    'FIELD_COMPARE', 'CONDITIONAL_FIELD', 'CROSS_FILE_REQUIRED_BY_KEY',
                    'CCCD_GENDER_CENTURY', 'API', 'SUBSTRING',
                    'TABLE_EXISTS', 'VTYT_HAS_DVKT', 'CROSS_FILE_ANY_MATCH'
                ), true)) {
                    continue;
                }

                if ($ruleType === 'VTYT_HAS_DVKT') {
                    self::addRequiredField($required, $fileType, 'MA_DICH_VU');
                    self::addRequiredField($required, $fileType, 'MA_VAT_TU');
                    continue;
                }

                $config = json_decode($rule['rule_value'], true);
                if (!is_array($config)) {
                    continue;
                }

                if ($ruleType === 'FIELD_COMPARE' && !empty($config['other_field'])) {
                    $targetType = !empty($config['other_file_type'])
                        ? strtoupper($config['other_file_type']) : $fileType;
                    self::addRequiredField($required, $targetType, $config['other_field']);
                } elseif ($ruleType === 'CONDITIONAL_FIELD' && !empty($config['other_field'])) {
                    $targetType = !empty($config['other_file_type'])
                        ? strtoupper($config['other_file_type']) : $fileType;
                    self::addRequiredField($required, $targetType, $config['other_field']);
                } elseif ($ruleType === 'CROSS_FILE_ANY_MATCH') {
                    $targetType = !empty($config['target_file_type'])
                        ? strtoupper($config['target_file_type']) : $fileType;
                    $targetFields = isset($config['target_fields']) && is_array($config['target_fields'])
                        ? $config['target_fields'] : array();
                    foreach ($targetFields as $targetField) {
                        self::addRequiredField($required, $targetType, $targetField);
                    }
                } elseif ($ruleType === 'CROSS_FILE_REQUIRED_BY_KEY') {
                    if (!empty($config['source_key'])) {
                        self::addRequiredField($required, $fileType, $config['source_key']);
                    }
                    $when = isset($config['when_match']) && is_array($config['when_match'])
                        ? $config['when_match'] : array();
                    $targetType = !empty($when['file_type'])
                        ? strtoupper($when['file_type']) : $fileType;
                    if (!empty($when['target_key'])) {
                        self::addRequiredField($required, $targetType, $when['target_key']);
                    }
                    if (!empty($when['field'])) {
                        self::addRequiredField($required, $targetType, $when['field']);
                    }
                } elseif ($ruleType === 'SUBSTRING' && !empty($config['when']['field'])) {
                    $targetType = !empty($config['when']['file_type'])
                        ? strtoupper($config['when']['file_type']) : $fileType;
                    self::addRequiredField($required, $targetType, $config['when']['field']);
                } elseif ($ruleType === 'TABLE_EXISTS' && !empty($config['conditions_from_field'])
                    && is_array($config['conditions_from_field'])) {
                    foreach ($config['conditions_from_field'] as $fieldConfig) {
                        $fieldName = is_array($fieldConfig)
                            ? (isset($fieldConfig['field']) ? $fieldConfig['field'] : '') : $fieldConfig;
                        $targetType = is_array($fieldConfig) && !empty($fieldConfig['file_type'])
                            ? strtoupper($fieldConfig['file_type']) : $fileType;
                        if ($fieldName !== '') self::addRequiredField($required, $targetType, $fieldName);
                    }
                } elseif ($ruleType === 'CCCD_GENDER_CENTURY') {
                    if (!empty($config['birth_field'])) {
                        $targetType = !empty($config['birth_file_type'])
                            ? strtoupper($config['birth_file_type']) : $fileType;
                        self::addRequiredField($required, $targetType, $config['birth_field']);
                    }
                    if (!empty($config['gender_field'])) {
                        $targetType = !empty($config['gender_file_type'])
                            ? strtoupper($config['gender_file_type']) : $fileType;
                        self::addRequiredField($required, $targetType, $config['gender_field']);
                    }
                } elseif ($ruleType === 'API' && isset($config['request_mapping'])
                    && is_array($config['request_mapping'])) {
                    foreach ($config['request_mapping'] as $fieldConfig) {
                        if (is_array($fieldConfig) && array_key_exists('value', $fieldConfig)) {
                            continue;
                        }
                        $fieldName = is_array($fieldConfig)
                            ? (isset($fieldConfig['field']) ? $fieldConfig['field'] : '') : $fieldConfig;
                        $targetType = is_array($fieldConfig) && !empty($fieldConfig['file_type'])
                            ? strtoupper($fieldConfig['file_type']) : $fileType;
                        self::addRequiredField($required, $targetType, $fieldName);
                    }
                }
            }
        }
        return $required;
    }

    private static function addRequiredField(array &$required, $fileType, $fieldName)
    {
        $fileType = strtoupper(trim((string) $fileType));
        $fieldName = trim((string) $fieldName);
        if ($fileType === '' || $fieldName === '') {
            return;
        }
        if (!isset($required[$fileType])) {
            $required[$fileType] = array();
        }
        $required[$fileType][$fieldName] = true;
    }

    private static function buildFileContexts(array $fileList, array $requiredFieldsByType)
    {
        $contexts = array();
        foreach ($fileList as $fileIndex => $file) {
            $fileType = isset($file['LOAIHOSO']) ? strtoupper($file['LOAIHOSO']) : '';
            $content = isset($file['NOIDUNGFILE']) ? $file['NOIDUNGFILE'] : array();
            $fields = array();
            $required = isset($requiredFieldsByType[$fileType])
                ? $requiredFieldsByType[$fileType] : array();
            if (!empty($required)) {
                self::indexFields($content, $required, '', $fields);
            }
            $contexts[$fileIndex] = array('file_type' => $fileType, 'fields' => $fields);
        }
        return $contexts;
    }

    private static function indexFields($data, array $required, $path, array &$fields)
    {
        if (!is_array($data)) {
            return;
        }

        foreach ($data as $key => $value) {
            $key = (string) $key;
            $currentPath = $path === ''
                ? $key
                : ($key !== '' && ctype_digit($key) ? $path . '[' . $key . ']' : $path . '.' . $key);

            if (isset($required[$key])) {
                if (!isset($fields[$key])) {
                    $fields[$key] = array();
                }
                if (isset(self::$multiValueFields[$key])) {
                    $values = self::splitMultiValueField($value);
                    foreach ($values as $itemIndex => $itemValue) {
                        $fields[$key][] = array(
                            'path' => $currentPath,
                            'value' => $itemValue,
                            'item_index' => $itemIndex
                        );
                    }
                } else {
                    $fields[$key][] = array('path' => $currentPath, 'value' => $value);
                }
            }
            if (is_array($value)) {
                self::indexFields($value, $required, $currentPath, $fields);
            }
        }
    }

    private static function splitMultiValueField($value)
    {
        $sourceValues = is_array($value) ? $value : array($value);
        $result = array();
        foreach ($sourceValues as $sourceValue) {
            if (!is_scalar($sourceValue)) {
                continue;
            }
            $parts = preg_split('/\s*[;,|]\s*/u', trim((string) $sourceValue));
            foreach ($parts as $part) {
                $part = trim($part);
                if ($part !== '') {
                    $result[] = $part;
                }
            }
        }
        return $result;
    }

    private static function indexedMatches(array $fileContexts, $currentFileIndex, $targetFileType, $fieldName)
    {
        $targetIndex = $currentFileIndex;
        if ($targetFileType !== null && trim((string) $targetFileType) !== '') {
            $targetType = strtoupper($targetFileType);
            $targetIndex = null;
            foreach ($fileContexts as $fileIndex => $context) {
                if ($context['file_type'] === $targetType) {
                    $targetIndex = $fileIndex;
                    break;
                }
            }
        }

        if ($targetIndex === null || !isset($fileContexts[$targetIndex]['fields'][$fieldName])) {
            return array();
        }
        return $fileContexts[$targetIndex]['fields'][$fieldName];
    }

    private static function appendValidMatches(
        array &$validFields,
        array $rule,
        array $matches,
        array $ruleErrors,
        $firstOnly = false,
        array $lookupResults = array()
    ) {
        if (empty($matches)) {
            return;
        }

        foreach ($ruleErrors as $error) {
            if (!isset($error['message']) || $error['message'] !== $rule['error_message']) {
                return;
            }
        }

        $targets = $firstOnly ? array($matches[0]) : $matches;
        foreach ($targets as $match) {
            $hasError = false;
            foreach ($ruleErrors as $error) {
                $samePath = isset($error['path']) && $error['path'] === $match['path'];
                $sameValue = !array_key_exists('value', $error)
                    || (string) $error['value'] === (is_scalar($match['value']) ? (string) $match['value'] : '');
                if ($samePath && $sameValue) {
                    $hasError = true;
                    break;
                }
            }
            if (!$hasError) {
                $lookupResult = isset($lookupResults[$match['path']]) ? $lookupResults[$match['path']] : null;
                $validFields[] = self::makeValid($rule, $match['path'], $match['value'], $lookupResult);
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

    private static function validateFormula(array $rule, array $content)
    {
        $config = json_decode($rule['rule_value'], true);
        if (!is_array($config)
            || (empty($config['expression']) && empty($config['condition']))) {
            return array(self::makeError($rule, $rule['field_name'], null, 'error', 'Cấu hình FORMULA không hợp lệ.'));
        }

        // Conditional formulas such as A + B <= C do not have a result field.
        if (!empty($config['condition']) && empty($config['expression'])) {
            $condition = $config['condition'];
            $values = isset($condition['values']) && is_array($condition['values'])
                ? $condition['values'] : array();
            $fields = array_values(array_unique(self::formulaFields($condition)));
            if (count($values) !== 2 || !$fields
                || strtoupper((string) ($condition['operation'] ?? '')) !== 'LTE') {
                return array(self::makeError($rule, $rule['field_name'], null, 'error', 'Cấu hình FORMULA không hợp lệ.'));
            }
            $records = array();
            self::findFormulaRecords($content, $fields[0], $fields, '', $records);
            $errors = array();
            $tolerance = isset($config['tolerance']) ? max(0, (float) $config['tolerance']) : 0.01;
            foreach ($records as $item) {
                $evaluationError = null;
                $left = self::evaluateFormulaNode($values[0], $item['record'], $evaluationError);
                $right = self::evaluateFormulaNode($values[1], $item['record'], $evaluationError);
                if ($evaluationError !== null || $left > ($right + $tolerance)) {
                    $errors[] = self::makeError(
                        $rule,
                        $item['path'] . '.' . $rule['field_name'],
                        $left,
                        'error',
                        self::formulaErrorMessage($rule['error_message'], $item['record'])
                    );
                }
            }
            return $errors;
        }
        $resultField = !empty($config['result_field'])
            ? trim((string) $config['result_field']) : trim((string) $rule['field_name']);
        $errorField = !empty($config['error_field'])
            ? trim((string) $config['error_field']) : $resultField;
        $fields = array_values(array_unique(self::formulaFields($config['expression'])));
        if ($resultField === '' || !$fields) {
            return array(self::makeError($rule, $rule['field_name'], null, 'error', 'FORMULA thiếu trường kết quả hoặc biến đầu vào.'));
        }
        $tolerance = isset($config['tolerance']) ? max(0, (float) $config['tolerance']) : 0.01;
        $round = isset($config['round']) ? max(0, min(10, (int) $config['round'])) : null;
        $records = array();
        self::findFormulaRecords($content, $resultField, $fields, '', $records);
        if (!$records) {
            return array(self::makeError($rule, $resultField, null, 'error', 'Không tìm thấy dòng dữ liệu đủ trường để kiểm tra FORMULA.'));
        }

        $errors = array();
        foreach ($records as $item) {
            $record = $item['record'];
            $errorValue = isset($record[$errorField]) && is_scalar($record[$errorField])
                ? trim((string) $record[$errorField]) : '';
            $actualText = isset($record[$resultField]) && is_scalar($record[$resultField])
                ? trim((string) $record[$resultField]) : '';
            $evaluationError = null;
            $expected = self::evaluateFormulaNode($config['expression'], $record, $evaluationError);
            if ($evaluationError !== null || $actualText === '' || !is_numeric($actualText)) {
                $message = $evaluationError !== null ? $evaluationError : 'Giá trị ' . $resultField . ' không phải là số.';
                $errors[] = self::makeError($rule, $item['path'] . '.' . $errorField, $errorValue, 'error', $message);
                continue;
            }
            if ($round !== null) $expected = round($expected, $round);
            $actual = (float) $actualText;
            $difference = abs($actual - $expected);
            if ($difference > $tolerance) {
                $message = self::formulaErrorMessage($rule['error_message'], $record);
                $error = self::makeError(
                    $rule,
                    $item['path'] . '.' . $errorField,
                    $errorValue,
                    'error',
                    $message
                );
                $error['expected_value'] = $expected;
                $error['difference'] = $difference;
                $error['tolerance'] = $tolerance;
                $errors[] = $error;
            }
        }
        return $errors;
    }

    private static function formulaErrorMessage($message, array $record)
    {
        return preg_replace_callback('/\{([A-Z0-9_]+)\}/', function ($match) use ($record) {
            $field = $match[1];
            if (!isset($record[$field]) || !is_scalar($record[$field])) return '';
            return trim((string) $record[$field]);
        }, (string) $message);
    }

    private static function formulaFields($node)
    {
        if (!is_array($node)) return array();
        if (isset($node['field']) && is_scalar($node['field'])) return array(trim((string) $node['field']));
        $fields = array();
        $values = isset($node['values']) && is_array($node['values']) ? $node['values'] : array();
        foreach ($values as $value) $fields = array_merge($fields, self::formulaFields($value));
        return $fields;
    }

    private static function findFormulaRecords($data, $resultField, array $fields, $path, array &$records)
    {
        if (!is_array($data)) return;
        $hasFields = array_key_exists($resultField, $data);
        foreach ($fields as $field) {
            if (!array_key_exists($field, $data)) { $hasFields = false; break; }
        }
        if ($hasFields) $records[] = array('record' => $data, 'path' => $path !== '' ? $path : 'NOIDUNGFILE');
        foreach ($data as $key => $value) {
            if (!is_array($value)) continue;
            $childPath = $path === '' ? (string) $key
                : (ctype_digit((string) $key) ? $path . '[' . $key . ']' : $path . '.' . $key);
            self::findFormulaRecords($value, $resultField, $fields, $childPath, $records);
        }
    }

    private static function evaluateFormulaNode($node, array $record, &$error)
    {
        if (is_int($node) || is_float($node) || (is_string($node) && is_numeric($node))) return (float) $node;
        if (!is_array($node)) { $error = 'Thành phần công thức không hợp lệ.'; return 0; }
        if (isset($node['field'])) {
            $field = trim((string) $node['field']);
            $value = isset($record[$field]) && is_scalar($record[$field]) ? trim((string) $record[$field]) : '';
            if ($value === '' || !is_numeric($value)) {
                $error = 'Trường ' . $field . ' không có giá trị số hợp lệ.';
                return 0;
            }
            return (float) $value;
        }
        $operation = isset($node['operation']) ? strtoupper(trim((string) $node['operation'])) : '';
        $nodes = isset($node['values']) && is_array($node['values']) ? $node['values'] : array();
        if (!$nodes || !in_array($operation, array('ADD', 'SUM', 'SUBTRACT', 'MULTIPLY', 'DIVIDE'), true)) {
            $error = 'Phép toán FORMULA không hợp lệ.';
            return 0;
        }
        $values = array();
        foreach ($nodes as $child) {
            $values[] = self::evaluateFormulaNode($child, $record, $error);
            if ($error !== null) return 0;
        }
        if ($operation === 'ADD' || $operation === 'SUM') return array_sum($values);
        $result = array_shift($values);
        foreach ($values as $value) {
            if ($operation === 'SUBTRACT') $result -= $value;
            elseif ($operation === 'MULTIPLY') $result *= $value;
            elseif ($operation === 'DIVIDE') {
                if (abs($value) < 0.000000000001) { $error = 'Không thể chia cho 0 trong FORMULA.'; return 0; }
                $result /= $value;
            }
        }
        return $result;
    }

    private static function validateFieldCompare(array $rule, array $matches, array $fileContexts, $currentFileIndex)
    {
        $config = json_decode($rule['rule_value'], true);
        if (!is_array($config) || empty($config['other_field']) || empty($config['operator'])) {
            return array(self::makeError($rule, $rule['field_name'], null, 'error', 'Cấu hình FIELD_COMPARE không hợp lệ.'));
        }
        if (empty($matches)) {
            return array(self::makeError($rule, $rule['field_name'], null));
        }

        $otherMatches = self::indexedMatches(
            $fileContexts,
            $currentFileIndex,
            !empty($config['other_file_type']) ? $config['other_file_type'] : null,
            $config['other_field']
        );
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

    private static function validateConditionalField(
        array $rule,
        array $matches,
        array $fileContexts,
        $currentFileIndex
    ) {
        $config = json_decode($rule['rule_value'], true);
        if (!is_array($config) || !array_key_exists('when_value', $config)
            || empty($config['other_field']) || !array_key_exists('expected', $config)) {
            return array(self::makeError(
                $rule, $rule['field_name'], null, 'error', 'Cấu hình CONDITIONAL_FIELD không hợp lệ.'
            ));
        }
        if (empty($matches)) return array();

        $otherMatches = self::indexedMatches(
            $fileContexts,
            $currentFileIndex,
            !empty($config['other_file_type']) ? $config['other_file_type'] : null,
            $config['other_field']
        );
        $errors = array();
        foreach ($matches as $match) {
            if (!self::compareValues(
                $match['value'],
                $config['when_value'],
                isset($config['when_operator']) ? $config['when_operator'] : '=',
                isset($config['data_type']) ? $config['data_type'] : 'string',
                null
            )) {
                continue;
            }
            $sameRowPath = preg_replace('/\.[^.]+$/', '.' . $config['other_field'], $match['path']);
            $rowMatches = array_values(array_filter($otherMatches, function ($otherMatch) use ($sameRowPath) {
                return isset($otherMatch['path']) && $otherMatch['path'] === $sameRowPath;
            }));
            if (empty($rowMatches) && count($otherMatches) === 1) {
                $rowMatches = $otherMatches;
            }
            $passed = false;
            foreach ($rowMatches as $otherMatch) {
                if (self::compareValues(
                    $otherMatch['value'],
                    $config['expected'],
                    isset($config['operator']) ? $config['operator'] : '=',
                    isset($config['other_data_type']) ? $config['other_data_type'] : 'string',
                    null
                )) {
                    $passed = true;
                    break;
                }
            }
            if (!$passed) {
                $error = self::makeError($rule, $match['path'], $match['value']);
                $error['other_field'] = $config['other_field'];
                $error['other_value'] = isset($rowMatches[0]['value'])
                    ? (string) $rowMatches[0]['value'] : null;
                $error['expected'] = (string) $config['expected'];
                $errors[] = $error;
            }
        }
        return $errors;
    }

    private static function validateSequentialFormula(array $rule, array $fieldIndex)
    {
        $config = json_decode($rule['rule_value'], true);
        if (!is_array($config) || empty($config['group_field']) || empty($config['order_field'])
            || empty($config['amount_field']) || !isset($config['group_value'])
            || !isset($config['first_value']) || !isset($config['next_percent'])) return array();
        $groups = array();
        $groupMatches = isset($fieldIndex[$config['group_field']]) ? $fieldIndex[$config['group_field']] : array();
        $orderMatches = isset($fieldIndex[$config['order_field']]) ? $fieldIndex[$config['order_field']] : array();
        $amountMatches = isset($fieldIndex[$config['amount_field']]) ? $fieldIndex[$config['amount_field']] : array();
        foreach ($groupMatches as $group) {
            if ((string) $group['value'] !== (string) $config['group_value']) continue;
            $parent = preg_replace('/\.[^.]+$/', '', $group['path']);
            $order = array_values(array_filter($orderMatches, function ($item) use ($parent) {
                return preg_replace('/\.[^.]+$/', '', $item['path']) === $parent;
            }));
            $amount = array_values(array_filter($amountMatches, function ($item) use ($parent) {
                return preg_replace('/\.[^.]+$/', '', $item['path']) === $parent;
            }));
            if (!$order || !$amount) continue;
            $groups[] = array('order' => $order[0], 'amount' => $amount[0]);
        }
        usort($groups, function ($a, $b) { return strcmp((string) $a['order']['value'], (string) $b['order']['value']); });
        $errors = array();
        foreach ($groups as $index => $item) {
            $expected = $index === 0 ? (float) $config['first_value']
                : (float) $config['first_value'] * ((float) $config['next_percent'] / 100);
            if (!is_numeric($item['amount']['value']) || abs((float) $item['amount']['value'] - $expected) > (float) ($config['tolerance'] ?? 0.01)) {
                $errors[] = self::makeError($rule, $item['amount']['path'], $item['amount']['value']);
            }
        }
        return $errors;
    }

    private static function validateCrossFileAnyMatch(
        array $rule,
        array $matches,
        array $fileContexts,
        $currentFileIndex,
        &$applicable
    ) {
        $applicable = false;
        $config = json_decode($rule['rule_value'], true);
        $targetFields = is_array($config) && isset($config['target_fields'])
            && is_array($config['target_fields']) ? $config['target_fields'] : array();
        $whenValues = is_array($config) && isset($config['when_values']) && is_array($config['when_values'])
            ? $config['when_values'] : array();
        $expectedValues = is_array($config) && isset($config['expected_values'])
            && is_array($config['expected_values']) ? $config['expected_values'] : array();
        if (!is_array($config) || empty($config['target_file_type']) || empty($targetFields)
            || empty($whenValues) || empty($expectedValues)) {
            return array(self::makeError(
                $rule,
                $rule['field_name'],
                null,
                'error',
                'Cáº¥u hÃ¬nh CROSS_FILE_ANY_MATCH khÃ´ng há»£p lá»‡.'
            ));
        }

        $normalise = function ($value) {
            $text = is_scalar($value) ? trim((string) $value) : '';
            return function_exists('mb_strtoupper') ? mb_strtoupper($text, 'UTF-8') : strtoupper($text);
        };
        $whenValues = array_map($normalise, $whenValues);
        $targetValues = array();
        foreach ($targetFields as $targetField) {
            foreach (self::indexedMatches($fileContexts, $currentFileIndex, $config['target_file_type'], $targetField) as $targetMatch) {
                $value = $normalise($targetMatch['value']);
                if ($value !== '') $targetValues[] = $value;
            }
        }

        $errors = array();
        foreach ($matches as $match) {
            $sourceValue = $normalise($match['value']);
            if (!in_array($sourceValue, $whenValues, true)) {
                continue;
            }
            $applicable = true;
            $allowedValues = $expectedValues;
            foreach (isset($config['overrides']) && is_array($config['overrides']) ? $config['overrides'] : array() as $override) {
                if (!is_array($override) || !isset($override['when_values'])
                    || !is_array($override['when_values']) || !isset($override['expected_values'])
                    || !is_array($override['expected_values'])) {
                    continue;
                }
                if (in_array($sourceValue, array_map($normalise, $override['when_values']), true)) {
                    $allowedValues = $override['expected_values'];
                    break;
                }
            }
            if (!self::hasConfiguredMatch(
                $targetValues,
                $allowedValues,
                isset($config['match_type']) ? $config['match_type'] : 'EXACT'
            )) {
                $errors[] = self::makeError($rule, $match['path'], $match['value']);
            }
        }
        return $errors;
    }

    private static function hasConfiguredMatch(array $targetValues, array $expectedValues, $matchType)
    {
        $matchType = strtoupper(trim((string) $matchType));
        foreach ($expectedValues as $expectedValue) {
            $expectedValue = is_scalar($expectedValue) ? trim((string) $expectedValue) : '';
            if ($expectedValue === '') continue;
            $expectedValue = function_exists('mb_strtoupper')
                ? mb_strtoupper($expectedValue, 'UTF-8') : strtoupper($expectedValue);
            foreach ($targetValues as $targetValue) {
                if ($matchType === 'PREFIX' && strpos($targetValue, $expectedValue) === 0) {
                    return true;
                }
                if ($matchType === 'REGEX' && @preg_match($expectedValue, $targetValue) === 1) {
                    return true;
                }
                if ($matchType !== 'PREFIX' && $matchType !== 'REGEX' && $targetValue === $expectedValue) {
                    return true;
                }
            }
        }
        return false;
    }

    private static function validateCrossFileRequiredByKey(
        array $rule,
        array $matches,
        array $fileContexts,
        $currentFileIndex
    ) {
        $config = json_decode($rule['rule_value'], true);
        $when = is_array($config) && isset($config['when_match']) && is_array($config['when_match'])
            ? $config['when_match'] : array();
        if (!is_array($config) || empty($config['source_key']) || empty($when['file_type'])
            || empty($when['target_key']) || empty($when['field'])
            || !array_key_exists('value', $when)) {
            return array(self::makeError(
                $rule,
                $rule['field_name'],
                null,
                'error',
                'Cấu hình CROSS_FILE_REQUIRED_BY_KEY không hợp lệ.'
            ));
        }

        $sourceKeys = self::indexedMatches(
            $fileContexts,
            $currentFileIndex,
            null,
            $config['source_key']
        );
        $targetKeys = self::indexedMatches(
            $fileContexts,
            $currentFileIndex,
            $when['file_type'],
            $when['target_key']
        );
        $conditions = self::indexedMatches(
            $fileContexts,
            $currentFileIndex,
            $when['file_type'],
            $when['field']
        );

        $qualifyingKeys = array();
        foreach ($targetKeys as $targetKey) {
            $targetParent = self::parentPath($targetKey['path']);
            foreach ($conditions as $condition) {
                if (self::parentPath($condition['path']) !== $targetParent) continue;
                if (self::compareValues(
                    $condition['value'],
                    $when['value'],
                    isset($when['operator']) ? $when['operator'] : '=',
                    isset($when['data_type']) ? $when['data_type'] : 'string',
                    null
                )) {
                    $key = is_scalar($targetKey['value']) ? trim((string) $targetKey['value']) : '';
                    if ($key !== '') $qualifyingKeys[$key] = true;
                }
                break;
            }
        }

        $errors = array();
        $foundSourceKeys = array();
        foreach ($sourceKeys as $sourceKey) {
            $key = is_scalar($sourceKey['value']) ? trim((string) $sourceKey['value']) : '';
            if ($key === '' || !isset($qualifyingKeys[$key])) continue;
            $foundSourceKeys[$key] = true;
            $sourceParent = self::parentPath($sourceKey['path']);
            $requiredMatch = null;
            foreach ($matches as $match) {
                if (self::parentPath($match['path']) === $sourceParent) {
                    $requiredMatch = $match;
                    break;
                }
            }
            $requiredValue = $requiredMatch !== null ? $requiredMatch['value'] : null;
            if (self::isNullValue($requiredValue)) {
                $path = $sourceParent === ''
                    ? $rule['field_name'] : $sourceParent . '.' . $rule['field_name'];
                $error = self::makeError($rule, $path, $requiredValue);
                $error['key_field'] = $config['source_key'];
                $error['key_value'] = $key;
                $errors[] = $error;
            }
        }

        if (!empty($config['error_when_source_missing'])) {
            foreach ($qualifyingKeys as $key => $_) {
                if (isset($foundSourceKeys[$key])) continue;
                $error = self::makeError(
                    $rule,
                    $rule['field_name'] . '[' . $config['source_key'] . '=' . $key . ']',
                    null
                );
                $error['key_field'] = $config['source_key'];
                $error['key_value'] = $key;
                $error['source_missing'] = true;
                $errors[] = $error;
            }
        }
        return $errors;
    }

    private static function parentPath($path)
    {
        $path = (string) $path;
        $position = strrpos($path, '.');
        return $position === false ? '' : substr($path, 0, $position);
    }

    private static function validateVtytHasDvkt(
        array $rule,
        array $packageMatches,
        array $fileContexts,
        $currentFileIndex
    ) {
        $serviceMatches = self::indexedMatches(
            $fileContexts,
            $currentFileIndex,
            null,
            'MA_DICH_VU'
        );
        $materialMatches = self::indexedMatches(
            $fileContexts,
            $currentFileIndex,
            null,
            'MA_VAT_TU'
        );

        $servicesByParent = array();
        $materialsByParent = array();
        foreach ($serviceMatches as $match) {
            $servicesByParent[self::parentPath($match['path'])] = $match;
        }
        foreach ($materialMatches as $match) {
            $materialsByParent[self::parentPath($match['path'])] = $match;
        }

        $dvktCodes = array();
        foreach ($servicesByParent as $parent => $serviceMatch) {
            $materialValue = isset($materialsByParent[$parent])
                ? $materialsByParent[$parent]['value'] : null;
            $serviceCode = is_scalar($serviceMatch['value'])
                ? trim((string) $serviceMatch['value']) : '';
            if ($serviceCode !== '' && self::isNullValue($materialValue)) {
                $dvktCodes[$serviceCode] = true;
            }
        }

        $errors = array();
        foreach ($packageMatches as $packageMatch) {
            if (self::isNullValue($packageMatch['value'])) continue;
            $parent = self::parentPath($packageMatch['path']);
            $materialValue = isset($materialsByParent[$parent])
                ? $materialsByParent[$parent]['value'] : null;
            if (self::isNullValue($materialValue)) continue;

            $serviceCode = isset($servicesByParent[$parent])
                && is_scalar($servicesByParent[$parent]['value'])
                ? trim((string) $servicesByParent[$parent]['value']) : '';
            if ($serviceCode !== '' && isset($dvktCodes[$serviceCode])) continue;

            $errorPath = isset($servicesByParent[$parent])
                ? $servicesByParent[$parent]['path']
                : ($parent === '' ? 'MA_DICH_VU' : $parent . '.MA_DICH_VU');
            $error = self::makeError(
                $rule,
                $errorPath,
                $serviceCode
            );
            $error['field_name'] = 'MA_DICH_VU';
            $error['display_name'] = 'Mã dịch vụ';
            $error['service_code'] = $serviceCode;
            $error['material_code'] = is_scalar($materialValue)
                ? trim((string) $materialValue) : '';
            $errors[] = $error;
        }
        return $errors;
    }

    private static function validateTableExists(
        array $rule,
        array $matches,
        $tableLookupService,
        array &$lookupResults = array(),
        array $fileContexts = array(),
        $currentFileIndex = 0
    )
    {
        $config = json_decode($rule['rule_value'], true);
        if (!is_array($config) || empty($config['table'])
            || (empty($config['column']) && empty($config['value_mappings']))) {
            return array(self::makeError(
                $rule,
                $rule['field_name'],
                null,
                'error',
                'Cấu hình TABLE_EXISTS không hợp lệ.'
            ));
        }
        if (empty($matches) && !empty($config['allow_empty'])) {
            return array();
        }
        if (empty($matches)) {
            return array(self::makeError($rule, $rule['field_name'], null));
        }
        if ($tableLookupService === null) {
            return array(self::makeError(
                $rule,
                $matches[0]['path'],
                $matches[0]['value'],
                'error',
                'Dịch vụ tra cứu bảng chưa được cấu hình.'
            ));
        }

        if (method_exists($tableLookupService, 'preload')
            && empty($config['conditions_from_substring'])
            && empty($config['conditions_from_field'])) {
            $preloadValues = array();
            foreach ($matches as $preloadMatch) {
                if (!empty($config['allow_empty']) && self::isNullValue($preloadMatch['value'])) continue;
                $preloadValue = $preloadMatch['value'];
                if (isset($config['remove_suffix'])) {
                    $suffixes = is_array($config['remove_suffix'])
                        ? array_values($config['remove_suffix']) : array($config['remove_suffix']);
                    usort($suffixes, function ($left, $right) {
                        return strlen((string) $right) - strlen((string) $left);
                    });
                    $text = is_scalar($preloadValue) ? (string) $preloadValue : '';
                    foreach ($suffixes as $suffix) {
                        $suffix = (string) $suffix;
                        if ($suffix !== '' && strlen($text) >= strlen($suffix)
                            && substr($text, -strlen($suffix)) === $suffix) {
                            $preloadValue = substr($text, 0, -strlen($suffix));
                            break;
                        }
                    }
                }
                if (isset($config['value_substring']) && is_array($config['value_substring'])
                    && array_key_exists('start', $config['value_substring'])) {
                    $preloadValue = self::substringValue(
                        $preloadMatch['value'],
                        (int) $config['value_substring']['start'],
                        array_key_exists('length', $config['value_substring'])
                            ? (int) $config['value_substring']['length'] : null
                    );
                }
                $preloadValue = self::transformTableLookupValue(
                    $preloadValue,
                    isset($config['value_transform']) ? $config['value_transform'] : ''
                );
                if (isset($config['value_separator']) && is_string($config['value_separator'])
                    && $config['value_separator'] !== '') {
                    foreach (explode($config['value_separator'], (string) $preloadValue) as $part) {
                        $part = trim($part);
                        if ($part !== '') $preloadValues[] = $part;
                    }
                } else {
                    $preloadValues[] = $preloadValue;
                }
            }
            $tableLookupService->preload($preloadValues, $config);
        }

        $errors = array();
        foreach ($matches as $match) {
            if (!empty($config['allow_empty']) && self::isNullValue($match['value'])) {
                continue;
            }
            $lookupValue = $match['value'];
            $lookupConfig = $config;
            if (isset($config['remove_suffix'])) {
                $suffixes = is_array($config['remove_suffix'])
                    ? array_values($config['remove_suffix']) : array($config['remove_suffix']);
                usort($suffixes, function ($left, $right) {
                    return strlen((string) $right) - strlen((string) $left);
                });
                $text = is_scalar($lookupValue) ? (string) $lookupValue : '';
                foreach ($suffixes as $suffix) {
                    $suffix = (string) $suffix;
                    if ($suffix !== '' && strlen($text) >= strlen($suffix)
                        && substr($text, -strlen($suffix)) === $suffix) {
                        $lookupValue = substr($text, 0, -strlen($suffix));
                        break;
                    }
                }
            }
            if (isset($config['value_substring']) && is_array($config['value_substring'])
                && array_key_exists('start', $config['value_substring'])) {
                $lookupValue = self::substringValue(
                    $match['value'],
                    (int) $config['value_substring']['start'],
                    array_key_exists('length', $config['value_substring'])
                        ? (int) $config['value_substring']['length'] : null
                );
            }
            $lookupValue = self::transformTableLookupValue(
                $lookupValue,
                isset($config['value_transform']) ? $config['value_transform'] : ''
            );
            if (isset($config['conditions_from_substring'])
                && is_array($config['conditions_from_substring'])) {
                if (!isset($lookupConfig['conditions']) || !is_array($lookupConfig['conditions'])) {
                    $lookupConfig['conditions'] = array();
                }
                foreach ($config['conditions_from_substring'] as $conditionColumn => $substringConfig) {
                    if (!is_array($substringConfig) || !array_key_exists('start', $substringConfig)) {
                        continue;
                    }
                    $lookupConfig['conditions'][$conditionColumn] = self::substringValue(
                        $match['value'],
                        (int) $substringConfig['start'],
                        array_key_exists('length', $substringConfig)
                            ? (int) $substringConfig['length'] : null
                    );
                }
            }
            if (isset($config['conditions_from_field']) && is_array($config['conditions_from_field'])) {
                if (!isset($lookupConfig['conditions']) || !is_array($lookupConfig['conditions'])) {
                    $lookupConfig['conditions'] = array();
                }
                foreach ($config['conditions_from_field'] as $conditionColumn => $fieldConfig) {
                    $fieldName = is_array($fieldConfig)
                        ? (isset($fieldConfig['field']) ? $fieldConfig['field'] : '') : $fieldConfig;
                    $targetFileType = is_array($fieldConfig) && !empty($fieldConfig['file_type'])
                        ? $fieldConfig['file_type'] : null;
                    $fieldMatches = self::indexedMatches(
                        $fileContexts,
                        $currentFileIndex,
                        $targetFileType,
                        $fieldName
                    );
                    $sameRowPath = preg_replace('/\.[^.]+$/', '.' . $fieldName, $match['path']);
                    $conditionValue = null;
                    foreach ($fieldMatches as $fieldMatch) {
                        if ($fieldMatch['path'] === $sameRowPath) {
                            $conditionValue = $fieldMatch['value'];
                            break;
                        }
                    }
                    if ($conditionValue === null && count($fieldMatches) === 1) {
                        $conditionValue = $fieldMatches[0]['value'];
                    }
                    $lookupConfig['conditions'][$conditionColumn] = $conditionValue;
                }
            }
            $lookupValues = array($lookupValue);
            if (isset($config['value_separator']) && is_string($config['value_separator'])
                && $config['value_separator'] !== '') {
                $lookupValues = array_values(array_filter(array_map('trim', explode(
                    $config['value_separator'],
                    is_scalar($lookupValue) ? (string) $lookupValue : ''
                )), function ($item) {
                    return $item !== '';
                }));
                if (!$lookupValues) $lookupValues = array('');
            }
            $allExist = true;
            $matchedLookups = array();
            foreach ($lookupValues as $oneLookupValue) {
                $lookupResult = $tableLookupService->lookup($oneLookupValue, $lookupConfig);
                if ($lookupResult === false) {
                    $allExist = false;
                    break;
                }
                $matchedLookups[] = $lookupResult;
            }
            if (!$allExist) {
                $errors[] = self::makeError($rule, $match['path'], $match['value']);
            } elseif (!empty($config['return_column'])) {
                $lookupResults[$match['path']] = count($matchedLookups) === 1
                    ? $matchedLookups[0] : $matchedLookups;
            }
        }
        return $errors;
    }

    private static function transformTableLookupValue($value, $transform)
    {
        $value = is_scalar($value) ? trim((string) $value) : '';
        $transform = strtolower(trim((string) $transform));
        if ($transform === 'integer' && preg_match('/^\d+$/', $value)) {
            $value = ltrim($value, '0');
            return $value === '' ? '0' : $value;
        }
        if ($transform === 'uppercase') {
            return function_exists('mb_strtoupper')
                ? mb_strtoupper($value, 'UTF-8') : strtoupper($value);
        }
        return $value;
    }

    private static function validateSubstring(
        array $rule,
        array $matches,
        array $fileContexts = array(),
        $currentFileIndex = 0,
        &$applicable = true
    )
    {
        $config = json_decode($rule['rule_value'], true);
        if (!is_array($config) || !array_key_exists('start', $config)
            || !array_key_exists('length', $config) || empty($config['operator'])
            || !array_key_exists('expected', $config)) {
            return array(self::makeError($rule, $rule['field_name'], null, 'error', 'Cấu hình SUBSTRING không hợp lệ.'));
        }
        $applicable = true;
        if (!empty($config['when']) && is_array($config['when'])) {
            $when = $config['when'];
            if (empty($when['field']) || !array_key_exists('expected', $when)) {
                return array(self::makeError(
                    $rule,
                    $rule['field_name'],
                    null,
                    'error',
                    'Cấu hình điều kiện when của SUBSTRING không hợp lệ.'
                ));
            }
            $whenMatches = self::indexedMatches(
                $fileContexts,
                $currentFileIndex,
                !empty($when['file_type']) ? strtoupper($when['file_type']) : null,
                $when['field']
            );
            $applicable = false;
            foreach ($whenMatches as $whenMatch) {
                if (self::compareValues(
                    isset($whenMatch['value']) ? $whenMatch['value'] : null,
                    $when['expected'],
                    isset($when['operator']) ? $when['operator'] : '=',
                    isset($when['data_type']) ? $when['data_type'] : 'string',
                    null
                )) {
                    $applicable = true;
                    break;
                }
            }
            if (!$applicable) return array();
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

    private static function validateCccdGenderCentury(
        array $rule,
        array $cccdMatches,
        array $fileContexts,
        $currentFileIndex
    )
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

        $birthMatches = self::indexedMatches(
            $fileContexts,
            $currentFileIndex,
            !empty($config['birth_file_type']) ? $config['birth_file_type'] : null,
            $config['birth_field']
        );
        $genderMatches = self::indexedMatches(
            $fileContexts,
            $currentFileIndex,
            !empty($config['gender_file_type']) ? $config['gender_file_type'] : null,
            $config['gender_field']
        );

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

    private static function validateApi(
        array $rule,
        array $matches,
        array $fileContexts,
        $currentFileIndex,
        $apiService,
        &$passed = null
    ) {
        $passed = false;
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
                $fieldMatches = self::indexedMatches(
                    $fileContexts,
                    $currentFileIndex,
                    $targetType,
                    $targetField
                );
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
            $passed = true;
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
            case 'STARTS_WITH':
            case 'PREFIX':
                return strpos((string) $left, (string) $right) === 0;
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
            'rule_id' => isset($rule['id']) ? (int) $rule['id'] : null,
            'field_name' => $rule['field_name'],
            'display_name' => $rule['display_name'],
            'rule_type' => $rule['rule_type'],
            'rule_value' => isset($rule['rule_value']) ? (string) $rule['rule_value'] : '',
            'huongxuly' => isset($rule['huongxuly']) ? (string) $rule['huongxuly'] : '',
            'path' => $path,
            'value' => is_scalar($value) ? (string) $value : null,
            'message' => $message !== null ? $message : $rule['error_message'],
            'severity' => $severity
        );
    }

    private static function makeValid(array $rule, $path, $value, $lookupResult = null)
    {
        $valid = array(
            'field_name' => $rule['field_name'],
            'display_name' => $rule['display_name'],
            'rule_type' => $rule['rule_type'],
            'path' => $path,
            'value' => is_scalar($value) ? (string) $value : null,
            'message' => 'Hợp lệ'
        );
        if (is_array($lookupResult)) {
            $valid['lookup_result'] = $lookupResult;
            if (array_key_exists('return_value', $lookupResult)) {
                $valid['message'] = 'Hợp lệ - ' . $lookupResult['return_column'] . ': '
                    . (string) $lookupResult['return_value'];
            }
        }
        return $valid;
    }
}
