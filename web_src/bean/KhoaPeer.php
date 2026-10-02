<?php

class KhoaPeer
{
    private $connection;

    public function __construct()
    {
        global $connect;
        $db = new db_mysql();
        $db->connect();
        $db->selectdb();
        $this->connection = $connect;
        $this->connection->set_charset('utf8mb4');
    }

    public function getPage($start, $length, $search)
    {
        $start = max(0, (int) $start);
        $length = max(10, min(200, (int) $length));
        $search = trim((string) $search);

        $totalResult = $this->connection->query('SELECT COUNT(*) AS total FROM khoa');
        if (!$totalResult) {
            throw new RuntimeException('Không thể tải danh mục khoa.');
        }
        $total = (int) $totalResult->fetch_assoc()['total'];
        $totalResult->free();

        $where = '';
        $like = '%' . $search . '%';
        if ($search !== '') {
            $where = ' WHERE khoa.ma LIKE ? OR khoa.ten LIKE ? OR khoa.ghi_chu LIKE ?
                       OR khoa.ma_khoa_goc LIKE ? OR khoa.quyet_dinh LIKE ? ';
            $countStatement = $this->connection->prepare('SELECT COUNT(*) AS total FROM khoa' . $where);
            if (!$countStatement) {
                throw new RuntimeException('Không thể chuẩn bị tìm kiếm danh mục khoa.');
            }
            $countStatement->bind_param('sssss', $like, $like, $like, $like, $like);
            $countStatement->execute();
            $filtered = (int) $countStatement->get_result()->fetch_assoc()['total'];
            $countStatement->close();
        } else {
            $filtered = $total;
        }

        $statement = $this->connection->prepare(
            'SELECT khoa.ma, khoa.stt, khoa.ten, khoa.ghi_chu, khoa.ma_khoa_goc,
                    khoa.is_active, khoa.quyet_dinh, khoa.ngay_hieu_luc, khoa.source_url
             FROM khoa
             LEFT JOIN khoa AS khoa_goc ON khoa_goc.ma = khoa.ma_khoa_goc' . $where . '
             ORDER BY COALESCE(khoa.stt, khoa_goc.stt) ASC,
                      CASE WHEN khoa.stt IS NULL THEN 1 ELSE 0 END ASC,
                      khoa.ma ASC
             LIMIT ' . $start . ', ' . $length
        );
        if (!$statement) {
            throw new RuntimeException('Không thể chuẩn bị dữ liệu danh mục khoa.');
        }
        if ($search !== '') {
            $statement->bind_param('sssss', $like, $like, $like, $like, $like);
        }
        $statement->execute();
        $result = $statement->get_result();
        $items = array();
        while ($row = $result->fetch_assoc()) {
            $row['stt'] = $row['stt'] === null ? null : (int) $row['stt'];
            $row['is_active'] = (int) $row['is_active'];
            $items[] = $row;
        }
        $statement->close();

        return array('data' => $items, 'total' => $total, 'filtered' => $filtered);
    }
}
