<?php
require_once 'web_src/bean/KhoaPhongCatalogPeer.php';
class khoaphongAction
{
    public static $listRole = 'khoaphong';
    private $request; private $peer;
    function __construct() { $this->request = new Request(); $this->peer = new KhoaPhongCatalogPeer(); $this->request->setTitle('Danh mục khoa phòng'); }
    function index() { $this->request->setAttribute('script', '<script src="' . _DEFAULT_URL_ . 'js/khoaphong-page.js?' . _DEFAULT_VERSION_JS_CSS_ . '&amp;v=' . filemtime(__DIR__ . '/../../js/khoaphong-page.js') . '"></script>'); $this->request->setModel('www/khoaphong/index.php'); return true; }
    function getData()
    {
        $draw = isset($_POST['draw']) ? (int) $_POST['draw'] : 0;

        try {
            $page = $this->peer->getPage($_POST['searchText'] ?? '');

            return $this->request->json_response(json_encode(array(
                'draw' => $draw,
                'recordsTotal' => $page['total'],
                'recordsFiltered' => $page['filtered'],
                'data' => $page['data']
            ), JSON_UNESCAPED_UNICODE));
        } catch (Throwable $exception) {
            return $this->request->json_response(json_encode(array(
                'draw' => $draw,
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => array(),
                'error' => $exception->getMessage()
            ), JSON_UNESCAPED_UNICODE));
        }
    }

    private $catalogDb;

    // Connection and table schema belong to this catalog action.
    private function catalogConnect()
    {
        if ($this->catalogDb instanceof mysqli) {
            return;
        }
        global $connect;
        $database = new db_mysql();
        $database->connect();
        $database->selectdb();
        if (!($connect instanceof mysqli)) {
            throw new RuntimeException('Không kết nối được cơ sở dữ liệu.');
        }
        $this->catalogDb = $connect;
        $this->catalogDb->set_charset('utf8mb4');
    }

