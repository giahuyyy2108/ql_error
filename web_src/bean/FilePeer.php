<?php

class RevalidationQueueBusyException extends RuntimeException
{
}

class FilePeer
{
    const MAX_DEADLOCK_RETRIES = 3;
    const REVALIDATION_LEASE_SECONDS = 900;
    private $dbsql;
    private $connection;

    // ---------------------------------------------------------------------
    // Cache danh sách và trạng thái hàng đợi upload
    // ---------------------------------------------------------------------

    private function fileListCachePath($date, $userKhoa)
    {
        $dir = dirname(__DIR__, 2) . '/storage/runtime';
        if (!is_dir($dir)) @mkdir($dir, 0775, true);
        return $dir . '/file-list-' . sha1($date . '|' . (string) $userKhoa) . '.json';
    }

    private function clearFileListCache()
    {
        $dir = dirname(__DIR__, 2) . '/storage/runtime';
        foreach (glob($dir . '/file-list-*.json') ?: array() as $path) @unlink($path);
    }

    public function findUserKhoa($username)
    {
        $statement = $this->connection->prepare(
            'SELECT id_khoa FROM `user` WHERE username = ? LIMIT 1'
        );
        if (!$statement) throw new RuntimeException('Không thể xác định khoa của người dùng.');
        $statement->bind_param('s', $username);
        $statement->execute();
        $row = $statement->get_result()->fetch_assoc();
        $statement->close();
        return array(
            'found' => (bool) $row,
            'id_khoa' => ($row && $row['id_khoa'] !== null) ? (int) $row['id_khoa'] : null
        );
    }

    public function getCurrentUserKhoa($username)
    {
        $user = $this->findUserKhoa($username);
        return $user['found'] ? $user['id_khoa'] : null;
    }

    public function getKhoaOptions()
    {
        $result = $this->connection->query('SELECT id, ten FROM `khoa` ORDER BY ten ASC');
        if (!$result) {
            throw new RuntimeException('Không thể tải danh sách khoa.');
        }
        $rows = array();
        while ($row = $result->fetch_assoc()) {
            $rows[] = array(
                'id' => (int) $row['id'],
                'ten' => (string) $row['ten']
            );
        }
        $result->free();
        return $rows;
    }

    public function upsertUploadQueue($fileName, $idKhoa, $username)
    {
        $statement = $this->connection->prepare(
            'INSERT INTO `xml_upload_queue` (file_name, id_khoa, username) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE id_khoa = VALUES(id_khoa), username = VALUES(username)'
        );
        if (!$statement) {
            throw new RuntimeException('Không thể chuẩn bị thông tin hàng đợi upload.');
        }
        $fileName = basename((string) $fileName);
        $queueKhoa = $idKhoa === null ? null : (string) (int) $idKhoa;
        $username = (string) $username;
        $statement->bind_param('sss', $fileName, $queueKhoa, $username);
        if (!$statement->execute()) {
            $statement->close();
            throw new RuntimeException('Không thể lưu thông tin hàng đợi upload.');
        }
        $statement->close();
        return true;
    }

    public function removeUploadQueue($fileName)
    {
        $statement = $this->connection->prepare(
            'DELETE FROM `xml_upload_queue` WHERE file_name = ?'
        );
        if (!$statement) {
            throw new RuntimeException('Không thể chuẩn bị xóa hàng đợi upload.');
        }
        $fileName = basename((string) $fileName);
        $statement->bind_param('s', $fileName);
        if (!$statement->execute()) {
            $statement->close();
            throw new RuntimeException('Không thể xóa thông tin hàng đợi upload.');
        }
        $statement->close();
        return true;
    }

    public function countQueuedUploadFiles(array $fileNames)
    {
        $names = array_values(array_unique(array_filter(array_map('basename', $fileNames))));
        $activeNames = $this->activeQueuedXmlNames();
        $names = array_values(array_filter($names, function ($name) use ($activeNames) {
            return isset($activeNames[$name]);
        }));
        if (!$names) return 0;
        $escaped = array_map(function ($name) {
            return "'" . $this->connection->real_escape_string($name) . "'";
        }, $names);
        $result = $this->connection->query(
            'SELECT COUNT(*) AS total FROM xml_upload_queue WHERE file_name IN (' . implode(',', $escaped) . ')'
        );
        if (!$result) throw new RuntimeException('Không thể đọc tiến độ import.');
        $row = $result->fetch_assoc();
        $result->free();
        return (int) $row['total'];
    }

    public function countActiveQueuedUploadFiles($date = null)
    {
        $activeNames = $this->activeQueuedXmlNames();
        if (!$activeNames) return 0;

        $isAdmin = isset($_SESSION['AdminType']) && (int) $_SESSION['AdminType'] === 1;
        $userKhoa = null;
        $filterByKhoa = false;
        if (!$isAdmin) {
            $username = isset($_SESSION['sUserName']) ? (string) $_SESSION['sUserName'] : '';
            $userStatement = $this->connection->prepare(
                'SELECT id_khoa FROM `user` WHERE username = ? LIMIT 1'
            );
            if (!$userStatement) throw new RuntimeException('Không thể xác định khoa của người dùng.');
            $userStatement->bind_param('s', $username);
            $userStatement->execute();
            $userRow = $userStatement->get_result()->fetch_assoc();
            if ($userRow && $userRow['id_khoa'] !== null) {
                $userKhoa = (int) $userRow['id_khoa'];
                $filterByKhoa = true;
            }
            $userStatement->close();
        }

        $sql = 'SELECT file_name FROM `xml_upload_queue`';
        $conditions = array();
        $dateFrom = null;
        $dateTo = null;
        if ($date !== null) {
            $conditions[] = 'created_at >= ? AND created_at < ?';
            $dateFrom = $date . ' 00:00:00';
            $dateTo = date('Y-m-d 00:00:00', strtotime($date . ' +1 day'));
        }
        if ($filterByKhoa) $conditions[] = 'id_khoa = ?';
        if ($conditions) $sql .= ' WHERE ' . implode(' AND ', $conditions);

        $statement = $this->connection->prepare($sql);
        if (!$statement) throw new RuntimeException('Không thể đọc hàng đợi import.');
        if ($date !== null && $filterByKhoa) {
            $statement->bind_param('ssi', $dateFrom, $dateTo, $userKhoa);
        } elseif ($date !== null) {
            $statement->bind_param('ss', $dateFrom, $dateTo);
        } elseif ($filterByKhoa) {
            $statement->bind_param('i', $userKhoa);
        }
        $statement->execute();
        $result = $statement->get_result();
        if (!$result) {
            $statement->close();
            throw new RuntimeException('Không thể đọc hàng đợi import.');
        }

        $count = 0;
        while ($row = $result->fetch_assoc()) {
            if (isset($activeNames[basename((string) $row['file_name'])])) $count++;
        }
        $result->free();
        $statement->close();
        return $count;
    }

