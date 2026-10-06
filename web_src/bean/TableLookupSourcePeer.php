<?php

class TableLookupSourcePeer
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
            'SELECT id, source_key, display_name, table_name, allowed_columns, condition_columns, is_active
             FROM table_lookup_sources ORDER BY display_name, source_key'
        );
        if (!$result) throw new RuntimeException('Không thể tải danh sách nguồn đối chiếu.');
        $items = array();
        while ($row = $result->fetch_assoc()) {
            $row['id'] = (int) $row['id'];
            $row['is_active'] = (int) $row['is_active'];
            $row['allowed_columns'] = implode(', ', $this->decodeColumns($row['allowed_columns']));
            $row['condition_columns'] = implode(', ', $this->decodeColumns($row['condition_columns']));
            $items[] = $row;
        }
        $result->free();
        return $items;
    }

    public function insert(array $item)
    {
        $this->validatePhysicalTable($item);
        $statement = $this->connection->prepare(
            'INSERT INTO table_lookup_sources
             (source_key, display_name, table_name, allowed_columns, condition_columns, is_active)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        if (!$statement) throw new RuntimeException('Không thể chuẩn bị thao tác thêm nguồn đối chiếu.');
        $allowed = json_encode($item['allowed_columns'], JSON_UNESCAPED_UNICODE);
        $conditions = json_encode($item['condition_columns'], JSON_UNESCAPED_UNICODE);
        $statement->bind_param('sssssi', $item['source_key'], $item['display_name'], $item['table_name'], $allowed, $conditions, $item['is_active']);
        return $this->executeWrite($statement, 'thêm');
    }

    public function update($id, array $item)
    {
        $this->validatePhysicalTable($item);
        $statement = $this->connection->prepare(
            'UPDATE table_lookup_sources
             SET source_key=?, display_name=?, table_name=?, allowed_columns=?, condition_columns=?, is_active=?
             WHERE id=?'
        );
        if (!$statement) throw new RuntimeException('Không thể chuẩn bị thao tác cập nhật nguồn đối chiếu.');
        $allowed = json_encode($item['allowed_columns'], JSON_UNESCAPED_UNICODE);
        $conditions = json_encode($item['condition_columns'], JSON_UNESCAPED_UNICODE);
        $statement->bind_param('sssssii', $item['source_key'], $item['display_name'], $item['table_name'], $allowed, $conditions, $item['is_active'], $id);
        $this->executeWrite($statement, 'cập nhật');
        return true;
    }

    public function delete($id)
    {
        $find = $this->connection->prepare('SELECT source_key FROM table_lookup_sources WHERE id=?');
        $find->bind_param('i', $id);
        $find->execute();
        $row = $find->get_result()->fetch_assoc();
        $find->close();
        if (!$row) return false;

        $pattern = '%"table":"' . $this->connection->real_escape_string($row['source_key']) . '"%';
        $used = $this->connection->prepare('SELECT 1 FROM xml_validation_rules WHERE rule_type="TABLE_EXISTS" AND REPLACE(rule_value, " ", "") LIKE ? LIMIT 1');
        $used->bind_param('s', $pattern);
        $used->execute();
        $used->store_result();
        $isUsed = $used->num_rows > 0;
        $used->close();
        if ($isUsed) throw new RuntimeException('Nguồn đang được sử dụng trong rule TABLE_EXISTS và không thể xóa.');

        $statement = $this->connection->prepare('DELETE FROM table_lookup_sources WHERE id=?');
        $statement->bind_param('i', $id);
        $statement->execute();
        $deleted = $statement->affected_rows > 0;
        $statement->close();
        return $deleted;
    }

    public function setActive($id, $isActive)
    {
        $statement = $this->connection->prepare(
            'UPDATE table_lookup_sources SET is_active=? WHERE id=?'
        );
        if (!$statement) throw new RuntimeException('Không thể chuẩn bị cập nhật trạng thái.');
        $isActive = $isActive ? 1 : 0;
        $statement->bind_param('ii', $isActive, $id);
        if (!$statement->execute()) {
            $statement->close();
            throw new RuntimeException('Không thể cập nhật trạng thái nguồn đối chiếu.');
        }
        $updated = $statement->affected_rows > 0;
        $statement->close();
        if (!$updated) {
            $check = $this->connection->prepare('SELECT 1 FROM table_lookup_sources WHERE id=? LIMIT 1');
            $check->bind_param('i', $id);
            $check->execute();
            $check->store_result();
            $exists = $check->num_rows > 0;
            $check->close();
            if (!$exists) throw new RuntimeException('Nguồn đối chiếu không tồn tại.');
        }
        return true;
    }

    public function getDatabaseSchema()
    {
        $database = _DATABASE_NAME_;
        $statement = $this->connection->prepare(
            'SELECT TABLE_NAME, COLUMN_NAME FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA=? ORDER BY TABLE_NAME, ORDINAL_POSITION'
        );
        $statement->bind_param('s', $database);
        $statement->execute();
        $result = $statement->get_result();
        $schema = array();
        while ($row = $result->fetch_assoc()) {
            if (!isset($schema[$row['TABLE_NAME']])) $schema[$row['TABLE_NAME']] = array();
            $schema[$row['TABLE_NAME']][] = $row['COLUMN_NAME'];
        }
        $statement->close();
        return $schema;
    }

    private function validatePhysicalTable(array $item)
    {
        $schema = $this->getDatabaseSchema();
        if (!isset($schema[$item['table_name']])) throw new RuntimeException('Bảng dữ liệu không tồn tại trong database.');
        $columns = $schema[$item['table_name']];
        foreach (array_merge($item['allowed_columns'], $item['condition_columns']) as $column) {
            if (!in_array($column, $columns, true)) throw new RuntimeException('Cột ' . $column . ' không tồn tại trong bảng ' . $item['table_name'] . '.');
        }
    }

    private function executeWrite($statement, $action)
    {
        if (!$statement) throw new RuntimeException('Không thể chuẩn bị thao tác ' . $action . ' nguồn đối chiếu.');
        try {
            $statement->execute();
        } catch (mysqli_sql_exception $exception) {
            $statement->close();
            if ($exception->getCode() === 1062) throw new RuntimeException('Mã nguồn đối chiếu đã tồn tại.');
            throw new RuntimeException('Không thể ' . $action . ' nguồn đối chiếu.');
        }
        $id = $statement->insert_id;
        $statement->close();
        return $id;
    }

    private function decodeColumns($json)
    {
        $columns = json_decode($json, true);
        return is_array($columns) ? $columns : array();
    }
}
