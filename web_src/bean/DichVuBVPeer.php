<?php

class DichVuBVPeer
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

    public function getPage($start, $length, $search, $hieuLuc)
    {
        $start = max(0, (int) $start);
        $length = max(10, min(200, (int) $length));
        $search = trim((string) $search);
        $hieuLuc = trim((string) $hieuLuc);

        $clauses = array();
        $params = array();
        $types = '';

        if ($search !== '') {
            $clauses[] = '(ma_tuong_duong LIKE ? OR ten_dvkt_pheduyet LIKE ? OR ten_dvkt_gia LIKE ?
                           OR quyet_dinh LIKE ? OR ma LIKE ? OR ten LIKE ? OR so_quyet_dinh LIKE ?
                           OR ma_nhom_5937 LIKE ?)';
            $like = '%' . $search . '%';
            for ($i = 0; $i < 8; $i++) {
                $params[] = $like;
            }
            $types .= 'ssssssss';
        }

        if ($hieuLuc !== '') {
            $clauses[] = 'hieu_luc = ?';
            $params[] = $hieuLuc;
            $types .= 's';
        }

        $where = $clauses ? ' WHERE ' . implode(' AND ', $clauses) : '';

        $totalResult = $this->connection->query('SELECT COUNT(*) AS total FROM dichvu_bv');
        if (!$totalResult) {
            throw new RuntimeException($this->connection->error);
        }
        $total = (int) $totalResult->fetch_assoc()['total'];
        $filtered = $total;

        if ($where !== '') {
            $count = $this->connection->prepare('SELECT COUNT(*) AS total FROM dichvu_bv' . $where);
            if (!$count) {
                throw new RuntimeException($this->connection->error);
            }
            $count->bind_param($types, ...$params);
            $count->execute();
            $filtered = (int) $count->get_result()->fetch_assoc()['total'];
            $count->close();
        }

        $sql = 'SELECT id, stt, ma_tuong_duong, ten_dvkt_pheduyet, ten_dvkt_gia,
                       phan_loai_pttt, don_gia, quyet_dinh, tu_ngay, den_ngay,
                       ma, ten, gia, gia_dvkt, so_quyet_dinh, ma_nhom_5937,
                       hieu_luc, trang_thai_gui, ket_qua_giam_dinh
                FROM dichvu_bv' . $where . '
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

    public function getHieuLucList()
    {
        $result = $this->connection->query(
            "SELECT DISTINCT hieu_luc
             FROM dichvu_bv
             WHERE hieu_luc IS NOT NULL AND TRIM(hieu_luc) <> ''
             ORDER BY hieu_luc"
        );
        if (!$result) {
            throw new RuntimeException($this->connection->error);
        }

        $items = array();
        while ($row = $result->fetch_assoc()) {
            $items[] = $row['hieu_luc'];
        }
        return $items;
    }
}
