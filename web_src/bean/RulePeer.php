<?php

class RulePeer
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
            'SELECT id, code, display_name, description, value_hint, requires_value, is_active
             FROM `rule` ORDER BY id ASC'
        );
        if (!$result) {
            throw new RuntimeException('Không thể tải danh mục rule.');
        }

        $items = array();
        while ($row = $result->fetch_assoc()) {
            $row['id'] = (int) $row['id'];
            $row['requires_value'] = (int) $row['requires_value'];
            $row['is_active'] = (int) $row['is_active'];
            $items[] = $row;
        }
        $result->free();
        return $items;
    }

    public function insert(array $item)
    {
        $statement = $this->connection->prepare(
            'INSERT INTO `rule` (code, display_name, description, value_hint, requires_value, is_active)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        if (!$statement) {
            throw new RuntimeException('Không thể chuẩn bị dữ liệu loại rule.');
        }
        $statement->bind_param(
            'ssssii',
            $item['code'],
            $item['display_name'],
            $item['description'],
            $item['value_hint'],
            $item['requires_value'],
            $item['is_active']
        );
        try {
            $executed = $statement->execute();
        } catch (mysqli_sql_exception $exception) {
            $error = $exception->getCode();
            $statement->close();
            if ($error === 1062) {
                throw new RuntimeException('Mã rule đã tồn tại.');
            }
            throw new RuntimeException('Không thể thêm loại rule.');
        }
        if (!$executed) {
            $statement->close();
            throw new RuntimeException('Không thể thêm loại rule.');
        }
        $id = $statement->insert_id;
        $statement->close();
        return $id;
    }

    public function update($id, array $item)
    {
        $statement = $this->connection->prepare(
            'UPDATE `rule`
             SET code = ?, display_name = ?, description = ?, value_hint = ?, requires_value = ?, is_active = ?
             WHERE id = ?'
        );
        if (!$statement) {
            throw new RuntimeException('Không thể chuẩn bị dữ liệu loại rule.');
        }
        $statement->bind_param(
            'ssssiii',
            $item['code'],
            $item['display_name'],
            $item['description'],
            $item['value_hint'],
            $item['requires_value'],
            $item['is_active'],
            $id
        );
        try {
            $executed = $statement->execute();
        } catch (mysqli_sql_exception $exception) {
            $error = $exception->getCode();
            $statement->close();
            if ($error === 1062) {
                throw new RuntimeException('Mã rule đã tồn tại.');
            }
            throw new RuntimeException('Không thể cập nhật loại rule.');
        }
        if (!$executed) {
            $statement->close();
            throw new RuntimeException('Không thể cập nhật loại rule.');
        }
        $statement->close();
        return true;
    }

    public function delete($id)
    {
        $statement = $this->connection->prepare('DELETE FROM `rule` WHERE id = ?');
        if (!$statement) {
            throw new RuntimeException('Không thể chuẩn bị thao tác xóa loại rule.');
        }
        $statement->bind_param('i', $id);
        try {
            $executed = $statement->execute();
        } catch (mysqli_sql_exception $exception) {
            $error = $exception->getCode();
            $statement->close();
            if ($error === 1451) {
                throw new RuntimeException('Loại rule đang được sử dụng và không thể xóa.');
            }
            throw new RuntimeException('Không thể xóa loại rule.');
        }
        if (!$executed) {
            $statement->close();
            throw new RuntimeException('Không thể xóa loại rule.');
        }
        $deleted = $statement->affected_rows > 0;
        $statement->close();
        return $deleted;
    }
}
