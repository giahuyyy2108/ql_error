<?php

class DanTocPeer
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

        $totalResult = $this->connection->query('SELECT COUNT(*) AS total FROM dan_toc');
        if (!$totalResult) {
            throw new RuntimeException('Không thể tải danh mục dân tộc.');
        }
        $total = (int) $totalResult->fetch_assoc()['total'];
        $totalResult->free();

        $where = '';
        $like = '%' . $search . '%';
        if ($search !== '') {
            $where = ' WHERE ma LIKE ? OR ten LIKE ? OR ten_goi_khac LIKE ? ';
            $countStatement = $this->connection->prepare('SELECT COUNT(*) AS total FROM dan_toc' . $where);
            if (!$countStatement) {
                throw new RuntimeException('Không thể chuẩn bị tìm kiếm danh mục dân tộc.');
            }
            $countStatement->bind_param('sss', $like, $like, $like);
            $countStatement->execute();
            $filtered = (int) $countStatement->get_result()->fetch_assoc()['total'];
            $countStatement->close();
        } else {
            $filtered = $total;
        }

        $statement = $this->connection->prepare(
            'SELECT ma, ten, ten_goi_khac, is_active, source_url
             FROM dan_toc' . $where . ' ORDER BY ma ASC LIMIT ' . $start . ', ' . $length
        );
        if (!$statement) {
            throw new RuntimeException('Không thể chuẩn bị dữ liệu danh mục dân tộc.');
        }
        if ($search !== '') {
            $statement->bind_param('sss', $like, $like, $like);
        }
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
