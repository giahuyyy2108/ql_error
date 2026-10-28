<?php

class TrangThietBiPeer
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

    public function getPage($start, $length, $search, $nuocSx)
    {
        $start = max(0, (int) $start);
        $length = max(10, min(200, (int) $length));
        $search = trim((string) $search);
        $nuocSx = trim((string) $nuocSx);

        $clauses = array();
        $params = array();
        $types = '';

        if ($search !== '') {
            $clauses[] = '(ten_tb LIKE ? OR ky_hieu LIKE ? OR congty_sx LIKE ? OR nuoc_sx LIKE ?
                           OR ma_may LIKE ? OR so_luu_hanh LIKE ?)';
            $like = '%' . $search . '%';
            for ($i = 0; $i < 6; $i++) {
                $params[] = $like;
            }
            $types .= 'ssssss';
        }

        if ($nuocSx !== '') {
            $clauses[] = 'nuoc_sx = ?';
            $params[] = $nuocSx;
            $types .= 's';
        }

        $where = $clauses ? ' WHERE ' . implode(' AND ', $clauses) : '';

        $totalResult = $this->connection->query('SELECT COUNT(*) AS total FROM trang_thiet_bi');
        if (!$totalResult) {
            throw new RuntimeException($this->connection->error);
        }
        $total = (int) $totalResult->fetch_assoc()['total'];
        $filtered = $total;

        if ($where !== '') {
            $count = $this->connection->prepare('SELECT COUNT(*) AS total FROM trang_thiet_bi' . $where);
            if (!$count) {
                throw new RuntimeException($this->connection->error);
            }
            $count->bind_param($types, ...$params);
            $count->execute();
            $filtered = (int) $count->get_result()->fetch_assoc()['total'];
            $count->close();
        }

        $sql = 'SELECT id, stt, ten_tb, ky_hieu, congty_sx, nuoc_sx, nam_sx, nam_sd,
                       ma_may, so_luu_hanh, hd_tu, hd_den, tu_ngay, den_ngay
                FROM trang_thiet_bi' . $where . '
                ORDER BY COALESCE(stt, 2147483647) ASC, id ASC
                LIMIT ' . $start . ', ' . $length;

        $statement = $this->connection->prepare($sql);
        if (!$statement) {
            throw new RuntimeException($this->connection->error);
        }
        if ($types !== '') {
            $statement->bind_param($types, ...$params);
        }
        $statement->execute();
        $result = $statement->get_result();
        $items = array();
        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
        }
        $statement->close();

        return array(
            'data' => $items,
            'total' => $total,
            'filtered' => $filtered
        );
    }

    public function getNuocSanXuatList()
    {
        $result = $this->connection->query(
            "SELECT DISTINCT nuoc_sx
             FROM trang_thiet_bi
             WHERE nuoc_sx IS NOT NULL AND TRIM(nuoc_sx) <> ''
             ORDER BY nuoc_sx"
        );
        if (!$result) {
            throw new RuntimeException($this->connection->error);
        }

        $items = array();
        while ($row = $result->fetch_assoc()) {
            $items[] = $row['nuoc_sx'];
        }
        return $items;
    }
}
