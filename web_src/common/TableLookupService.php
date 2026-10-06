<?php

class TableLookupService
{
    private $connection;
    private $existsCache = array();
    private static $databaseSources = null;

    private static $allowedSources = array(
        'icd10' => array(
            'table' => 'icd10',
            'columns' => array('code'),
            'conditions' => array('is_active', 'is_leaf', 'version', 'chapter_code', 'type_code')
        ),
        'nhom_bhyt' => array(
            'table' => 'nhom_BHYT',
            'columns' => array('id'),
            'conditions' => array('dien')
        ),
        'dan_toc' => array(
            'table' => 'dan_toc',
            'columns' => array('ma'),
            'conditions' => array('is_active')
        ),
        'khoa' => array(
            'table' => 'khoa',
            'columns' => array('ma'),
            'conditions' => array('is_active')
        ),
        'ma_tinhthanh' => array(
            'table' => 'tinhthanh',
            'columns' => array('ma_cu', 'ma_sau_sapnhap'),
            'conditions' => array()
        ),
        'tinhthanh' => array(
            'table' => 'tinhthanh',
            'columns' => array('ma_cu', 'ma_sau_sapnhap'),
            'conditions' => array()
        ),
        'ma_doituong_kcb' => array(
            'table' => 'doituong_kcb',
            'columns' => array('ma'),
            'conditions' => array('is_active')
        ),
        'doituong_kcb' => array(
            'table' => 'doituong_kcb',
            'columns' => array('ma'),
            'conditions' => array('is_active')
        ),
        'tan_duoc' => array(
            'table' => 'tan_duoc',
            'columns' => array('ma_hoat_chat', 'sdk_gpnk', 'sdk_chuan_hoa'),
            'conditions' => array('is_active', 'nhom_tieu_chi', 'goi_thau', 'tinh_thanh')
        )
    );

    public function __construct()
    {
        global $connect;
        $db = new db_mysql();
        $db->connect();
        $db->selectdb();
        $this->connection = $connect;
    }

    public static function normalizeConfig(array $config)
    {
        $tableKey = isset($config['table']) ? strtolower(trim((string) $config['table'])) : '';
        $column = isset($config['column']) ? trim((string) $config['column']) : '';
        if ($tableKey === '' || $column === '') {
            throw new RuntimeException('TABLE_EXISTS cần có table và column.');
        }
        $allowedSources = self::getAllowedSources();
        if (!isset($allowedSources[$tableKey])) {
            throw new RuntimeException('Bảng ' . $tableKey . ' không được phép dùng trong TABLE_EXISTS.');
        }

        $source = $allowedSources[$tableKey];
        if (!in_array($column, $source['columns'], true)) {
            throw new RuntimeException('Cột ' . $column . ' không được phép đối chiếu trong bảng ' . $source['table'] . '.');
        }

        $conditions = isset($config['conditions']) ? $config['conditions'] : array();
        if (!is_array($conditions)) {
            throw new RuntimeException('conditions của TABLE_EXISTS phải là một JSON object.');
        }
        foreach ($conditions as $conditionColumn => $conditionValue) {
            if (!in_array($conditionColumn, $source['conditions'], true)) {
                throw new RuntimeException(
                    'Cột điều kiện ' . $conditionColumn . ' không được phép dùng trong bảng ' . $source['table'] . '.'
                );
            }
            if (!is_scalar($conditionValue) && $conditionValue !== null) {
                throw new RuntimeException('Giá trị điều kiện ' . $conditionColumn . ' phải là giá trị đơn hoặc null.');
            }
        }

        return array(
            'table' => $source['table'],
            'column' => $column,
            'conditions' => $conditions
        );
    }

    private static function getAllowedSources()
    {
        if (self::$databaseSources !== null) return self::$databaseSources;
        global $connect;
        if (!($connect instanceof mysqli)) return self::$allowedSources;

        $exists = $connect->query("SHOW TABLES LIKE 'table_lookup_sources'");
        if (!$exists || $exists->num_rows === 0) {
            if ($exists) $exists->free();
            return self::$allowedSources;
        }
        $exists->free();

        $result = $connect->query(
            'SELECT source_key, table_name, allowed_columns, condition_columns
             FROM table_lookup_sources WHERE is_active=1'
        );
        if (!$result) return self::$allowedSources;
        $sources = array();
        while ($row = $result->fetch_assoc()) {
            $columns = json_decode($row['allowed_columns'], true);
            $conditions = json_decode($row['condition_columns'], true);
            if (!is_array($columns) || !$columns) continue;
            $sources[strtolower($row['source_key'])] = array(
                'table' => $row['table_name'],
                'columns' => array_values($columns),
                'conditions' => is_array($conditions) ? array_values($conditions) : array()
            );
        }
        $result->free();
        self::$databaseSources = $sources;
        return self::$databaseSources;
    }

    public function exists($value, array $config)
    {
        $config = self::normalizeConfig($config);
        $normalizedValue = is_scalar($value) ? trim((string) $value) : '';
        $cacheKey = json_encode(array($config, $normalizedValue));
        if ($cacheKey !== false && array_key_exists($cacheKey, $this->existsCache)) {
            return $this->existsCache[$cacheKey];
        }

        $sql = 'SELECT 1 FROM `' . $config['table'] . '` WHERE `' . $config['column'] . '` = ?';
        $parameters = array($normalizedValue);

        foreach ($config['conditions'] as $column => $conditionValue) {
            if ($conditionValue === null) {
                $sql .= ' AND `' . $column . '` IS NULL';
                continue;
            }
            $sql .= ' AND `' . $column . '` = ?';
            $parameters[] = (string) $conditionValue;
        }
        $sql .= ' LIMIT 1';

        $statement = $this->connection->prepare($sql);
        if (!$statement) {
            throw new RuntimeException('Không thể chuẩn bị truy vấn TABLE_EXISTS.');
        }

        $types = str_repeat('s', count($parameters));
        $statement->bind_param($types, ...$parameters);
        if (!$statement->execute()) {
            $statement->close();
            throw new RuntimeException('Không thể thực hiện truy vấn TABLE_EXISTS.');
        }
        $statement->store_result();
        $exists = $statement->num_rows > 0;
        $statement->close();
        if ($cacheKey !== false) {
            $this->existsCache[$cacheKey] = $exists;
        }
        return $exists;
    }
}
