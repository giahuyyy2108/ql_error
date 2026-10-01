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
        $sql = 'SELECT id, ten, ma_lk, kichthuoc, update_at, create_at FROM `file` ORDER BY update_at DESC, id DESC';
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

    public function insert($name, $maLk, $size, $xmlContent, array $decodedContent)
    {
        // Cột noidung có ràng buộc JSON, XML được lưu nguyên vẹn dưới dạng JSON string.
        $jsonContent = json_encode($xmlContent, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($jsonContent === false) {
            throw new RuntimeException('Nội dung XML không sử dụng bảng mã UTF-8 hợp lệ.');
        }

        $jsonDecodedContent = json_encode($decodedContent, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($jsonDecodedContent === false) {
            throw new RuntimeException('Không thể chuyển nội dung giải mã sang JSON.');
        }

        $statement = $this->connection->prepare(
            'INSERT INTO `file` (ten, ma_lk, kichthuoc, noidung, `decode`) VALUES (?, ?, ?, ?, ?)'
        );
        if (!$statement) {
            throw new RuntimeException('Không thể chuẩn bị dữ liệu file.');
        }

        $size = (string) $size;
        $statement->bind_param('sssss', $name, $maLk, $size, $jsonContent, $jsonDecodedContent);
        if (!$statement->execute()) {
            $statement->close();
            throw new RuntimeException('Không thể lưu file vào cơ sở dữ liệu.');
        }

        $id = $statement->insert_id;
        $statement->close();
        return $id;
    }

    public function getById($id)
    {
        $statement = $this->connection->prepare(
            'SELECT id, ten, ma_lk, kichthuoc, noidung, `decode`, update_at, create_at FROM `file` WHERE id = ? LIMIT 1'
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

    public function updateDecodedContent($id, array $decodedContent)
    {
        $jsonDecodedContent = json_encode($decodedContent, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($jsonDecodedContent === false) {
            throw new RuntimeException('Không thể chuyển nội dung giải mã sang JSON.');
        }

        $statement = $this->connection->prepare('UPDATE `file` SET `decode` = ? WHERE id = ?');
        if (!$statement) {
            throw new RuntimeException('Không thể chuẩn bị dữ liệu giải mã.');
        }

        $statement->bind_param('si', $jsonDecodedContent, $id);
        if (!$statement->execute()) {
            $statement->close();
            throw new RuntimeException('Không thể cập nhật nội dung giải mã.');
        }
        $updated = $statement->affected_rows >= 0;
        $statement->close();

        return $updated;
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
}
