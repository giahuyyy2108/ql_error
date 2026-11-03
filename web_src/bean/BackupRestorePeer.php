<?php

class BackupRestorePeer
{
    private $dbsql;
    private $connection;

    public function __construct()
    {
        global $connect;
        $this->dbsql = new db_mysql();
        $this->dbsql->connect();
        $this->dbsql->selectdb();
        if (!$connect instanceof mysqli) {
            throw new RuntimeException('Không thể kết nối cơ sở dữ liệu.');
        }
        $this->connection = $connect;
    }

    private function setLog($chucnang)
    {
        $log = new Log();
        $log->set('chucnang', $chucnang);
        (new LogPeer())->ghiLog($log);
    }

    public function backup()
    {
        $result = $this->connection->query('SHOW FULL TABLES WHERE Table_type = \'BASE TABLE\'');
        if (!$result) {
            throw new RuntimeException('Không thể đọc danh sách bảng để sao lưu.');
        }

        $output = '-- QL Error database backup' . "\n";
        $output .= '-- Generated: ' . date('c') . "\n\n";
        $output .= "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\n";
        while ($tableRow = $result->fetch_row()) {
            $table = (string) $tableRow[0];
            $identifier = '`' . str_replace('`', '``', $table) . '`';
            $createResult = $this->connection->query('SHOW CREATE TABLE ' . $identifier);
            $createRow = $createResult ? $createResult->fetch_assoc() : null;
            if (!$createRow || empty($createRow['Create Table'])) {
                throw new RuntimeException('Không thể đọc cấu trúc bảng ' . $table . '.');
            }

            $output .= '-- Table structure for ' . $identifier . "\n";
            $output .= 'DROP TABLE IF EXISTS ' . $identifier . ";\n";
            $output .= $createRow['Create Table'] . ";\n\n";
            $createResult->free();

            $dataResult = $this->connection->query('SELECT * FROM ' . $identifier);
            if (!$dataResult) {
                throw new RuntimeException('Không thể đọc dữ liệu bảng ' . $table . '.');
            }
            while ($row = $dataResult->fetch_row()) {
                $values = array();
                foreach ($row as $value) {
                    $values[] = $value === null
                        ? 'NULL'
                        : "'" . $this->connection->real_escape_string((string) $value) . "'";
                }
                $output .= 'INSERT INTO ' . $identifier . ' VALUES (' . implode(', ', $values) . ");\n";
            }
            $dataResult->free();
            $output .= "\n";
        }
        $result->free();
        $output .= "SET FOREIGN_KEY_CHECKS=1;\n";

        $this->setLog('Backup');
        return $output;
    }

    public function restore($filename)
    {
        $safeName = basename((string) $filename);
        if ($safeName === '' || strtolower(pathinfo($safeName, PATHINFO_EXTENSION)) !== 'sql') {
            throw new InvalidArgumentException('File phục hồi không hợp lệ.');
        }

        $path = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'restore' . DIRECTORY_SEPARATOR . $safeName;
        $realPath = realpath($path);
        $restoreRoot = realpath(dirname($path));
        if ($realPath === false || $restoreRoot === false
            || strpos($realPath, $restoreRoot . DIRECTORY_SEPARATOR) !== 0
            || !is_file($realPath)) {
            throw new RuntimeException('Không tìm thấy file phục hồi.');
        }

        $sql = file_get_contents($realPath);
        if ($sql === false || trim($sql) === '') {
            throw new RuntimeException('File phục hồi rỗng hoặc không thể đọc.');
        }
        if (strlen($sql) > (int) _BACKUP_SIZE_) {
            throw new RuntimeException('File phục hồi vượt quá dung lượng cho phép.');
        }

        if (!$this->connection->multi_query($sql)) {
            throw new RuntimeException('Không thể thực thi file phục hồi: ' . $this->connection->error);
        }
        do {
            $queryResult = $this->connection->store_result();
            if ($queryResult instanceof mysqli_result) {
                $queryResult->free();
            }
            if (!$this->connection->more_results()) {
                break;
            }
            if (!$this->connection->next_result()) {
                throw new RuntimeException('Phục hồi bị gián đoạn: ' . $this->connection->error);
            }
        } while (true);

        $this->setLog('Restore');
        return 'Phục hồi thành công. Vui lòng kiểm tra lại dữ liệu.';
    }
}
