<?php
class KhoiPeer
{
    /** @var mysqli */
    private $connection;

    function __construct()
    {
        global $connect;

        $db = new db_mysql();
        $db->connect();
        $db->selectdb();

        if (!($connect instanceof mysqli)) {
            throw new RuntimeException('Không thể kết nối cơ sở dữ liệu.');
        }
        $this->connection = $connect;
        $this->connection->set_charset('utf8mb4');
    }

    function getPage($start, $length, $search = '')
    {
        $where = '';
        $params = array();

        if (trim($search) !== '') {
            $where = ' WHERE id LIKE ? OR ten LIKE ?';
            $searchValue = '%' . trim($search) . '%';
            $params = array($searchValue, $searchValue);
        }

        $totalResult = $this->connection->query('SELECT COUNT(*) AS total FROM khoi');
        $total = (int) $totalResult->fetch_assoc()['total'];

        $sql = 'SELECT id, ten
                FROM khoi' . $where . '
                ORDER BY id';
        $statement = $this->connection->prepare($sql);

        if ($where !== '') {
            $statement->bind_param('ss', $params[0], $params[1]);
        }

        $statement->execute();
        $result = $statement->get_result();
        $data = array();

        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }

        $filtered = $total;

        if ($where !== '') {
            $count = $this->connection->prepare(
                'SELECT COUNT(*) AS total FROM khoi' . $where
            );
            $count->bind_param('ss', $params[0], $params[1]);
            $count->execute();
            $filtered = (int) $count->get_result()->fetch_assoc()['total'];
        }

        return array(
            'total' => $total,
            'filtered' => $filtered,
            'data' => $data
        );
    }
}
