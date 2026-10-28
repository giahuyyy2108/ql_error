<?php
class KhoaPhongCatalogPeer
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
    function getPage($search='')
    {
        $search=trim((string)$search); $where=''; $params=array();
        if ($search !== '') { $where=' WHERE khoa.id LIKE ? OR khoa.ten LIKE ? OR khoa.id_khoi LIKE ? OR khoi.ten LIKE ?'; $value='%'.$search.'%'; $params=array($value,$value,$value,$value); }
        $totalResult = $this->connection->query('SELECT COUNT(*) AS total FROM khoa');
        if (!$totalResult) {
            throw new RuntimeException('Không thể tải danh mục khoa phòng.');
        }
        $total=(int)$totalResult->fetch_assoc()['total'];
        $sql='SELECT khoa.id, khoa.ten, khoa.id_khoi, khoi.ten AS ten_khoi
              FROM khoa
              LEFT JOIN khoi ON khoi.id = khoa.id_khoi'.$where.' ORDER BY khoa.id'; $stmt=$this->connection->prepare($sql);
        if (!$stmt) {
            throw new RuntimeException('Không thể chuẩn bị dữ liệu khoa phòng.');
        }
        if ($where !== '') $stmt->bind_param('ssss',$params[0],$params[1],$params[2],$params[3]);
        $stmt->execute(); $result=$stmt->get_result(); $data=array(); while($row=$result->fetch_assoc()) $data[]=$row;
        return array('total'=>$total,'filtered'=>count($data),'data'=>$data);
    }
}
