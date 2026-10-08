<?php

class Icd10Peer
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

    public function getPage($start, $length, $search)
    {
        $start = max(0, (int) $start);
        $length = (int) $length;
        $search = trim((string) $search);

        $total = (int) $this->connection->query('SELECT COUNT(*) AS total FROM icd10')->fetch_assoc()['total'];
        $where = '';
        $like = '%' . $search . '%';
        if ($search !== '') {
            $where = ' WHERE code LIKE ? OR display_vi LIKE ? OR display_en LIKE ? ';
            $countStatement = $this->connection->prepare('SELECT COUNT(*) AS total FROM icd10' . $where);
            $countStatement->bind_param('sss', $like, $like, $like);
            $countStatement->execute();
            $filtered = (int) $countStatement->get_result()->fetch_assoc()['total'];
            $countStatement->close();
        } else {
            $filtered = $total;
        }

        $limit = $length < 0 ? '' : ' LIMIT ' . $start . ', ' . max(1, $length);
        $sql = 'SELECT code, display_vi, display_en, level, parent_code, chapter_code,
                       chapter_id, section_id, type_code, is_leaf, is_active, coding_guidance
                FROM icd10' . $where . ' ORDER BY code ASC' . $limit;
        $statement = $this->connection->prepare($sql);
        if ($search !== '') $statement->bind_param('sss', $like, $like, $like);
        $statement->execute();
        $result = $statement->get_result();
        $items = array();
        while ($row = $result->fetch_assoc()) {
            $row['is_leaf'] = (int) $row['is_leaf'];
            $row['is_active'] = (int) $row['is_active'];
            $items[] = $row;
        }
        $statement->close();
        return array('data' => $items, 'total' => $total, 'filtered' => $filtered);
    }

    public function existsActive($code)
    {
        $statement = $this->connection->prepare('SELECT code FROM icd10 WHERE code = ? AND is_active = 1 LIMIT 1');
        $statement->bind_param('s', $code);
        $statement->execute();
        $statement->store_result();
        $exists = $statement->num_rows > 0;
        $statement->close();
        return $exists;
    }
}
