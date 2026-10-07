<?php

class NhanVienYTePeer
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

    public function getPage($start, $length, $search)
    {
        $start = max(0, (int) $start);
        $length = (int) $length;
        $search = trim((string) $search);

        $totalResult = $this->connection->query('SELECT COUNT(*) AS total FROM nhanvienyte');
        if (!$totalResult) throw new RuntimeException('Không thể tải danh sách nhân viên y tế.');
        $total = (int) $totalResult->fetch_assoc()['total'];
        $totalResult->free();

        $where = '';
        $like = '%' . $search . '%';
        if ($search !== '') {
            $where = ' WHERE CAST(id AS CHAR) LIKE ? OR ho_ten LIKE ? OR macchn LIKE ? ';
            $count = $this->connection->prepare('SELECT COUNT(*) AS total FROM nhanvienyte' . $where);
            if (!$count) throw new RuntimeException('Không thể chuẩn bị tìm kiếm nhân viên y tế.');
            $count->bind_param('sss', $like, $like, $like);
            $count->execute();
            $filtered = (int) $count->get_result()->fetch_assoc()['total'];
            $count->close();
        } else {
            $filtered = $total;
        }

        $limit = $length < 0 ? '' : ' LIMIT ' . $start . ', ' . max(1, $length);
        $statement = $this->connection->prepare(
            'SELECT id, ho_ten, macchn FROM nhanvienyte' . $where
            . ' ORDER BY stt ASC, id ASC' . $limit
        );
        if (!$statement) throw new RuntimeException('Không thể chuẩn bị dữ liệu nhân viên y tế.');
        if ($search !== '') $statement->bind_param('sss', $like, $like, $like);
        $statement->execute();
        $result = $statement->get_result();
        $items = array();
        while ($row = $result->fetch_assoc()) $items[] = $row;
        $statement->close();

        return array('data' => $items, 'total' => $total, 'filtered' => $filtered);
    }
}