    private function activeQueuedXmlNames()
    {
        $queueDirectory = dirname(__DIR__, 2) . '/storage/xml';
        $names = array();
        $collect = function ($directory) use (&$names) {
            if (!is_dir($directory)) return;
            foreach (new DirectoryIterator($directory) as $entry) {
                if ($entry->isDot() || !$entry->isFile()) continue;
                if (strtolower($entry->getExtension()) !== 'xml') continue;
                $names[$entry->getFilename()] = true;
            }
        };

        $collect($queueDirectory);
        $processingRoot = $queueDirectory . DIRECTORY_SEPARATOR . 'processing';
        foreach (glob($processingRoot . DIRECTORY_SEPARATOR . 'worker-*', GLOB_ONLYDIR) ?: array() as $workerDirectory) {
            $collect($workerDirectory);
        }
        return $names;
    }

    public function __construct()
    {
        global $connect;

        $this->dbsql = new db_mysql();
        $this->dbsql->connect();
        $this->dbsql->selectdb();
        $this->connection = $connect;
    }

    // ---------------------------------------------------------------------
    // Danh sách file và dữ liệu báo cáo
    // ---------------------------------------------------------------------

    public function getList($date)
    {
        $isAdmin = isset($_SESSION['AdminType']) && (int) $_SESSION['AdminType'] === 1;
        $userKhoa = null;
        $filterByKhoa = false;
        if (!$isAdmin) {
            $userStatement = $this->connection->prepare(
                'SELECT id_khoa FROM `user` WHERE username = ? LIMIT 1'
            );
            $username = isset($_SESSION['sUserName']) ? $_SESSION['sUserName'] : '';
            $userStatement->bind_param('s', $username);
            $userStatement->execute();
            $userRow = $userStatement->get_result()->fetch_assoc();
            if ($userRow && $userRow['id_khoa'] !== null) {
                $userKhoa = (int) $userRow['id_khoa'];
                $filterByKhoa = true;
            }
            $userStatement->close();
        }

        $cachePath = $this->fileListCachePath($date, $filterByKhoa ? $userKhoa : 'admin');
        if (is_file($cachePath) && (time() - (int) @filemtime($cachePath)) < 60) {
            $cached = json_decode((string) @file_get_contents($cachePath), true);
            if (is_array($cached)) return $cached;
        }

        $sql = 'SELECT id, ten, ma_lk, ma_bn, ho_ten, thongtin_bn, kichthuoc, processing_status, da_day_cong,
                       validation_result, update_at, create_at
                FROM `file`
                WHERE update_at >= ? AND update_at < ?'
                . ' AND da_day_cong = 0'
                . ($filterByKhoa ? ' AND id_khoa = ?' : '') . '
                ORDER BY update_at DESC, id DESC';
        $statement = $this->connection->prepare($sql);
        if (!$statement) {
            throw new RuntimeException('Không thể chuẩn bị danh sách file.');
        }
        $dateFrom = $date . ' 00:00:00';
        $dateTo = date('Y-m-d 00:00:00', strtotime($date . ' +1 day'));
        if ($filterByKhoa) {
            $statement->bind_param('ssi', $dateFrom, $dateTo, $userKhoa);
        } else {
            $statement->bind_param('ss', $dateFrom, $dateTo);
        }
        $statement->execute();
        $result = $statement->get_result();
        if (!$result) {
            $statement->close();
            throw new RuntimeException('Không thể tải danh sách file.');
        }

        $files = array();
        while ($row = $result->fetch_assoc()) {
            $timestamp = strtotime($row['update_at']);
            $summary = $this->fileSummaryFromValidation($row['validation_result'], $row['thongtin_bn']);
            $files[] = array(
                'id' => (int) $row['id'],
                'name' => $row['ten'],
                'ma_lk' => $row['ma_lk'],
                'ma_bn' => $row['ma_bn'] !== null ? $row['ma_bn'] : '',
                'ho_ten' => $row['ho_ten'] !== null ? $row['ho_ten'] : '',
                'ma_the_bhyt' => $summary['ma_the_bhyt'],
                'ngay_vao' => $summary['ngay_vao'],
                'ngay_ra' => $summary['ngay_ra'],
                'error_count' => $summary['error_count'],
                'warning_count' => $summary['warning_count'],
                'error_fields' => $summary['error_fields'],
                'total_amount' => $summary['total_amount'],
                'size' => (int) $row['kichthuoc'],
                'status' => $row['processing_status'],
                'da_day_cong' => (bool) $row['da_day_cong'],
                'modified' => $timestamp ? date('d/m/Y H:i:s', $timestamp) : '',
                'timestamp' => $timestamp ?: 0
            );
        }
        $result->free();
        $statement->close();

        @file_put_contents($cachePath, json_encode($files, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
        return $files;
    }

    private function fileSummaryFromValidation($json, $patientInfoJson = null)
    {
        $summary = array(
            'ma_the_bhyt' => '',
            'ngay_vao' => '',
            'ngay_ra' => '',
            'error_count' => 0,
            'warning_count' => 0,
            'error_fields' => '',
            'total_amount' => 0
        );
        $patientInfo = json_decode((string) $patientInfoJson, true);
        if (is_array($patientInfo) && isset($patientInfo['maThe']) && is_scalar($patientInfo['maThe'])) {
            $summary['ma_the_bhyt'] = (string) $patientInfo['maThe'];
        }
        $validation = json_decode((string) $json, true);
        if (!is_array($validation)) return $summary;

        $fieldMap = array(
            'MA_THE_BHYT' => 'ma_the_bhyt',
            'NGAY_VAO' => 'ngay_vao',
            'NGAY_RA' => 'ngay_ra',
            'T_TONGCHI_BV' => 'total_amount'
        );
        foreach ($validation as $item) {
            if (!is_array($item)) continue;
            $fileType = isset($item['file_type']) ? trim((string) $item['file_type']) : '';
            $summary['error_count'] += isset($item['error_count'])
                ? (int) $item['error_count']
                : (isset($item['errors']) && is_array($item['errors']) ? count($item['errors']) : 0);
            $summary['warning_count'] += isset($item['warning_count'])
                ? (int) $item['warning_count'] : 0;
            foreach (array('valid_fields', 'errors') as $collectionName) {
                $fields = isset($item[$collectionName]) && is_array($item[$collectionName])
                    ? $item[$collectionName] : array();
                foreach ($fields as $field) {
                    $fieldName = isset($field['field_name']) ? $field['field_name'] : '';
                    $isWarning = isset($field['severity']) && strtolower((string) $field['severity']) === 'warning';
                    if ($collectionName === 'errors' && !$isWarning && $fieldName !== '') {
                        $errorFieldName = $fieldName;
                        if ($fileType !== '' && stripos($fieldName, $fileType . '.') !== 0) {
                            $errorFieldName = $fileType . '.' . $fieldName;
                        }
                        $existing = $summary['error_fields'] === '' ? array() : explode(', ', $summary['error_fields']);
                        if (!in_array($errorFieldName, $existing, true)) $existing[] = $errorFieldName;
                        $summary['error_fields'] = implode(', ', $existing);
                    }
                    if (!isset($fieldMap[$fieldName])) continue;
                    $key = $fieldMap[$fieldName];
                    if ($summary[$key] === '' || $summary[$key] === 0) {
                        $summary[$key] = isset($field['value']) && is_scalar($field['value'])
                            ? (string) $field['value'] : '';
                    }
                }
            }
        }
        return $summary;
    }

    public function getErrorReportRows($date, $to = null, $idKhoa = null)
    {
        $to = $to ?: date('Y-m-d', strtotime($date . ' +1 day'));
        $sql = 'SELECT ma_lk, ma_bn, ngay_lap, processing_status, validation_result
                FROM `file`
                WHERE update_at >= ? AND update_at < ?
                  AND validation_result IS NOT NULL AND validation_result <> \'\'';
        if ($idKhoa !== null) $sql .= ' AND id_khoa = ?';
        $sql .= '
                ORDER BY update_at DESC, id DESC';
        $statement = $this->connection->prepare($sql);
        if (!$statement) {
            throw new RuntimeException('Không thể chuẩn bị dữ liệu báo cáo lỗi.');
        }
        if ($idKhoa !== null) $statement->bind_param('ssi', $date, $to, $idKhoa);
        else $statement->bind_param('ss', $date, $to);
        $statement->execute();
        $result = $statement->get_result();
        if (!$result) {
            throw new RuntimeException('Không thể tải dữ liệu báo cáo lỗi.');
        }

        $reportRows = array();
        while ($file = $result->fetch_assoc()) {
            $validation = json_decode($file['validation_result'], true);
            if (!is_array($validation)) continue;
            $documentDate = $this->formatDocumentDate($file['ngay_lap']);
            $summary = $this->fileSummaryFromValidation($file['validation_result']);

            foreach ($validation as $validationItem) {
                $errors = isset($validationItem['errors']) && is_array($validationItem['errors'])
                    ? $validationItem['errors'] : array();
                foreach ($errors as $error) {
                    $reportRows[] = array(
                        'ma_lk' => (string) $file['ma_lk'],
                        'ma_bn' => (string) ($file['ma_bn'] ?: ''),
                        'ma_the_bhyt' => $summary['ma_the_bhyt'],
                        'ngay_vao' => $summary['ngay_vao'],
                        'ngay_ra' => $summary['ngay_ra'],
                        'total_amount' => $summary['total_amount'],
                        'xml' => isset($validationItem['file_type']) ? (string) $validationItem['file_type'] : '',
                        'field' => isset($error['field_name']) ? (string) $error['field_name']
                            : (isset($error['display_name']) ? (string) $error['display_name'] : ''),
                        'value' => isset($error['value']) && is_scalar($error['value'])
                            ? (string) $error['value'] : '',
                        'error' => isset($error['message']) ? (string) $error['message'] : 'Lỗi validation',
                        'rule_id' => isset($error['rule_id']) ? (int) $error['rule_id'] : null,
                        'rule_name' => isset($error['display_name']) ? (string) $error['display_name'] : '',
                        'severity' => isset($error['severity']) ? (string) $error['severity'] : 'error',
                        'notes' => '',
                        'date' => $documentDate,
                        'status' => $file['processing_status'] === 'failed' ? 'CHƯA FIX' : 'ĐÃ FIX'
                    );
                }
            }
        }
        $result->free();
        $statement->close();
        return $reportRows;
    }

    public function getAllFileReportRows($date, $to = null, $idKhoa = null)
    {
        $to = $to ?: date('Y-m-d', strtotime($date . ' +1 day'));
        $sql = 'SELECT ma_lk, ma_bn, ngay_lap, validation_result
                FROM `file` WHERE update_at >= ? AND update_at < ?';
        if ($idKhoa !== null) $sql .= ' AND id_khoa = ?';
        $sql .= ' ORDER BY update_at DESC, id DESC';
        $statement = $this->connection->prepare($sql);
        if (!$statement) throw new RuntimeException('Không thể chuẩn bị dữ liệu tổng hợp file.');
        if ($idKhoa !== null) $statement->bind_param('ssi', $date, $to, $idKhoa);
        else $statement->bind_param('ss', $date, $to);
        $statement->execute();
        $result = $statement->get_result();
        if (!$result) throw new RuntimeException('Không thể tải dữ liệu tổng hợp file.');
        $rows = array();
        while ($file = $result->fetch_assoc()) {
            $summary = $this->fileSummaryFromValidation($file['validation_result']);
            $rows[] = array(
                'ma_lk' => (string) $file['ma_lk'], 'ma_bn' => (string) ($file['ma_bn'] ?: ''),
                'ma_the_bhyt' => $summary['ma_the_bhyt'], 'ngay_vao' => $summary['ngay_vao'],
                'ngay_ra' => $summary['ngay_ra'], 'error_count' => $summary['error_count'],
                'warning_count' => $summary['warning_count'],
                'total_amount' => $summary['total_amount'], 'date' => $this->formatDocumentDate($file['ngay_lap'])
            );
        }
        $result->free(); $statement->close();
        return $rows;
    }

    private function formatDocumentDate($value)
    {
        $value = preg_replace('/\D/', '', (string) $value);
        if (strlen($value) < 8) return '';

        $date = DateTime::createFromFormat('!Ymd', substr($value, 0, 8));
        return $date ? $date->format('d.m.Y') : '';
    }

    public function maLkExists($maLk)
    {
        $statement = $this->connection->prepare('SELECT id FROM `file` WHERE ma_lk = ? LIMIT 1');
        if (!$statement) {
            throw new RuntimeException('Không thể kiểm tra MA_LK của hồ sơ.');
        }
        $statement->bind_param('s', $maLk);
        $statement->execute();
        $statement->store_result();
        $exists = $statement->num_rows > 0;
        $statement->close();

        return $exists;
    }

    public function getByMaLk($maLk)
    {
        $statement = $this->connection->prepare(
            'SELECT id, ten, ma_lk, kichthuoc, file_path, processing_status, validation_result, id_khoa
             FROM `file` WHERE ma_lk = ? LIMIT 1'
        );
        if (!$statement) {
            throw new RuntimeException('Không thể đọc hồ sơ theo MA_LK.');
        }
        $statement->bind_param('s', $maLk);
        $statement->execute();
        $result = $statement->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $statement->close();
        return $row ?: false;
    }

    public function insert(
        $name,
        $maLk,
        $size,
        $filePath,
        $validationResult = null,
        $status = 'processed',
        $ngayLap = null,
        $maBn = null,
        $hoTen = null,
        array $thongTinBn = null
    )
    {
        $jsonValidation = $validationResult === null
            ? null
            : json_encode($validationResult, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($validationResult !== null && $jsonValidation === false) {
            throw new RuntimeException('Không thể chuyển kết quả kiểm tra sang JSON.');
        }
        $jsonThongTinBn = $thongTinBn === null ? null
            : json_encode($thongTinBn, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($thongTinBn !== null && $jsonThongTinBn === false) {
            throw new RuntimeException('Không thể chuyển thông tin bệnh nhân sang JSON.');
        }

        $statement = $this->connection->prepare(
            'INSERT INTO `file`
             (ten, ma_lk, ma_bn, ho_ten, thongtin_bn, ngay_lap, kichthuoc, file_path, processing_status, validation_result, id_khoa)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        if (!$statement) {
            throw new RuntimeException('Không thể chuẩn bị dữ liệu file.');
        }

        $size = (string) $size;
        $idKhoa = null;
        $queueStatement = $this->connection->prepare(
            'SELECT id_khoa FROM `xml_upload_queue` WHERE file_name = ? LIMIT 1'
        );
        $queuedFileName = basename($filePath);
        $queueStatement->bind_param('s', $queuedFileName);
        $queueStatement->execute();
        $queueRow = $queueStatement->get_result()->fetch_assoc();
        if ($queueRow && $queueRow['id_khoa'] !== null) {
            $idKhoa = (int) $queueRow['id_khoa'];
        }
        $queueStatement->close();

        $metaPath = $filePath . '.meta.json';
        if (!is_file($metaPath)) {
            $queueMetaPath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage'
                . DIRECTORY_SEPARATOR . 'xml' . DIRECTORY_SEPARATOR
                . basename($filePath) . '.meta.json';
            if (is_file($queueMetaPath)) {
                $metaPath = $queueMetaPath;
            }
        }
        if (is_file($metaPath)) {
            $meta = json_decode((string) file_get_contents($metaPath), true);
            $idKhoa = isset($meta['id_khoa']) && $meta['id_khoa'] !== null
                ? (int) $meta['id_khoa'] : null;
        }
        if ($idKhoa === null && !empty($_SESSION['sUserName'])) {
            $userStatement = $this->connection->prepare(
                'SELECT id_khoa FROM `user` WHERE username = ? LIMIT 1'
            );
            $username = $_SESSION['sUserName'];
            $userStatement->bind_param('s', $username);
            $userStatement->execute();
            $userRow = $userStatement->get_result()->fetch_assoc();
            if ($userRow && $userRow['id_khoa'] !== null) {
                $idKhoa = (int) $userRow['id_khoa'];
            }
            $userStatement->close();
        }
        $idKhoaValue = $idKhoa === null ? null : (string) $idKhoa;
        $statement->bind_param('sssssssssss', $name, $maLk, $maBn, $hoTen, $jsonThongTinBn, $ngayLap, $size, $filePath, $status, $jsonValidation, $idKhoaValue);
        if (!$statement->execute()) {
            $statement->close();
            throw new RuntimeException('Không thể lưu file vào cơ sở dữ liệu.');
        }

        if (is_file($metaPath)) {
            @unlink($metaPath);
        }

        $deleteQueue = $this->connection->prepare(
            'DELETE FROM `xml_upload_queue` WHERE file_name = ?'
        );
        $deleteQueue->bind_param('s', $queuedFileName);
        $deleteQueue->execute();
        $deleteQueue->close();

        $id = $statement->insert_id;
        $statement->close();
        $this->clearFileListCache();
        return $id;
    }

    public function replaceImportedFile(
        $id,
        $name,
        $size,
        $filePath,
        array $validationResult,
        $status,
        $ngayLap = null,
        $maBn = null,
        $hoTen = null,
        array $thongTinBn = null
    )
    {
        $jsonValidation = json_encode($validationResult, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($jsonValidation === false) {
            throw new RuntimeException('Không thể chuyển kết quả kiểm tra sang JSON.');
        }
        $jsonThongTinBn = $thongTinBn === null ? null
            : json_encode($thongTinBn, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($thongTinBn !== null && $jsonThongTinBn === false) {
            throw new RuntimeException('Không thể chuyển thông tin bệnh nhân sang JSON.');
        }

        $queuedFileName = basename($filePath);
        $idKhoa = null;
        $queueStatement = $this->connection->prepare(
            'SELECT id_khoa FROM `xml_upload_queue` WHERE file_name = ? LIMIT 1'
        );
        if ($queueStatement) {
            $queueStatement->bind_param('s', $queuedFileName);
            $queueStatement->execute();
            $queueRow = $queueStatement->get_result()->fetch_assoc();
            if ($queueRow && $queueRow['id_khoa'] !== null) {
                $idKhoa = (int) $queueRow['id_khoa'];
            }
            $queueStatement->close();
        }

        if ($idKhoa !== null) {
            $statement = $this->connection->prepare(
                'UPDATE `file`
                 SET ten = ?, ma_bn = ?, ho_ten = ?, thongtin_bn = ?, ngay_lap = ?, kichthuoc = ?, file_path = ?, processing_status = ?, validation_result = ?, id_khoa = ?
                 WHERE id = ?'
            );
        } else {
            $statement = $this->connection->prepare(
                'UPDATE `file`
                 SET ten = ?, ma_bn = ?, ho_ten = ?, thongtin_bn = ?, ngay_lap = ?, kichthuoc = ?, file_path = ?, processing_status = ?, validation_result = ?
                 WHERE id = ?'
            );
        }
        if (!$statement) {
            throw new RuntimeException('Không thể chuẩn bị dữ liệu import lại.');
        }
        $size = (string) $size;
        if ($idKhoa !== null) {
            $statement->bind_param('sssssssssii', $name, $maBn, $hoTen, $jsonThongTinBn, $ngayLap, $size, $filePath, $status, $jsonValidation, $idKhoa, $id);
        } else {
            $statement->bind_param('sssssssssi', $name, $maBn, $hoTen, $jsonThongTinBn, $ngayLap, $size, $filePath, $status, $jsonValidation, $id);
        }
        if (!$statement->execute()) {
            $statement->close();
            throw new RuntimeException('Không thể cập nhật file đã tồn tại.');
        }
        $statement->close();

        $deleteQueue = $this->connection->prepare(
            'DELETE FROM `xml_upload_queue` WHERE file_name = ?'
        );
        if ($deleteQueue) {
            $deleteQueue->bind_param('s', $queuedFileName);
            $deleteQueue->execute();
            $deleteQueue->close();
        }
        $this->clearFileListCache();
        return true;
    }

    // ---------------------------------------------------------------------
    // Lưu/cập nhật hồ sơ XML và lịch sử thay thế
    // ---------------------------------------------------------------------

    public function saveFileHistory($existing, $archivePath)
    {
        $username = isset($_SESSION['sUserName']) ? (string) $_SESSION['sUserName'] : null;
        if ($username === null || $username === '') {
            $queue = $this->connection->prepare('SELECT username FROM `xml_upload_queue` WHERE file_name = ? LIMIT 1');
            if ($queue) {
                $queuedName = (string) $existing['ten'];
                $queue->bind_param('s', $queuedName);
                $queue->execute();
                $queueRow = $queue->get_result()->fetch_assoc();
                $username = $queueRow && $queueRow['username'] !== null ? (string) $queueRow['username'] : null;
                $queue->close();
            }
        }
        $khoaName = null;
        if (isset($existing['id_khoa']) && $existing['id_khoa'] !== null) {
            $query = $this->connection->prepare('SELECT ten FROM `khoa` WHERE id = ? LIMIT 1');
            if ($query) {
                $idKhoa = (int) $existing['id_khoa'];
                $query->bind_param('i', $idKhoa);
                $query->execute();
                $row = $query->get_result()->fetch_assoc();
                $khoaName = $row ? (string) $row['ten'] : null;
                $query->close();
            }
        }
        $hash = is_file($archivePath) ? hash_file('sha256', $archivePath) : null;
        $query = $this->connection->prepare(
            'INSERT INTO `file_history` (file_id, ma_lk, file_name, replaced_at, username, khoa_name, file_path, file_hash, old_validation_result)
             VALUES (?, ?, ?, NOW(), ?, ?, ?, ?, ?)'
        );
        if (!$query) throw new RuntimeException('Không thể chuẩn bị lịch sử file.');
        $id = (int) $existing['id'];
        $maLk = (string) $existing['ma_lk'];
        $name = (string) $existing['ten'];
        $validation = isset($existing['validation_result']) ? $existing['validation_result'] : null;
        $query->bind_param('isssssss', $id, $maLk, $name, $username, $khoaName, $archivePath, $hash, $validation);
        if (!$query->execute()) throw new RuntimeException('Không thể lưu lịch sử file.');
        $query->close();
    }

    // ---------------------------------------------------------------------
    // Nhật ký truy vết và dữ liệu đẩy cổng
    // ---------------------------------------------------------------------

    public function saveAuditLog($fileId, $maLk, $fileName, $action, $oldPath, $newPath, $errorDetail = null)
    {
        $username = isset($_SESSION['sUserName']) ? (string) $_SESSION['sUserName'] : null;
        if (($username === null || $username === '') && $fileName !== '') {
            $queue = $this->connection->prepare('SELECT username FROM `xml_upload_queue` WHERE file_name = ? LIMIT 1');
            if ($queue) {
                $queue->bind_param('s', $fileName);
                $queue->execute();
                $queueRow = $queue->get_result()->fetch_assoc();
                $username = $queueRow && $queueRow['username'] !== null ? (string) $queueRow['username'] : null;
                $queue->close();
            }
        }
        $query = $this->connection->prepare(
            'INSERT INTO `file_audit_logs` (file_id, ma_lk, file_name, action, username, file_path_old, file_path_new, error_detail)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        if (!$query) throw new RuntimeException('Không thể chuẩn bị nhật ký truy vết.');
        $id = $fileId === null ? null : (int) $fileId;
        $query->bind_param('isssssss', $id, $maLk, $fileName, $action, $username, $oldPath, $newPath, $errorDetail);
        if (!$query->execute()) throw new RuntimeException('Không thể lưu nhật ký truy vết.');
        $query->close();
    }

    public function getAuditLogs($maLk)
    {
        $query = $this->connection->prepare('SELECT created_at, action, username, khoa_name, file_name, file_path_old, file_path_new, error_detail FROM file_audit_logs WHERE ma_lk = ? ORDER BY created_at DESC, id DESC');
        if (!$query) {
            throw new RuntimeException('Không thể tải nhật ký truy vết.');
        }
        $query->bind_param('s', $maLk);
        $query->execute();
        $result = $query->get_result();
        $rows = array();
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        $query->close();
        return $rows;
    }

    public function getPortalFiles($date)
    {
        $q = $this->connection->prepare("SELECT f.id, f.ten, f.ma_lk, f.file_path
            FROM file f
            WHERE DATE(f.update_at)=? AND f.processing_status='processed' AND f.da_day_cong=0
              AND NOT EXISTS (SELECT 1 FROM cong_hoso c WHERE c.malk = f.ma_lk)
            ORDER BY f.id");
        if (!$q) {
            throw new RuntimeException('Không thể tải file đẩy cổng.');
        }
        $q->bind_param('s', $date);
        $q->execute();
        $result = $q->get_result();
        $rows = array();
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        $q->close();
        return $rows;
    }

    public function getPortalFilesByIds(array $ids, $allowWarnings = false)
    {
        $ids = array_values(array_filter(array_map('intval', $ids), function ($id) {
            return $id > 0;
        }));
        if (!$ids) {
            return array();
        }
        $idList = implode(',', $ids);
        $q = $this->connection->prepare("SELECT f.id, f.ten, f.ma_lk, f.file_path, f.validation_result
            FROM file f
            WHERE f.id IN ($idList) AND f.da_day_cong=0
              AND NOT EXISTS (SELECT 1 FROM cong_hoso c WHERE c.malk = f.ma_lk)");
        $q->execute();
        $result = $q->get_result();
        $rows = array();
        while ($row = $result->fetch_assoc()) {
            $validation = json_decode((string) $row['validation_result'], true);
            $hasErrors = false;
            $hasWarnings = false;
            foreach (is_array($validation) ? $validation : array() as $item) {
                if ((int) ($item['error_count'] ?? 0) > 0) {
                    $hasErrors = true;
                    break;
                }
                $hasWarnings = $hasWarnings || (int) ($item['warning_count'] ?? 0) > 0;
            }
            if (!$hasErrors && ($allowWarnings || !$hasWarnings)) {
                unset($row['validation_result']);
                $rows[] = $row;
            }
        }
        $q->close();
        return $rows;
    }

    public function getCongHosoData($date = null, $maLk = null, $limit = 1000)
    {
        $limit = max(1, min(5000, (int) $limit));
        $where = array();
        $params = array();
        $types = '';
        if ($date !== null) {
            $where[] = 'DATE(ngay_day) = ?';
            $params[] = $date;
            $types .= 's';
        }
        if ($maLk !== null && $maLk !== '') {
            $where[] = 'malk = ?';
            $params[] = $maLk;
            $types .= 's';
        }
        $sql = 'SELECT id, cccd, malk, ten_file, nguoi_day, id_khoa, ngay_day
                FROM cong_hoso';
        if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
        $sql .= ' ORDER BY id DESC LIMIT ' . $limit;
        $statement = $this->connection->prepare($sql);
        if (!$statement) throw new RuntimeException('Không thể tải dữ liệu cổng hồ sơ.');
        if ($params) $statement->bind_param($types, ...$params);
        if (!$statement->execute()) {
            $statement->close();
            throw new RuntimeException('Không thể tải dữ liệu cổng hồ sơ.');
        }
        $result = $statement->get_result();
        $rows = array();
        while ($row = $result->fetch_assoc()) $rows[] = $row;
        $result->free();
        $statement->close();
        return $rows;
    }

    public function markPortalPushed($id)
    {
        $q = $this->connection->prepare('UPDATE file SET da_day_cong=1 WHERE id=?');
        if (!$q) {
            throw new RuntimeException('Không thể cập nhật trạng thái đẩy cổng.');
        }
        $q->bind_param('i', $id);
        $ok = $q->execute();
        $q->close();
        return $ok;
    }

    public function getById($id)
    {
        $statement = $this->connection->prepare(
            'SELECT id, ten, ma_lk, kichthuoc, file_path, processing_status,
                    validation_result, update_at, create_at
             FROM `file` WHERE id = ? LIMIT 1'
        );
        if (!$statement) {
            throw new RuntimeException('Không thể đọc dữ liệu file.');
        }

        $statement->bind_param('i', $id);
        $statement->execute();
        $result = $statement->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $statement->close();

        return $row ?: false;
    }

    public function updateValidationResult($id, array $validationResult)
    {
        $jsonValidation = json_encode($validationResult, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($jsonValidation === false) {
            throw new RuntimeException('Không thể chuyển kết quả kiểm tra sang JSON.');
        }

        $statement = $this->connection->prepare(
            'UPDATE `file` SET validation_result = ? WHERE id = ?'
        );
        if (!$statement) {
            throw new RuntimeException('Không thể chuẩn bị kết quả kiểm tra.');
        }

        $statement->bind_param('si', $jsonValidation, $id);
        if (!$statement->execute()) {
            $statement->close();
            throw new RuntimeException('Không thể cập nhật kết quả kiểm tra.');
        }
        $updated = $statement->affected_rows >= 0;
        $statement->close();
        $this->clearFileListCache();

        return $updated;
    }

    public function updateStorageLocation($id, $filePath, $status)
    {
        $statement = $this->connection->prepare(
            'UPDATE `file`
             SET file_path = ?, processing_status = ?, revalidation_claimed_at = NULL
             WHERE id = ?'
        );
        if (!$statement) {
            throw new RuntimeException('Không thể chuẩn bị đường dẫn lưu file.');
        }
        $statement->bind_param('ssi', $filePath, $status, $id);
        if (!$statement->execute()) {
            $statement->close();
            throw new RuntimeException('Không thể cập nhật đường dẫn lưu file.');
        }
        $statement->close();
        $this->clearFileListCache();
        return true;
    }

    // ---------------------------------------------------------------------
    // Hàng đợi quét lại: tạo queue, claim batch và đọc tiến độ
    // ---------------------------------------------------------------------

    public function markAllForRevalidation($date, $idKhoa = null)
    {
        // Serialize queue creation and batch claiming on the same MySQL mutex.
        // This prevents the range UPDATE below from racing with FOR UPDATE.
        $mutex = $this->connection->query("SELECT GET_LOCK('ql_error_revalidation_queue', 10)");
        if (!$mutex || (int) $mutex->fetch_row()[0] !== 1) {
            throw new RevalidationQueueBusyException('Hàng đợi quét lại đang được xử lý, vui lòng thử lại.');
        }
        $mutex->free();
        try {
        $pendingStatement = $this->connection->prepare(
            "SELECT COUNT(*) AS total FROM `file`
             WHERE DATE(update_at) = ?
               AND processing_status IN ('pending_revalidation', 'revalidating')"
             . ($idKhoa !== null ? ' AND id_khoa = ?' : '')
        );
        if (!$pendingStatement) {
            throw new RuntimeException('Không thể chuẩn bị kiểm tra hàng đợi quét lại.');
        }
        if ($idKhoa !== null) $pendingStatement->bind_param('si', $date, $idKhoa);
        else $pendingStatement->bind_param('s', $date);
        $pendingStatement->execute();
        $pendingResult = $pendingStatement->get_result();
        if (!$pendingResult) {
            throw new RuntimeException('Không thể kiểm tra hàng đợi quét lại.');
        }
        $pendingRow = $pendingResult->fetch_assoc();
        $pendingResult->free();
        $pendingStatement->close();
        if ((int) $pendingRow['total'] > 0) {
            $this->clearFileListCache();
            return -1;
        }
        $statement = $this->connection->prepare(
            "UPDATE `file`
             SET processing_status = 'pending_revalidation',
                 revalidation_claimed_at = NULL,
                 update_at = update_at
             WHERE DATE(update_at) = ?
               AND processing_status NOT IN ('pending_revalidation', 'revalidating')"
             . ($idKhoa !== null ? ' AND id_khoa = ?' : '') . "
             LIMIT 100"
        );
        if (!$statement) {
            throw new RuntimeException('Không thể tạo hàng đợi quét lại.');
        }
        $affectedRows = 0;
        // Update small batches so the watcher never competes with one large
        // range lock covering all files for the selected day.
        do {
            if ($idKhoa !== null) $statement->bind_param('si', $date, $idKhoa);
            else $statement->bind_param('s', $date);
            if (!$statement->execute()) {
                $errorCode = (int) $statement->errno;
                throw new RuntimeException('Queue update failed.', $errorCode);
            }
            $batchRows = $statement->affected_rows;
            $affectedRows += $batchRows;
        } while ($batchRows > 0);
        $statement->close();
        $this->clearFileListCache();
        return $affectedRows;
        } catch (Throwable $exception) {
            $errorCode = (int) $exception->getCode();
            if ($errorCode === 0) {
                $errorCode = (int) $this->connection->errno;
            }
            static $queueRetryCount = 0;
            if (in_array($errorCode, array(1205, 1213), true) && $queueRetryCount < self::MAX_DEADLOCK_RETRIES) {
                $queueRetryCount++;
                usleep($queueRetryCount * 200000 + mt_rand(0, 100000));
                try {
                    return $this->markAllForRevalidation($date, $idKhoa);
                } finally {
                    $queueRetryCount--;
                }
            }
            throw $exception;
        } finally {
            $this->connection->query("SELECT RELEASE_LOCK('ql_error_revalidation_queue')");
        }
    }

    public function claimPendingRevalidation($limit = 2, $date = null, $idKhoa = null, $lockTimeoutSeconds = 10)
    {
        $limit = max(1, min(20, (int) $limit));
        $lockTimeoutSeconds = max(0, (int) $lockTimeoutSeconds);
        $mutex = $this->connection->query(
            "SELECT GET_LOCK('ql_error_revalidation_queue', " . $lockTimeoutSeconds . ")"
        );
        if (!$mutex || (int) $mutex->fetch_row()[0] !== 1) {
            throw new RevalidationQueueBusyException('Hàng đợi quét lại đang được xử lý, vui lòng thử lại.');
        }
        $mutex->free();
        $this->connection->begin_transaction();
        try {
            $leaseSeconds = self::REVALIDATION_LEASE_SECONDS;
            if (!$this->connection->query(
                "UPDATE `file`
                 SET processing_status = 'pending_revalidation',
                     revalidation_claimed_at = NULL,
                     update_at = update_at
                 WHERE processing_status = 'revalidating'
                   AND (
                       revalidation_claimed_at IS NULL
                       OR revalidation_claimed_at < DATE_SUB(NOW(), INTERVAL " . $leaseSeconds . " SECOND)
                   )"
            )) {
                throw new RuntimeException('Không thể phục hồi hàng đợi quét lại bị gián đoạn.');
            }

            $sql = "SELECT id, ten, ma_lk, file_path, processing_status, validation_result
                    FROM `file`
                    WHERE processing_status = 'pending_revalidation'";
            if ($date !== null) $sql .= " AND DATE(update_at) = ?";
            if ($idKhoa !== null) $sql .= " AND id_khoa = ?";
            // MariaDB does not support SKIP LOCKED. The GET_LOCK mutex above
            // serializes the watcher and web requests before this row lock.
            $sql .= " ORDER BY update_at ASC, id ASC LIMIT " . $limit . " FOR UPDATE";
            $statement = $this->connection->prepare($sql);
            if (!$statement) {
                throw new RuntimeException('Không thể chuẩn bị nhận hàng đợi quét lại.');
            }
            if ($date !== null && $idKhoa !== null) $statement->bind_param('si', $date, $idKhoa);
            elseif ($date !== null) $statement->bind_param('s', $date);
            elseif ($idKhoa !== null) $statement->bind_param('i', $idKhoa);
            if (!$statement->execute()) {
                $errorCode = (int) $statement->errno;
                $statement->close();
                throw new RuntimeException('Queue claim failed.', $errorCode);
            }
            $result = $statement->get_result();
            if (!$result) {
                throw new RuntimeException('Không thể nhận hàng đợi quét lại.');
            }

            $files = array();
            $ids = array();
            while ($row = $result->fetch_assoc()) {
                $row['id'] = (int) $row['id'];
                $files[] = $row;
                $ids[] = (int) $row['id'];
            }
            $result->free();
            $statement->close();

            if (!empty($ids)) {
                $idList = implode(',', $ids);
                if (!$this->connection->query(
                    "UPDATE `file`
                     SET processing_status = 'revalidating',
                         revalidation_claimed_at = NOW(),
                         update_at = update_at
                     WHERE id IN (" . $idList . ")"
                )) {
                    throw new RuntimeException('Không thể cập nhật hàng đợi quét lại.');
                }
            }
            $this->connection->commit();
            return $files;
        } catch (Throwable $exception) {
            $this->connection->rollback();
            static $retryCount = 0;
            $errorCode = (int) $exception->getCode();
            if ($errorCode === 0) {
                $errorCode = (int) $this->connection->errno;
            }
            if (in_array($errorCode, array(1205, 1213), true) && $retryCount < self::MAX_DEADLOCK_RETRIES) {
                $retryCount++;
                usleep($retryCount * 150000 + mt_rand(0, 100000));
                try {
                    return $this->claimPendingRevalidation($limit, $date, $idKhoa, $lockTimeoutSeconds);
                } finally {
                    $retryCount--;
                }
            }
            throw $exception;
        } finally {
            $this->connection->query("SELECT RELEASE_LOCK('ql_error_revalidation_queue')");
        }
    }

    public function countPendingRevalidation()
    {
        $result = $this->connection->query(
            "SELECT COUNT(*) AS total FROM `file` WHERE processing_status = 'pending_revalidation'"
        );
        if (!$result) {
            throw new RuntimeException('Không thể đếm hàng đợi quét lại.');
        }
        $row = $result->fetch_assoc();
        $result->free();
        return (int) $row['total'];
    }

    public function getRevalidationProgress($date = null)
    {
        $sql = "SELECT
                    SUM(processing_status = 'pending_revalidation') AS pending,
                    SUM(processing_status = 'revalidating') AS revalidating,
                    SUM(processing_status = 'processed') AS success,
                    SUM(processing_status = 'failed') AS failed,
                    COUNT(*) AS total
                FROM `file`";
        if ($date !== null) $sql .= " WHERE DATE(update_at) = ?";
        $statement = $this->connection->prepare($sql);
        if (!$statement) {
            throw new RuntimeException('Không thể chuẩn bị đọc tiến độ quét lại.');
        }
        if ($date !== null) $statement->bind_param('s', $date);
        $statement->execute();
        $result = $statement->get_result();
        if (!$result) {
            throw new RuntimeException('Không thể đọc tiến độ quét lại.');
        }
        $row = $result->fetch_assoc();
        $result->free();
        $statement->close();
        $pending = isset($row['pending']) ? (int) $row['pending'] : 0;
        $revalidating = isset($row['revalidating']) ? (int) $row['revalidating'] : 0;
        // validation_result is an array of XML results; warning_count is stored per item.
        $warningSql = 'SELECT validation_result FROM `file`';
        if ($date !== null) $warningSql .= ' WHERE DATE(update_at) = ?';
        $warningStatement = $this->connection->prepare($warningSql);
        $warningCount = 0;
        if ($warningStatement) {
            if ($date !== null) $warningStatement->bind_param('s', $date);
            if ($warningStatement->execute()) {
                $warningResult = $warningStatement->get_result();
                while ($warningRow = $warningResult->fetch_assoc()) {
                    $validation = json_decode((string) $warningRow['validation_result'], true);
                    if (!is_array($validation)) continue;
                    foreach ($validation as $item) {
                        if (is_array($item)) $warningCount += (int) ($item['warning_count'] ?? 0);
                    }
                }
                $warningResult->free();
            }
            $warningStatement->close();
        }
        return array(
            'pending' => $pending,
            'revalidating' => $revalidating,
            'remaining' => $pending + $revalidating,
            'processing' => $pending + $revalidating,
            'success' => isset($row['success']) ? (int) $row['success'] : 0,
            'failed' => isset($row['failed']) ? (int) $row['failed'] : 0,
            'warning_count' => $warningCount,
            'total' => isset($row['total']) ? (int) $row['total'] : 0
        );
    }

    public function getPendingRevalidation($limit = 2)
    {
        $limit = max(1, min(20, (int) $limit));
        $result = $this->connection->query(
            "SELECT id, ten, ma_lk, file_path, processing_status
             FROM `file`
             WHERE processing_status = 'pending_revalidation'
             ORDER BY update_at ASC, id ASC
             LIMIT " . $limit
        );
        if (!$result) {
            throw new RuntimeException('Không thể đọc hàng đợi quét lại.');
        }
        $files = array();
        while ($row = $result->fetch_assoc()) {
            $row['id'] = (int) $row['id'];
            $files[] = $row;
        }
        $result->free();
        return $files;
    }

    // ---------------------------------------------------------------------
    // Xóa dữ liệu file
    // ---------------------------------------------------------------------

    public function delete($id)
    {
        $statement = $this->connection->prepare('DELETE FROM `file` WHERE id = ?');
        if (!$statement) {
            throw new RuntimeException('Không thể chuẩn bị thao tác xóa file.');
        }

        $statement->bind_param('i', $id);
        $statement->execute();
        $deleted = $statement->affected_rows > 0;
        $statement->close();

        return $deleted;
    }

    public function deleteMany(array $ids)
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), function ($id) {
            return $id > 0;
        })));
        if (empty($ids)) {
            return 0;
        }

        $idList = implode(',', $ids);
        $result = $this->connection->query('DELETE FROM `file` WHERE id IN (' . $idList . ')');
        if ($result === false) {
            throw new RuntimeException('Không thể xóa các file đã chọn.');
        }
        $deleted = $this->connection->affected_rows;
        $this->clearFileListCache();
        return $deleted;
    }

}
