<?php

class HeThongPeer
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
            'SELECT id, chucnang, trangthai FROM hethong ORDER BY id ASC'
        );
        if (!$result) {
            throw new RuntimeException('Không thể tải cấu hình hệ thống.');
        }

        $items = array();
        while ($row = $result->fetch_assoc()) {
            $row['id'] = (int) $row['id'];
            $row['trangthai'] = (int) $row['trangthai'];
            $items[] = $row;
        }
        $result->free();
        return $items;
    }

    public function updateStatus($id, $status)
    {
        $statement = $this->connection->prepare(
            'UPDATE hethong SET trangthai = ? WHERE id = ?'
        );
        if (!$statement) {
            throw new RuntimeException('Không thể chuẩn bị trạng thái hệ thống.');
        }
        $status = $status ? 1 : 0;
        $statement->bind_param('ii', $status, $id);
        if (!$statement->execute()) {
            $statement->close();
            throw new RuntimeException('Không thể cập nhật trạng thái hệ thống.');
        }
        $statement->close();

        $check = $this->connection->prepare('SELECT id FROM hethong WHERE id = ? LIMIT 1');
        if (!$check) {
            throw new RuntimeException('Không thể kiểm tra cấu hình hệ thống.');
        }
        $check->bind_param('i', $id);
        $check->execute();
        $check->store_result();
        $exists = $check->num_rows > 0;
        $check->close();
        return $exists;
    }

    public function getInputPath()
    {
        $statement = $this->connection->prepare(
            'SELECT id, tenCauHinh, path FROM path WHERE id = 1 LIMIT 1'
        );
        if (!$statement) {
            throw new RuntimeException('Không thể chuẩn bị dữ liệu đường dẫn đầu vào XML.');
        }
        if (!$statement->execute()) {
            $statement->close();
            throw new RuntimeException('Không thể đọc đường dẫn đầu vào XML.');
        }

        $result = $statement->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $statement->close();

        if (!$row) {
            return null;
        }

        $row['id'] = (int) $row['id'];
        return $row;
    }

    public function updateInputPath($path)
    {
        $statement = $this->connection->prepare(
            'UPDATE path SET path = ? WHERE id = 1'
        );
        if (!$statement) {
            throw new RuntimeException('Không thể chuẩn bị cập nhật đường dẫn đầu vào XML.');
        }
        $statement->bind_param('s', $path);
        if (!$statement->execute()) {
            $statement->close();
            throw new RuntimeException('Không thể cập nhật đường dẫn đầu vào XML.');
        }
        $statement->close();

        $check = $this->connection->query('SELECT id FROM path WHERE id = 1 LIMIT 1');
        if (!$check) {
            throw new RuntimeException('Không thể kiểm tra cấu hình đường dẫn đầu vào XML.');
        }
        $exists = $check->num_rows > 0;
        $check->free();
        return $exists;
    }

    public function getXmlDisplaySettings()
    {
        $settings = array('show_errors' => true, 'show_valid' => true);
        $result = $this->connection->query(
            'SELECT id, trangthai FROM hethong WHERE id IN (1, 2)'
        );
        if (!$result) {
            throw new RuntimeException('Không thể đọc cấu hình hiển thị XML.');
        }
        while ($row = $result->fetch_assoc()) {
            if ((int) $row['id'] === 1) {
                $settings['show_errors'] = (int) $row['trangthai'] === 1;
            } elseif ((int) $row['id'] === 2) {
                $settings['show_valid'] = (int) $row['trangthai'] === 1;
            }
        }
        $result->free();
        return $settings;
    }
}