    private function catalogSchema()
    {
        $this->catalogConnect();
        $table = 'khoa';
        $result = $this->catalogDb->query('SHOW COLUMNS FROM `' . $table . '`');
        if (!$result) {
            throw new RuntimeException('Không đọc được cấu trúc bảng danh mục.');
        }
        $columns = array();
        $primary = null;
        foreach ($result as $info) {
            $field = $info['Field'];
            if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_]*$/', $field)) {
                throw new RuntimeException('Tên cột không hợp lệ.');
            }
            if ($info['Key'] === 'PRI') {
                if ($primary !== null) {
                    throw new RuntimeException('Danh mục có khóa chính phức hợp chưa được hỗ trợ.');
                }
                $primary = $field;
            }
            $type = strtolower($info['Type']);
            $generated = stripos($info['Extra'], 'generated') !== false;
            $system = in_array(strtolower($field), array('created_at', 'updated_at', 'create_at', 'update_at'), true);
            $columns[] = array(
                'name' => $field,
                'type' => $type,
                'nullable' => $info['Null'] === 'YES',
                'default' => $info['Default'],
                'primary' => $info['Key'] === 'PRI',
                'auto' => stripos($info['Extra'], 'auto_increment') !== false,
                'editable' => !$generated && !$system && !preg_match('/^(blob|binary|varbinary|geometry)/', $type)
            );
        }
        if ($primary === null) {
            throw new RuntimeException('Danh mục chưa có khóa chính.');
        }
        $rowKey = $primary;
        if (!in_array($rowKey, array_column($columns, 'name'), true)) {
            throw new RuntimeException('Không xác định được khóa của bản ghi.');
        }
        return array('module' => 'khoaphong', 'table' => $table, 'primary' => $primary,
            'rowKey' => $rowKey, 'fields' => $columns);
    }

    private function catalogColumnSql($name)
    {
        return '`' . $name . '`'; // Validated against SHOW COLUMNS.
    }

    private function catalogExecute($sql, $values)
    {
        $statement = $this->catalogDb->prepare($sql);
        if (!$statement) {
            throw new RuntimeException('Không chuẩn bị được câu lệnh lưu dữ liệu.');
        }
        if ($values) {
            $types = str_repeat('s', count($values));
            $statement->bind_param($types, ...$values);
        }
        $statement->execute();
        return $statement;
    }

    private function catalogGetOne($key)
    {
        $schema = $this->catalogSchema();
        if (!is_scalar($key) || (string) $key === '') {
            throw new InvalidArgumentException('Mã bản ghi không hợp lệ.');
        }
        $statement = $this->catalogExecute('SELECT * FROM `' . $schema['table'] . '` WHERE '
            . $this->catalogColumnSql($schema['rowKey']) . ' = ? LIMIT 1', array((string) $key));
        $row = $statement->get_result()->fetch_assoc();
        $statement->close();
        if (!$row) {
            throw new RuntimeException('Không tìm thấy bản ghi.');
        }
        return $row;
    }

    private function catalogNormalize($column, $value)
    {
        if ($value === null || $value === '') {
            if ($column['nullable']) {
                return null;
            }
            if ($column['default'] !== null) {
                return (string) $column['default'];
            }
            throw new InvalidArgumentException('Vui lòng nhập ' . $column['name'] . '.');
        }
        if (!is_scalar($value)) {
            throw new InvalidArgumentException('Giá trị trường ' . $column['name'] . ' không hợp lệ.');
        }
        $value = trim((string) $value);
        $type = $column['type'];
        if (preg_match('/^(tinyint|smallint|mediumint|int|integer|bigint)\b/', $type)) {
            if (!preg_match('/^-?\d+$/D', $value) ||
                (strpos($type, 'unsigned') !== false && $value[0] === '-')) {
                throw new InvalidArgumentException($column['name'] . ' phải là số nguyên hợp lệ.');
            }
        } elseif (preg_match('/^(decimal|float|double|real)\b/', $type)) {
            if (!preg_match('/^-?\d+(\.\d+)?$/D', $value)) {
                throw new InvalidArgumentException($column['name'] . ' phải là số hợp lệ.');
            }
        } elseif (preg_match('/^date\b/', $type)) {
            $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
            if (!$date || $date->format('Y-m-d') !== $value) {
                throw new InvalidArgumentException($column['name'] . ' phải là ngày YYYY-MM-DD.');
            }
        } elseif (preg_match('/^(datetime|timestamp)\b/', $type)) {
            $date = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $value);
            if (!$date || $date->format('Y-m-d H:i:s') !== $value) {
                throw new InvalidArgumentException($column['name'] . ' phải là ngày giờ hợp lệ.');
            }
        } elseif (preg_match('/^(char|varchar)\((\d+)\)/', $type, $matches)) {
            $length = function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
            if ($length > (int) $matches[2]) {
                throw new InvalidArgumentException($column['name'] . ' vượt quá ' . $matches[2] . ' ký tự.');
            }
        }
        if ($column['name'] === 'properties_json') {
            json_decode($value, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new InvalidArgumentException('properties_json phải là JSON hợp lệ.');
            }
        }
        return $value;
    }

    private function catalogValuesForWrite($schema, $input, $creating)
    {
        if (!is_array($input) || array_values($input) === $input) {
            throw new InvalidArgumentException('Dữ liệu biểu mẫu không hợp lệ.');
        }
        $allowed = array_column($schema['fields'], 'name');
        foreach (array_keys($input) as $name) {
            if (!in_array($name, $allowed, true)) {
                throw new InvalidArgumentException('Trường dữ liệu không được phép: ' . $name);
            }
        }
        $values = array();
        foreach ($schema['fields'] as $column) {
            $name = $column['name'];
            if (!$column['editable'] || $column['auto'] || (!$creating && $column['primary'])) {
                continue;
            }
            if (!array_key_exists($name, $input)) {
                if ($creating && !$column['nullable'] && $column['default'] === null) {
                    throw new InvalidArgumentException('Vui lòng nhập ' . $name . '.');
                }
                continue;
            }
            $values[$name] = $this->catalogNormalize($column, $input[$name]);
        }
        if (!$values) {
            throw new InvalidArgumentException('Không có dữ liệu để lưu.');
        }
        return $values;
    }

    private function catalogSave($input)
    {
        $schema = $this->catalogSchema();
        $values = $this->catalogValuesForWrite($schema, $input, true);
        // Legacy internal khoa table defines these dates without SQL defaults.
        if ($schema['table'] === 'khoa') {
            foreach (array('CREATE_AT', 'UPDATE_AT') as $field) {
                if (in_array($field, array_column($schema['fields'], 'name'), true)) {
                    $values[$field] = date('Y-m-d');
                }
            }
        }
        $names = array_keys($values);
        $sql = 'INSERT INTO `' . $schema['table'] . '` (' . implode(', ', array_map(array($this, 'catalogColumnSql'), $names))
            . ') VALUES (' . implode(', ', array_fill(0, count($names), '?')) . ')';
        $stmt = $this->catalogExecute($sql, array_values($values));
        $id = $this->catalogDb->insert_id;
        $stmt->close();
        return $id ?: (isset($values[$schema['primary']]) ? $values[$schema['primary']] : null);
    }

    private function catalogUpdate($key, $input)
    {
        $schema = $this->catalogSchema();
        $this->catalogGetOne($key);
        $values = $this->catalogValuesForWrite($schema, $input, false);
        if ($schema['table'] === 'khoa' && in_array('UPDATE_AT', array_column($schema['fields'], 'name'), true)) {
            $values['UPDATE_AT'] = date('Y-m-d');
        }
        $set = array();
        foreach (array_keys($values) as $name) {
            $set[] = $this->catalogColumnSql($name) . ' = ?';
        }
        $params = array_values($values);
        $params[] = (string) $key;
        $stmt = $this->catalogExecute('UPDATE `' . $schema['table'] . '` SET ' . implode(', ', $set)
            . ' WHERE ' . $this->catalogColumnSql($schema['rowKey']) . ' = ? LIMIT 1', $params);
        $stmt->close();
    }

    private function catalogDelete($key)
    {
        $schema = $this->catalogSchema();
        $this->catalogGetOne($key);
        $stmt = $this->catalogExecute('DELETE FROM `' . $schema['table'] . '` WHERE '
            . $this->catalogColumnSql($schema['rowKey']) . ' = ? LIMIT 1', array((string) $key));
        $stmt->close();
    }

    private function catalogAllowed($operation = null)
    {
        if (empty($_SESSION['sUserLogin'])) {
            return false;
        }
        if (isset($_SESSION['AdminType']) && (int) $_SESSION['AdminType'] === 1) {
            return true;
        }
        $roles = isset($_SESSION['quyen']) && is_array($_SESSION['quyen']) ? $_SESSION['quyen'] : array();
        if (!in_array('khoaphong', $roles, true)) {
            return false;
        }
        return $operation === null || in_array('khoaphong' . '.' . $operation, $roles, true);
    }

    private function catalogJson($payload, $status = 200)
    {
        http_response_code($status);
        return $this->request->json_response(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    private function catalogDispatch($operation, $callback)
    {
        if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->catalogJson(array('success' => false, 'message' => 'Yêu cầu phải sử dụng POST.'), 405);
        }
        if (!$this->catalogAllowed($operation)) {
            return $this->catalogJson(array('success' => false, 'message' => 'Bạn không có quyền thực hiện thao tác này.'), 403);
        }
        $csrf = isset($_POST['csrf']) && is_string($_POST['csrf']) ? $_POST['csrf'] : '';
        if (empty($_SESSION['catalog_csrf']) || !hash_equals($_SESSION['catalog_csrf'], $csrf)) {
            return $this->catalogJson(array('success' => false, 'message' => 'Phiên làm việc không hợp lệ, vui lòng tải lại trang.'), 403);
        }

        try {
            return $this->catalogJson(array_merge(array('success' => true), $callback()));
        } catch (InvalidArgumentException $exception) {
            return $this->catalogJson(array('success' => false, 'message' => $exception->getMessage()), 422);
        } catch (mysqli_sql_exception $exception) {
            $code = (int) $exception->getCode();
            if ($code === 1062) {
                $message = 'Mã hoặc giá trị duy nhất đã tồn tại.';
            } elseif ($code === 1451) {
                $message = 'Bản ghi đang được sử dụng, không thể xóa hoặc thay đổi khóa.';
            } elseif ($code === 1452) {
                $message = 'Giá trị liên kết đến danh mục khác không tồn tại.';
            } else {
                error_log('Catalog CRUD database error: ' . $exception->getMessage());
                $message = 'Không thể lưu dữ liệu. Vui lòng kiểm tra lại.';
            }
            return $this->catalogJson(array('success' => false, 'message' => $message), 422);
        } catch (Throwable $exception) {
            error_log('Catalog CRUD error: ' . $exception->getMessage());
            return $this->catalogJson(array('success' => false, 'message' => 'Không thể xử lý danh mục.'), 500);
        }
    }

    private function catalogKey()
    {
        if (!isset($_POST['key']) || !is_scalar($_POST['key']) || (string) $_POST['key'] === '') {
            throw new InvalidArgumentException('Vui lòng chọn bản ghi.');
        }
        return (string) $_POST['key'];
    }

    private function catalogData()
    {
        if (!isset($_POST['data']) || !is_string($_POST['data'])) {
            throw new InvalidArgumentException('Thiếu dữ liệu biểu mẫu.');
        }
        $data = json_decode($_POST['data'], true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($data) || !$data) {
            throw new InvalidArgumentException('Dữ liệu biểu mẫu không hợp lệ.');
        }
        return $data;
    }


    // The upload and download endpoints are owned by this catalog action.
    public function downloadExcelTemplate()
    {
        if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->catalogJson(array('success' => false, 'message' => 'Vui lòng dùng POST.'), 405);
        }
        if (!$this->catalogAllowed()) {
            return $this->catalogJson(array('success' => false, 'message' => 'Bạn không có quyền xem danh mục.'), 403);
        }
        $csrf = isset($_POST['csrf']) && is_string($_POST['csrf']) ? $_POST['csrf'] : '';
        if (empty($_SESSION['catalog_csrf']) || !hash_equals($_SESSION['catalog_csrf'], $csrf)) {
            return $this->catalogJson(array('success' => false, 'message' => 'Phiên làm việc không hợp lệ.'), 403);
        }
        try {
            require_once 'web_src/common/CatalogExcel.php';
            CatalogExcel::sendTemplate($this->catalogSchema());
            exit;
        } catch (Throwable $exception) {
            error_log('Catalog Excel template error: ' . $exception->getMessage());
            return $this->catalogJson(array('success' => false, 'message' => 'Không tạo được Excel mẫu.'), 500);
        }
    }

    public function importExcel()
    {
        return $this->catalogDispatch('save', function () {
            require_once 'web_src/common/CatalogExcel.php';
            $file = isset($_FILES['file']) ? $_FILES['file'] : null;
            if (!is_array($file) || !isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK ||
                !isset($file['tmp_name'], $file['name'], $file['size']) ||
                !is_string($file['name']) || strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) !== 'xlsx' ||
                (int) $file['size'] <= 0 || (int) $file['size'] > CatalogExcel::MAX_BYTES ||
                !is_uploaded_file($file['tmp_name'])) {
                throw new InvalidArgumentException('Vui lòng chọn file .xlsx hợp lệ, dung lượng tối đa 5 MB.');
            }
            $schema = $this->catalogSchema();
            $rows = CatalogExcel::read($file['tmp_name'], $schema);
            $errors = array();
            foreach ($rows as $line => $values) {
                try {
                    $this->catalogValuesForWrite($schema, $values, true);
                } catch (InvalidArgumentException $exception) {
                    $errors[] = 'Dòng ' . $line . ': ' . $exception->getMessage();
                    if (count($errors) >= 10) {
                        break;
                    }
                }
            }
            if ($errors) {
                throw new InvalidArgumentException("Excel có lỗi, chưa nhập dữ liệu:
" . implode("
", $errors));
            }
            $this->catalogDb->begin_transaction();
            try {
                foreach ($rows as $line => $values) {
                    try {
                        $this->catalogSave($values);
                    } catch (mysqli_sql_exception $exception) {
                        $code = (int) $exception->getCode();
                        if ($code === 1062) {
                            throw new InvalidArgumentException('Dòng ' . $line . ': Mã hoặc giá trị duy nhất đã tồn tại.', 0, $exception);
                        }
                        if ($code === 1452) {
                            throw new InvalidArgumentException('Dòng ' . $line . ': Dữ liệu liên kết không tồn tại.', 0, $exception);
                        }
                        throw $exception;
                    }
                }
                $this->catalogDb->commit();
            } catch (Throwable $exception) {
                $this->catalogDb->rollback();
                throw $exception;
            }
            return array('count' => count($rows), 'message' => 'Đã nhập ' . count($rows) . ' bản ghi từ Excel.');
        });
    }

    public function getSchema()
    {
        return $this->catalogDispatch(null, function () {
            return array('schema' => $this->catalogSchema(), 'permissions' => array(
                'save' => $this->catalogAllowed('save'),
                'update' => $this->catalogAllowed('update'),
                'delete' => $this->catalogAllowed('delete')
            ));
        });
    }

    public function getOne()
    {
        return $this->catalogDispatch(null, function () {
            return array('row' => $this->catalogGetOne($this->catalogKey()));
        });
    }

    public function save()
    {
        return $this->catalogDispatch('save', function () {
            return array('id' => $this->catalogSave($this->catalogData()), 'message' => 'Thêm bản ghi thành công.');
        });
    }

    public function update()
    {
        return $this->catalogDispatch('update', function () {
            $this->catalogUpdate($this->catalogKey(), $this->catalogData());
            return array('message' => 'Cập nhật bản ghi thành công.');
        });
    }

    public function delete()
    {
        return $this->catalogDispatch('delete', function () {
            $this->catalogDelete($this->catalogKey());
            return array('message' => 'Xóa bản ghi thành công.');
        });
    }

}
