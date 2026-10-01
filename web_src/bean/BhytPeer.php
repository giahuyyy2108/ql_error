<?php

class BhytPeer
{
    private $connection;

    public function __construct()
    {
        global $connect;
        $db = new db_mysql();
        $db->connect();
        $db->selectdb();
        $this->connection = $connect;
    }

    public function getList()
    {
        $result = $this->connection->query(
            'SELECT id, ten, mota, dien FROM nhom_BHYT ORDER BY dien ASC, id ASC'
        );
        if (!$result) throw new RuntimeException('Không thể tải danh mục nhóm BHYT.');
        $items = array();
        while ($row = $result->fetch_assoc()) {
            $row['dien'] = (int) $row['dien'];
            $items[] = $row;
        }
        $result->free();
        return $items;
    }

    public function insert(array $item)
    {
        $statement = $this->connection->prepare(
            'INSERT INTO nhom_BHYT (id, ten, mota, dien) VALUES (?, ?, ?, ?)'
        );
        if (!$statement) throw new RuntimeException('Không thể chuẩn bị dữ liệu BHYT.');
        $statement->bind_param('sssi', $item['id'], $item['ten'], $item['mota'], $item['dien']);
        try {
            $statement->execute();
        } catch (mysqli_sql_exception $exception) {
            $code = $exception->getCode();
            $statement->close();
            if ($code === 1062) throw new RuntimeException('Mã nhóm BHYT đã tồn tại.');
            throw new RuntimeException('Không thể thêm nhóm BHYT.');
        }
        $statement->close();
        return $item['id'];
    }

    public function update($originalId, array $item)
    {
        $statement = $this->connection->prepare(
            'UPDATE nhom_BHYT SET id = ?, ten = ?, mota = ?, dien = ? WHERE id = ?'
        );
        if (!$statement) throw new RuntimeException('Không thể chuẩn bị dữ liệu BHYT.');
        $statement->bind_param('sssis', $item['id'], $item['ten'], $item['mota'], $item['dien'], $originalId);
        try {
            $statement->execute();
        } catch (mysqli_sql_exception $exception) {
            $code = $exception->getCode();
            $statement->close();
            if ($code === 1062) throw new RuntimeException('Mã nhóm BHYT đã tồn tại.');
            throw new RuntimeException('Không thể cập nhật nhóm BHYT.');
        }
        $statement->close();
        return true;
    }

    public function delete($id)
    {
        $statement = $this->connection->prepare('DELETE FROM nhom_BHYT WHERE id = ?');
        if (!$statement) throw new RuntimeException('Không thể chuẩn bị thao tác xóa nhóm BHYT.');
        $statement->bind_param('s', $id);
        $statement->execute();
        $deleted = $statement->affected_rows > 0;
        $statement->close();
        return $deleted;
    }
}

