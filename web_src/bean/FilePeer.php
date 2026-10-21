<?php

class FilePeer
{
    private $dbsql;
    private $connection;

    public function __construct()
    {
        global $connect;

        $this->dbsql = new db_mysql();
        $this->dbsql->connect();
        $this->dbsql->selectdb();
        $this->connection = $connect;
    }

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

        $sql = 'SELECT id, ten, ma_lk, ma_bn, ho_ten, kichthuoc, processing_status,
                       validation_result, update_at, create_at
                FROM `file`
                WHERE update_at >= ? AND update_at < ?'
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
            $summary = $this->fileSummaryFromValidation($row['validation_result']);
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
                'total_amount' => $summary['total_amount'],
                'size' => (int) $row['kichthuoc'],
                'status' => $row['processing_status'],
                'modified' => $timestamp ? date('d/m/Y H:i:s', $timestamp) : '',
                'timestamp' => $timestamp ?: 0
            );
        }
        $result->free();
        $statement->close();

        return $files;
    }

    private function fileSummaryFromValidation($json)
    {
        $summary = array(
            'ma_the_bhyt' => '',
            'ngay_vao' => '',
            'ngay_ra' => '',
            'error_count' => 0,
            'total_amount' => 0
        );
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
            $summary['error_count'] += isset($item['error_count'])
                ? (int) $item['error_count']
                : (isset($item['errors']) && is_array($item['errors']) ? count($item['errors']) : 0);
            foreach (array('valid_fields', 'errors') as $collectionName) {
                $fields = isset($item[$collectionName]) && is_array($item[$collectionName])
                    ? $item[$collectionName] : array();
                foreach ($fields as $field) {
                    $fieldName = isset($field['field_name']) ? $field['field_name'] : '';
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

    public function getErrorReportRows($date, $to = null)
    {
        $to = $to ?: date('Y-m-d', strtotime($date . ' +1 day'));
        $sql = 'SELECT ma_lk, ma_bn, ngay_lap, processing_status, validation_result
                FROM `file`
                WHERE update_at >= ? AND update_at < ?
                  AND validation_result IS NOT NULL AND validation_result <> \'\'
                ORDER BY update_at DESC, id DESC';
        $statement = $this->connection->prepare($sql);
        if (!$statement) {
            throw new RuntimeException('Không thể chuẩn bị dữ liệu báo cáo lỗi.');
        }
        $statement->bind_param('ss', $date, $to);
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

    public function getAllFileReportRows($date, $to = null)
    {
        $to = $to ?: date('Y-m-d', strtotime($date . ' +1 day'));
        $sql = 'SELECT ma_lk, ma_bn, ngay_lap, validation_result
                FROM `file` WHERE update_at >= ? AND update_at < ? ORDER BY update_at DESC, id DESC';
        $statement = $this->connection->prepare($sql);
        if (!$statement) throw new RuntimeException('Không thể chuẩn bị dữ liệu tổng hợp file.');
        $statement->bind_param('ss', $date, $to);
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
        $statement = $this->connection->prepare(
            'UPDATE `file`
             SET ten = ?, ma_bn = ?, ho_ten = ?, thongtin_bn = ?, ngay_lap = ?, kichthuoc = ?, file_path = ?, processing_status = ?, validation_result = ?
             WHERE id = ?'
        );
        if (!$statement) {
            throw new RuntimeException('Không thể chuẩn bị dữ liệu import lại.');
        }
        $size = (string) $size;
        $statement->bind_param('sssssssssi', $name, $maBn, $hoTen, $jsonThongTinBn, $ngayLap, $size, $filePath, $status, $jsonValidation, $id);
        if (!$statement->execute()) {
            $statement->close();
            throw new RuntimeException('Không thể cập nhật file đã tồn tại.');
        }
        $statement->close();
        return true;
    }

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

    public function saveAuditLog($fileId, $maLk, $fileName, $action, $oldPath, $newPath, $errorDetail = null)
    {
        $username = isset($_SESSION['sUserName']) ? (string) $_SESSION['sUserName'] : null;
        if (($username === null || $username === '') && $fileName !== '') {
            $queue = $this->connection->prepare('SELECT username FROM `xml_upload_queue` WHERE file_name = ? LIMIT 1');
            if ($queue) {
                $queue->bind_param('s', $fileName); $queue->execute();
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
        if (!$query) throw new RuntimeException('Không thể tải nhật ký truy vết.');
        $query->bind_param('s', $maLk); $query->execute(); $result = $query->get_result();
        $rows = array(); while ($row = $result->fetch_assoc()) $rows[] = $row;
        $query->close(); return $rows;
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

        return $updated;
    }

    public function updateStorageLocation($id, $filePath, $status)
    {
        $statement = $this->connection->prepare(
            'UPDATE `file` SET file_path = ?, processing_status = ? WHERE id = ?'
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
        return true;
    }

    public function markAllForRevalidation($date)
    {
        $pendingStatement = $this->connection->prepare(
            "SELECT COUNT(*) AS total FROM `file`
             WHERE DATE(update_at) = ?
               AND processing_status IN ('pending_revalidation', 'revalidating')"
        );
        if (!$pendingStatement) {
            throw new RuntimeException('Không thể chuẩn bị kiểm tra hàng đợi quét lại.');
        }
        $pendingStatement->bind_param('s', $date);
        $pendingStatement->execute();
        $pendingResult = $pendingStatement->get_result();
        if (!$pendingResult) {
            throw new RuntimeException('Không thể kiểm tra hàng đợi quét lại.');
        }
        $pendingRow = $pendingResult->fetch_assoc();
        $pendingResult->free();
        $pendingStatement->close();
        if ((int) $pendingRow['total'] > 0) {
            return -1;
        }
        $statement = $this->connection->prepare(
            "UPDATE `file`
             SET processing_status = 'pending_revalidation', update_at = update_at
             WHERE DATE(update_at) = ?
               AND processing_status NOT IN ('pending_revalidation', 'revalidating')"
        );
        if (!$statement) {
            throw new RuntimeException('Không thể tạo hàng đợi quét lại.');
        }
        $statement->bind_param('s', $date);
        $statement->execute();
        $affectedRows = $statement->affected_rows;
        $statement->close();
        return $affectedRows;
    }

    public function claimPendingRevalidation($limit = 2, $date = null)
    {
        $limit = max(1, min(20, (int) $limit));
        $this->connection->begin_transaction();
        try {
            $sql = "SELECT id, ten, ma_lk, file_path, processing_status, validation_result
                    FROM `file`
                    WHERE processing_status = 'pending_revalidation'";
            if ($date !== null) $sql .= " AND DATE(update_at) = ?";
            $sql .= " ORDER BY update_at ASC, id ASC LIMIT " . $limit . " FOR UPDATE";
            $statement = $this->connection->prepare($sql);
            if (!$statement) {
                throw new RuntimeException('Không thể chuẩn bị nhận hàng đợi quét lại.');
            }
            if ($date !== null) $statement->bind_param('s', $date);
            $statement->execute();
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
                    "UPDATE `file` SET processing_status = 'revalidating', update_at = update_at WHERE id IN (" . $idList . ")"
                )) {
                    throw new RuntimeException('Không thể cập nhật hàng đợi quét lại.');
                }
            }
            $this->connection->commit();
            return $files;
        } catch (Throwable $exception) {
            $this->connection->rollback();
            throw $exception;
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
                    SUM(processing_status = 'revalidating') AS revalidating
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
        return array(
            'pending' => $pending,
            'revalidating' => $revalidating,
            'remaining' => $pending + $revalidating
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

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $statement = $this->connection->prepare('DELETE FROM `file` WHERE id IN (' . $placeholders . ')');
        if (!$statement) {
            throw new RuntimeException('Không thể chuẩn bị thao tác xóa nhiều file.');
        }
        $types = str_repeat('i', count($ids));
        $statement->bind_param($types, ...$ids);
        if (!$statement->execute()) {
            $statement->close();
            throw new RuntimeException('Không thể xóa các file đã chọn.');
        }
        $deleted = $statement->affected_rows;
        $statement->close();
        return $deleted;
    }

}
