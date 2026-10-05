<?php

class DoiTuongKcbPeer
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
        $total = (int) $this->connection->query('SELECT COUNT(*) total FROM doituong_kcb')->fetch_assoc()['total'];
        $where = '';
        $like = '%' . $search . '%';
        if ($search !== '') {
            $where = ' WHERE ma LIKE ? OR truong_hop LIKE ? OR quy_dinh LIKE ? OR muc_huong LIKE ? ';
            $count = $this->connection->prepare('SELECT COUNT(*) total FROM doituong_kcb' . $where);
            $count->bind_param('ssss', $like, $like, $like, $like);
            $count->execute();
            $filtered = (int) $count->get_result()->fetch_assoc()['total'];
            $count->close();
        } else {
            $filtered = $total;
        }
        $statement = $this->connection->prepare(
            'SELECT id, ma, truong_hop, quy_dinh, muc_huong, ghi_chu,
                    nguon, ngay_hieu_luc, is_active
             FROM doituong_kcb' . $where . ' ORDER BY id ASC LIMIT ' . $start . ', ' . $length
        );
        if ($search !== '') $statement->bind_param('ssss', $like, $like, $like, $like);
        $statement->execute();
        $result = $statement->get_result();
        $items = array();
        while ($row = $result->fetch_assoc()) {
            $row['is_active'] = (int) $row['is_active'];
            $items[] = $row;
        }
        $statement->close();
        return array('data' => $items, 'total' => $total, 'filtered' => $filtered);
    }
}
