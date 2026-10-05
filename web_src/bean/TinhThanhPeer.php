<?php

class TinhThanhPeer
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
        $total = (int) $this->connection->query('SELECT COUNT(*) total FROM tinhthanh')->fetch_assoc()['total'];
        $where = '';
        $like = '%' . $search . '%';
        if ($search !== '') {
            $where = ' WHERE ten_tinhthanh LIKE ? OR ma_cu LIKE ? OR ma_sau_sapnhap LIKE ? ';
            $count = $this->connection->prepare('SELECT COUNT(*) total FROM tinhthanh' . $where);
            $count->bind_param('sss', $like, $like, $like);
            $count->execute();
            $filtered = (int) $count->get_result()->fetch_assoc()['total'];
            $count->close();
        } else {
            $filtered = $total;
        }
        $statement = $this->connection->prepare(
            'SELECT id, ten_tinhthanh, ma_cu, ma_sau_sapnhap
             FROM tinhthanh' . $where . ' ORDER BY ma_cu ASC LIMIT ' . $start . ', ' . $length
        );
        if ($search !== '') $statement->bind_param('sss', $like, $like, $like);
        $statement->execute();
        $result = $statement->get_result();
        $items = array();
        while ($row = $result->fetch_assoc()) $items[] = $row;
        $statement->close();
        return array('data' => $items, 'total' => $total, 'filtered' => $filtered);
    }
}
