<?php

class BhytPeer
{
    /** @var mysqli */
    private $connection;

    public function __construct()
    {
        global $connect;
        $db = new db_mysql();
        $db->connect();
        $db->selectdb();
        if (!($connect instanceof mysqli)) {
            throw new RuntimeException('Không thể kết nối cơ sở dữ liệu.');
        }
        $this->connection = $connect;
    }

    public function getList()
    {
        $result = $this->connection->query(
            'SELECT b.id, b.ten, b.mota,
                    GROUP_CONCAT(d.dien ORDER BY d.dien SEPARATOR 0x2C) AS dien
             FROM nhom_BHYT b
             LEFT JOIN nhom_BHYT_dien d ON d.ma_bhyt = b.id
             GROUP BY b.id, b.ten, b.mota
             ORDER BY MIN(d.dien) ASC, b.id ASC'
        );
        if (!$result) throw new RuntimeException('Không thể tải danh mục nhóm BHYT.');
        $items = array();
        while ($row = $result->fetch_assoc()) {
            $row['dien'] = $row['dien'] === null || $row['dien'] === ''
                ? array()
                : array_map('intval', explode(',', $row['dien']));
            $items[] = $row;
        }
        $result->free();
        return $items;
    }

    public function insert(array $item)
    {
        $this->connection->begin_transaction();
        $statement = null;
        try {
            $statement = $this->connection->prepare(
                'INSERT INTO nhom_BHYT (id, ten, mota) VALUES (?, ?, ?)'
            );
            if (!$statement) throw new RuntimeException('Không thể chuẩn bị dữ liệu BHYT.');
            $statement->bind_param('sss', $item['id'], $item['ten'], $item['mota']);
            $statement->execute();
            $statement->close();
            $statement = null;
            $this->replaceDien($item['id'], $item['dien']);
            $this->connection->commit();
        } catch (Throwable $exception) {
            if ($statement) $statement->close();
            $this->connection->rollback();
            if ((int) $exception->getCode() === 1062) throw new RuntimeException('Mã nhóm BHYT đã tồn tại.');
            throw new RuntimeException('Không thể thêm nhóm BHYT.');
        }
        return $item['id'];
    }

    public function update($originalId, array $item)
    {
        $this->connection->begin_transaction();
        $statement = null;
        try {
            $statement = $this->connection->prepare(
                'UPDATE nhom_BHYT SET id = ?, ten = ?, mota = ? WHERE id = ?'
            );
            if (!$statement) throw new RuntimeException('Không thể chuẩn bị dữ liệu BHYT.');
            $statement->bind_param('ssss', $item['id'], $item['ten'], $item['mota'], $originalId);
            $statement->execute();
            $statement->close();
            $statement = null;
            $this->replaceDien($item['id'], $item['dien']);
            $this->connection->commit();
        } catch (Throwable $exception) {
            if ($statement) $statement->close();
            $this->connection->rollback();
            if ((int) $exception->getCode() === 1062) throw new RuntimeException('Mã nhóm BHYT đã tồn tại.');
            throw new RuntimeException('Không thể cập nhật nhóm BHYT.');
        }
        return true;
    }

    private function replaceDien($id, array $dien)
    {
        $delete = $this->connection->prepare('DELETE FROM nhom_BHYT_dien WHERE ma_bhyt = ?');
        if (!$delete) throw new RuntimeException('Không thể chuẩn bị cập nhật diện BHYT.');
        $delete->bind_param('s', $id);
        $delete->execute();
        $delete->close();

        $insert = $this->connection->prepare('INSERT INTO nhom_BHYT_dien (ma_bhyt, dien) VALUES (?, ?)');
        if (!$insert) throw new RuntimeException('Không thể chuẩn bị cập nhật diện BHYT.');
        foreach ($dien as $value) {
            $insert->bind_param('si', $id, $value);
            $insert->execute();
        }
        $insert->close();
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
