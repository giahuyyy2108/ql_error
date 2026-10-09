<?php

class Icd10YhctPeer
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
        $total = (int) $this->connection->query(
            'SELECT COUNT(*) AS total FROM icd10_yhct'
        )->fetch_assoc()['total'];

        $where = '';
        $parameters = array();
        if ($search !== '') {
            $where = ' WHERE ma_dung_chung LIKE ? OR ma_icd10 LIKE ? OR ma_u LIKE ? OR ma_hoa LIKE ?
                       OR ten_benh_huong_dan LIKE ? OR ten_benh_y_hoc_hien_dai LIKE ?
                       OR benh_danh_yhct LIKE ? OR the_lam_sang LIKE ? ';
            $like = '%' . $search . '%';
            $parameters = array_fill(0, 8, $like);
            $count = $this->connection->prepare('SELECT COUNT(*) AS total FROM icd10_yhct' . $where);
            $count->bind_param(str_repeat('s', count($parameters)), ...$parameters);
            $count->execute();
            $filtered = (int) $count->get_result()->fetch_assoc()['total'];
            $count->close();
        } else {
            $filtered = $total;
        }

        $statement = $this->connection->prepare(
            'SELECT ma_dung_chung, ma_dung_chung_cha, ten_benh_huong_dan,
                    ten_benh_y_hoc_hien_dai, ma_icd10, benh_danh_yhct,
                    ma_u, the_lam_sang, ma_hoa, is_active
             FROM icd10_yhct' . $where . '
             ORDER BY CAST(ma_dung_chung AS UNSIGNED) ASC
             LIMIT ' . $start . ', ' . $length
        );
        if ($parameters) $statement->bind_param(str_repeat('s', count($parameters)), ...$parameters);
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
