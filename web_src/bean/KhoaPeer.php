<?php

class KhoaPeer
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
        $this->connection->set_charset('utf8mb4');
    }

    public function getPage($start, $length, $search)
    {
        $start = max(0, (int) $start);
        $length = (int) $length;
        $search = trim((string) $search);

        $totalResult = $this->connection->query('SELECT COUNT(*) AS total FROM khoa_phong');
        if (!$totalResult) {
            throw new RuntimeException('Không thể tải danh mục khoa.');
        }
        $total = (int) $totalResult->fetch_assoc()['total'];
        $totalResult->free();

        $where = '';
        $like = '%' . $search . '%';
        if ($search !== '') {
            $where = ' WHERE khoa_phong.ma LIKE ? OR khoa_phong.ten LIKE ? OR khoa_phong.ghi_chu LIKE ?
                       OR khoa_phong.ma_khoa_goc LIKE ? OR khoa_phong.quyet_dinh LIKE ? ';
            $countStatement = $this->connection->prepare('SELECT COUNT(*) AS total FROM khoa_phong' . $where);
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

        $limit = $length < 0 ? '' : ' LIMIT ' . $start . ', ' . max(1, $length);
        $statement = $this->connection->prepare(
            'SELECT khoa_phong.ma, khoa_phong.stt, khoa_phong.ten, khoa_phong.ghi_chu, khoa_phong.ma_khoa_goc,
                    khoa_phong.is_active, khoa_phong.quyet_dinh, khoa_phong.ngay_hieu_luc, khoa_phong.source_url
             FROM khoa_phong
             LEFT JOIN khoa_phong AS khoa_goc ON khoa_goc.ma = khoa_phong.ma_khoa_goc' . $where . '
             ORDER BY COALESCE(khoa_phong.stt, khoa_goc.stt) ASC,
                      CASE WHEN khoa_phong.stt IS NULL THEN 1 ELSE 0 END ASC,
                      khoa_phong.ma ASC' . $limit
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
