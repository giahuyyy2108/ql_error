<?php

class DanhMucThuocPeer
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

    public function getPage($start, $length, $search, $province)
    {
        $start = max(0, (int) $start);
        $length = max(10, min(200, (int) $length));
        $search = trim((string) $search);
        $province = trim((string) $province);
        $clauses = array();
        $params = array();
        $types = '';

        if ($search !== '') {
            $clauses[] = '(ma_hoat_chat LIKE ? OR ten_hoat_chat LIKE ? OR ten_thuoc LIKE ? OR sdk_gpnk LIKE ? OR sdk_chuan_hoa LIKE ? OR nha_san_xuat LIKE ?)';
            $like = '%' . $search . '%';
            for ($i = 0; $i < 6; $i++) $params[] = $like;
            $types .= 'ssssss';
        }
        if ($province !== '') {
            $clauses[] = 'tinh_thanh = ?';
            $params[] = $province;
            $types .= 's';
        }
        $where = $clauses ? ' WHERE ' . implode(' AND ', $clauses) : '';
        $total = (int) $this->connection->query('SELECT COUNT(*) AS total FROM danhmuc_thuoc')->fetch_assoc()['total'];
        $filtered = $total;
        if ($where !== '') {
            $count = $this->connection->prepare('SELECT COUNT(*) AS total FROM danhmuc_thuoc' . $where);
            $count->bind_param($types, ...$params);
            $count->execute();
            $filtered = (int) $count->get_result()->fetch_assoc()['total'];
            $count->close();
        }

        $sql = 'SELECT id, stt_nguon, ma_hoat_chat, ten_hoat_chat, duong_dung_dang_bao_che,
                       nong_do_ham_luong, ten_thuoc, sdk_gpnk, sdk_chuan_hoa, nha_san_xuat,
                       nuoc_san_xuat, quy_cach_dong_goi, don_vi_tinh, so_luong, don_gia,
                       thanh_tien, nha_thau_trung_thau, nhom_tieu_chi, goi_thau, don_vi_cong_bo,
                       tinh_thanh, so_quyet_dinh, ngay_cong_bo
                FROM danhmuc_thuoc' . $where . ' ORDER BY stt_nguon ASC, id ASC LIMIT ' . $start . ', ' . $length;
        $statement = $this->connection->prepare($sql);
        if ($types !== '') $statement->bind_param($types, ...$params);
        $statement->execute();
        $result = $statement->get_result();
        $items = array();
        while ($row = $result->fetch_assoc()) $items[] = $row;
        $statement->close();
        return array('data' => $items, 'total' => $total, 'filtered' => $filtered);
    }

    public function getProvinces()
    {
        $result = $this->connection->query("SELECT DISTINCT tinh_thanh FROM danhmuc_thuoc WHERE tinh_thanh IS NOT NULL AND tinh_thanh <> '' ORDER BY tinh_thanh");
        $items = array();
        while ($row = $result->fetch_assoc()) $items[] = $row['tinh_thanh'];
        return $items;
    }
}
