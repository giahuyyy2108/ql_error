<?php

class ApiValidationConfigPeer
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

    public function getActiveById($id)
    {
        $statement = $this->connection->prepare(
            'SELECT id, name, endpoint, method, headers, timeout_seconds, response_field
             FROM api_validation_configs WHERE id = ? AND is_active = 1 LIMIT 1'
        );
        if (!$statement) {
            throw new RuntimeException('Không thể đọc cấu hình API.');
        }
        $statement->bind_param('i', $id);
        $statement->execute();
        $result = $statement->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $statement->close();
        return $row ?: false;
    }

    public function getList()
    {
        $result = $this->connection->query(
            'SELECT id, name, endpoint, method, headers, timeout_seconds, response_field, is_active
             FROM api_validation_configs ORDER BY id DESC'
        );
        if (!$result) {
            throw new RuntimeException('Không thể tải danh sách cấu hình API.');
        }
        $items = array();
        while ($row = $result->fetch_assoc()) {
            $row['id'] = (int) $row['id'];
            $row['timeout_seconds'] = (int) $row['timeout_seconds'];
            $row['is_active'] = (int) $row['is_active'];
            $items[] = $row;
        }
        $result->free();
        return $items;
    }

    public function insert(array $item)
    {
        $statement = $this->connection->prepare(
            'INSERT INTO api_validation_configs
             (name, endpoint, method, headers, timeout_seconds, response_field, is_active)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        if (!$statement) throw new RuntimeException('Không thể chuẩn bị cấu hình API.');
        $statement->bind_param(
            'ssssisi',
            $item['name'], $item['endpoint'], $item['method'], $item['headers'],
            $item['timeout_seconds'], $item['response_field'], $item['is_active']
        );
        if (!$statement->execute()) {
            $statement->close();
            throw new RuntimeException('Không thể thêm cấu hình API.');
        }
        $id = $statement->insert_id;
        $statement->close();
        return $id;
    }

    public function update($id, array $item)
    {
        $statement = $this->connection->prepare(
            'UPDATE api_validation_configs
             SET name = ?, endpoint = ?, method = ?, headers = ?, timeout_seconds = ?,
                 response_field = ?, is_active = ? WHERE id = ?'
        );
        if (!$statement) throw new RuntimeException('Không thể chuẩn bị cấu hình API.');
        $statement->bind_param(
            'ssssisii',
            $item['name'], $item['endpoint'], $item['method'], $item['headers'],
            $item['timeout_seconds'], $item['response_field'], $item['is_active'], $id
        );
        if (!$statement->execute()) {
            $statement->close();
            throw new RuntimeException('Không thể cập nhật cấu hình API.');
        }
        $statement->close();
        return true;
    }

    public function delete($id)
    {
        if ($this->isUsed($id)) {
            throw new RuntimeException('Cấu hình API đang được validation rule sử dụng và không thể xóa.');
        }
        $statement = $this->connection->prepare('DELETE FROM api_validation_configs WHERE id = ?');
        if (!$statement) throw new RuntimeException('Không thể chuẩn bị thao tác xóa cấu hình API.');
        $statement->bind_param('i', $id);
        $statement->execute();
        $deleted = $statement->affected_rows > 0;
        $statement->close();
        return $deleted;
    }

    private function isUsed($id)
    {
        $statement = $this->connection->prepare(
            "SELECT id FROM xml_validation_rules
             WHERE rule_type = 'API'
               AND JSON_VALID(rule_value)
               AND CAST(JSON_UNQUOTE(JSON_EXTRACT(rule_value, '$.api_config_id')) AS UNSIGNED) = ?
             LIMIT 1"
        );
        if (!$statement) throw new RuntimeException('Không thể kiểm tra validation rule liên quan.');
        $statement->bind_param('i', $id);
        $statement->execute();
        $statement->store_result();
        $used = $statement->num_rows > 0;
        $statement->close();
        return $used;
    }
}
