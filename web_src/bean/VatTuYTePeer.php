<?php

class VatTuYTePeer
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
            $clauses[] = '(ma_vat_tu LIKE ? OR nhom_vat_tu LIKE ? OR ten_vat_tu LIKE ? OR ma_hieu LIKE ?
                           OR quy_cach LIKE ? OR hang_sx LIKE ? OR nha_thau LIKE ? OR quyet_dinh LIKE ?
                           OR ma_vtyt_bv LIKE ? OR ten_vtyt_bv LIKE ? OR so_luu_hanh LIKE ?)';
            $like = '%' . $search . '%';
            for ($i = 0; $i < 11; $i++) {
                $params[] = $like;
            }
            $types .= 'sssssssssss';
        }

        if ($hieuLuc !== '') {
            $clauses[] = 'hieu_luc = ?';
            $params[] = $hieuLuc;
            $types .= 's';
        }

        $where = $clauses ? ' WHERE ' . implode(' AND ', $clauses) : '';

        $totalResult = $this->connection->query('SELECT COUNT(*) AS total FROM vattuyte');
        if (!$totalResult) {
            throw new RuntimeException($this->connection->error);
        }
        $total = (int) $totalResult->fetch_assoc()['total'];
        $filtered = $total;

        if ($where !== '') {
            $count = $this->connection->prepare('SELECT COUNT(*) AS total FROM vattuyte' . $where);
            if (!$count) {
                throw new RuntimeException($this->connection->error);
            }
            $count->bind_param($types, ...$params);
            $count->execute();
            $countResult = $count->get_result();
            $filtered = (int) $countResult->fetch_assoc()['total'];
            $count->close();
        }

        $sql = 'SELECT id, stt, ma_vat_tu, nhom_vat_tu, ten_vat_tu, ma_hieu, quy_cach,
                       hang_sx, nuoc_sx, don_vi_tinh, don_gia, don_gia_bh, tyle_tt_bh,
                       nha_thau, ma_cskcb, quyet_dinh, hieu_luc
                FROM vattuyte' . $where . '
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
             FROM vattuyte
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
