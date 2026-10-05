<?php

class XmlValidationRulePeer
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
        $sql = 'SELECT id, file_type, field_name, display_name, rule_type, rule_value, error_message, is_active
                FROM xml_validation_rules
                ORDER BY file_type ASC, field_name ASC, id DESC';
        $result = $this->connection->query($sql);
        if (!$result) {
            throw new RuntimeException('Không thể tải danh sách rules.');
        }

        $rules = array();
        while ($row = $result->fetch_assoc()) {
            $row['id'] = (int) $row['id'];
            $row['is_active'] = (int) $row['is_active'];
            $rules[] = $row;
        }
        $result->free();
        return $rules;
    }

    public function getSupportedRuleTypes()
    {
        $sql = 'SELECT id, code, display_name, description, value_hint, requires_value
                FROM `rule`
                WHERE is_active = 1
                ORDER BY id ASC';
        $result = $this->connection->query($sql);
        if (!$result) {
            throw new RuntimeException('Không thể tải danh mục loại rule.');
        }

        $types = array();
        while ($row = $result->fetch_assoc()) {
            $row['id'] = (int) $row['id'];
            $row['requires_value'] = (int) $row['requires_value'];
            $types[] = $row;
        }
        $result->free();
        return $types;
    }

    public function getRulesForValidation()
    {
        $sql = 'SELECT validation.id, validation.file_type, validation.field_name,
                       validation.display_name, validation.rule_type, validation.rule_value,
                       validation.error_message, validation.is_active
                FROM xml_validation_rules AS validation
                INNER JOIN `rule` AS supported ON supported.code = validation.rule_type
                WHERE validation.is_active = 1 AND supported.is_active = 1
                ORDER BY validation.file_type ASC, validation.field_name ASC';
        $result = $this->connection->query($sql);
        if (!$result) {
            throw new RuntimeException('Không thể tải rules kiểm tra XML.');
        }

        $rules = array();
        while ($row = $result->fetch_assoc()) {
            $row['id'] = (int) $row['id'];
            $row['is_active'] = (int) $row['is_active'];
            $rules[] = $row;
        }
        $result->free();
        return $rules;
    }

    public function getSupportedRuleType($code)
    {
        $statement = $this->connection->prepare(
            'SELECT id, code, display_name, description, value_hint, requires_value
             FROM `rule` WHERE code = ? AND is_active = 1 LIMIT 1'
        );
        if (!$statement) {
            throw new RuntimeException('Không thể kiểm tra loại rule.');
        }
        $statement->bind_param('s', $code);
        $statement->execute();
        $result = $statement->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $statement->close();
        if ($row) {
            $row['id'] = (int) $row['id'];
            $row['requires_value'] = (int) $row['requires_value'];
        }
        return $row ?: false;
    }

    public function insert(array $rule)
    {
        $sql = 'INSERT INTO xml_validation_rules
                (file_type, field_name, display_name, rule_type, rule_value, error_message, is_active)
                VALUES (?, ?, ?, ?, ?, ?, ?)';
        $statement = $this->connection->prepare($sql);
        if (!$statement) {
            throw new RuntimeException('Không thể chuẩn bị dữ liệu rule.');
        }

        $statement->bind_param(
            'ssssssi',
            $rule['file_type'],
            $rule['field_name'],
            $rule['display_name'],
            $rule['rule_type'],
            $rule['rule_value'],
            $rule['error_message'],
            $rule['is_active']
        );
        if (!$statement->execute()) {
            $statement->close();
            throw new RuntimeException('Không thể thêm rule.');
        }
        $id = $statement->insert_id;
        $statement->close();
        return $id;
    }

    public function update($id, array $rule)
    {
        $sql = 'UPDATE xml_validation_rules
                SET file_type = ?, field_name = ?, display_name = ?, rule_type = ?,
                    rule_value = ?, error_message = ?, is_active = ?
                WHERE id = ?';
        $statement = $this->connection->prepare($sql);
        if (!$statement) {
            throw new RuntimeException('Không thể chuẩn bị dữ liệu rule.');
        }

        $statement->bind_param(
            'ssssssii',
            $rule['file_type'],
            $rule['field_name'],
            $rule['display_name'],
            $rule['rule_type'],
            $rule['rule_value'],
            $rule['error_message'],
            $rule['is_active'],
            $id
        );
        if (!$statement->execute()) {
            $statement->close();
            throw new RuntimeException('Không thể cập nhật rule.');
        }
        $statement->close();
        return true;
    }

    public function updateActive($id, $isActive)
    {
        $statement = $this->connection->prepare(
            'UPDATE xml_validation_rules SET is_active = ? WHERE id = ?'
        );
        if (!$statement) {
            throw new RuntimeException('Không thể chuẩn bị trạng thái rule.');
        }
        $isActive = $isActive ? 1 : 0;
        $statement->bind_param('ii', $isActive, $id);
        if (!$statement->execute()) {
            $statement->close();
            throw new RuntimeException('Không thể cập nhật trạng thái rule.');
        }
        $exists = $statement->affected_rows > 0;
        $statement->close();

        if (!$exists) {
            $check = $this->connection->prepare('SELECT id FROM xml_validation_rules WHERE id = ? LIMIT 1');
            if (!$check) {
                throw new RuntimeException('Không thể kiểm tra rule.');
            }
            $check->bind_param('i', $id);
            $check->execute();
            $check->store_result();
            $exists = $check->num_rows > 0;
            $check->close();
        }
        return $exists;
    }

    public function delete($id)
    {
        $statement = $this->connection->prepare('DELETE FROM xml_validation_rules WHERE id = ?');
        if (!$statement) {
            throw new RuntimeException('Không thể chuẩn bị thao tác xóa rule.');
        }
        $statement->bind_param('i', $id);
        $statement->execute();
        $deleted = $statement->affected_rows > 0;
        $statement->close();
        return $deleted;
    }
}
