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

    public function getList()
    {
        $sql = 'SELECT id, ten, ma_lk, kichthuoc, processing_status, update_at, create_at
                FROM `file` ORDER BY update_at DESC, id DESC';
        $result = $this->connection->query($sql);
        if (!$result) {
            throw new RuntimeException('Không thể tải danh sách file.');
        }

        $files = array();
        while ($row = $result->fetch_assoc()) {
            $timestamp = strtotime($row['update_at']);
            $files[] = array(
                'id' => (int) $row['id'],
                'name' => $row['ten'],
                'ma_lk' => $row['ma_lk'],
                'size' => (int) $row['kichthuoc'],
                'status' => $row['processing_status'],
                'modified' => $timestamp ? date('d/m/Y H:i:s', $timestamp) : '',
                'timestamp' => $timestamp ?: 0
            );
        }
        $result->free();

        return $files;
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
            'SELECT id, ten, ma_lk, kichthuoc, file_path, processing_status, validation_result
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

    public function insert($name, $maLk, $size, $filePath, $validationResult = null, $status = 'processed')
    {
        $jsonValidation = $validationResult === null
            ? null
            : json_encode($validationResult, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($validationResult !== null && $jsonValidation === false) {
            throw new RuntimeException('Không thể chuyển kết quả kiểm tra sang JSON.');
        }

        $statement = $this->connection->prepare(
            'INSERT INTO `file`
             (ten, ma_lk, kichthuoc, file_path, processing_status, validation_result)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        if (!$statement) {
            throw new RuntimeException('Không thể chuẩn bị dữ liệu file.');
        }

        $size = (string) $size;
        $statement->bind_param('ssssss', $name, $maLk, $size, $filePath, $status, $jsonValidation);
        if (!$statement->execute()) {
            $statement->close();
            throw new RuntimeException('Không thể lưu file vào cơ sở dữ liệu.');
        }

        $id = $statement->insert_id;
        $statement->close();
        return $id;
    }

    public function replaceImportedFile($id, $name, $size, $filePath, array $validationResult, $status)
    {
        $jsonValidation = json_encode($validationResult, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($jsonValidation === false) {
            throw new RuntimeException('Không thể chuyển kết quả kiểm tra sang JSON.');
        }
        $statement = $this->connection->prepare(
            'UPDATE `file`
             SET ten = ?, kichthuoc = ?, file_path = ?, processing_status = ?, validation_result = ?
             WHERE id = ?'
        );
        if (!$statement) {
            throw new RuntimeException('Không thể chuẩn bị dữ liệu import lại.');
        }
        $size = (string) $size;
        $statement->bind_param('sssssi', $name, $size, $filePath, $status, $jsonValidation, $id);
        if (!$statement->execute()) {
            $statement->close();
            throw new RuntimeException('Không thể cập nhật file đã tồn tại.');
        }
        $statement->close();
        return true;
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

    public function markAllForRevalidation()
    {
        $pendingResult = $this->connection->query(
            "SELECT COUNT(*) AS total FROM `file`
             WHERE processing_status IN ('pending_revalidation', 'revalidating')"
        );
        if (!$pendingResult) {
            throw new RuntimeException('Không thể kiểm tra hàng đợi quét lại.');
        }
        $pendingRow = $pendingResult->fetch_assoc();
        $pendingResult->free();
        if ((int) $pendingRow['total'] > 0) {
            return -1;
        }
        if (!$this->connection->query(
            "UPDATE `file` SET processing_status = 'pending_revalidation'
             WHERE processing_status <> 'pending_revalidation'"
        )) {
            throw new RuntimeException('Không thể tạo hàng đợi quét lại.');
        }
        return $this->connection->affected_rows;
    }

    public function claimPendingRevalidation($limit = 2)
    {
        $limit = max(1, min(20, (int) $limit));
        $this->connection->begin_transaction();
        try {
            $result = $this->connection->query(
                "SELECT id, ten, ma_lk, file_path, processing_status
                 FROM `file`
                 WHERE processing_status = 'pending_revalidation'
                 ORDER BY update_at ASC, id ASC
                 LIMIT " . $limit . " FOR UPDATE"
            );
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

            if (!empty($ids)) {
                $idList = implode(',', $ids);
                if (!$this->connection->query(
                    "UPDATE `file` SET processing_status = 'revalidating' WHERE id IN (" . $idList . ")"
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
