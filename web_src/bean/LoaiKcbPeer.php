<?php

class LoaiKcbPeer
{
    /** @var mysqli */
    private $connection;

    public function __construct()
    {
        global $connect;
        $db = new db_mysql();
        $db->connect();
        $db->selectdb();
        if (!($connect instanceof mysqli)) throw new RuntimeException('Không thể kết nối cơ sở dữ liệu.');
        $this->connection = $connect;
        $this->connection->set_charset('utf8mb4');
    }

    public function getList()
    {
        $result = $this->connection->query(
            'SELECT id, ma, truong_hop, quy_dinh, muc_huong, ghi_chu,
                    nguon, ngay_hieu_luc, is_active
             FROM loai_kcb ORDER BY ma ASC'
        );
        if (!$result) throw new RuntimeException('Không thể tải danh mục loại khám chữa bệnh.');
        $items = array();
        while ($row = $result->fetch_assoc()) {
            $row['id'] = (int) $row['id'];
            $row['is_active'] = (int) $row['is_active'];
            $items[] = $row;
        }
        $result->free();
        return $items;
    }
}
