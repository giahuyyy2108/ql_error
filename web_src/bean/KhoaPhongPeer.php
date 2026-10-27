<?php
require_once ("web_src/bean/KhoaPhong.php");

class KhoaPhongPeer
{
    var $dbsql;
    var $kp;

    function __construct()
    {
        $this->dbsql = new db_mysql;
        $this->kp = new KhoaPhong;
        $this->dbsql->connect();
    }

    function setKhoaPhong($result)
    {
        $khoaphong = new KhoaPhong;

        $khoaphong->set("MaKhoaPhong", $result["MaKhoaPhong"]);
        $khoaphong->set("TenKhoaPhong", $result["TenKhoaPhong"]);
        return $khoaphong;
    }
    function setLog($_KhoaPhong, $chucnang = "")
    {
        $log = new Log;

        if ($chucnang == "") {
            if ($_KhoaPhong->get("MaKhoaPhong") == "" || $_KhoaPhong->get("MaKhoaPhong") == 0)
                $chucnang = "Thêm khoa phòng";
            else
                $chucnang = "Sửa khoa phòng";
        }
        $noidung = "";
        $noidungcu = "";
        if ($_KhoaPhong->get("MaKhoaPhong") == "" || $_KhoaPhong->get("MaKhoaPhong") == 0) {
            $noidung = "Tên khoa phòng: " . $_KhoaPhong->get("TenKhoaPhong");

        } else {
            $noidung = "Tên khoa phòng: " . $_KhoaPhong->get("TenKhoaPhong");
            $KhoaPhong = $this->getkhoaPhongID($_KhoaPhong->get("MaKhoaPhong"));
            $noidungcu = "Tên khoa phòng: " . $KhoaPhong->get("TenKhoaPhong");
        }

        $log->set("chucnang", $chucnang);
        $log->set("noidung", $noidung);
        $log->set("noidungcu", $noidungcu);

        $logPeer = new LogPeer;
        $logPeer->ghiLog($log);
    }
    function getKhoaPhongID($khoaPhongID)
    {
        global $connect;
        $khoaPhongID = (int) $khoaPhongID;
        $statement = $connect->prepare("SELECT * FROM khoaphong WHERE MaKhoaPhong = ? LIMIT 1");
        $statement->bind_param('i', $khoaPhongID);
        $statement->execute();
        $queryResult = $statement->get_result();
        if ($queryResult && $queryResult->num_rows > 0) {
            $result = $queryResult->fetch_array(MYSQLI_BOTH);
            $statement->close();
            return $this->setKhoaPhong($result);
        }
        $statement->close();
        return false;
    }

    function getListKhoaPhong($MaKhoaPhong = "")
    {
        global $connect;
        if (!empty($MaKhoaPhong)) {
            $MaKhoaPhong = (int) $MaKhoaPhong;
            $statement = $connect->prepare(
                "SELECT * FROM khoaphong WHERE MaKhoaPhong = ? ORDER BY MaKhoaPhong DESC"
            );
            $statement->bind_param('i', $MaKhoaPhong);
        } else {
            $statement = $connect->prepare("SELECT * FROM khoaphong ORDER BY MaKhoaPhong DESC");
        }

        $statement->execute();
        $result = $statement->get_result();
        $arrList = [];
        $i = 0;
        while ($row = $result->fetch_array(MYSQLI_BOTH)) {
            $arrList[$i] = $this->setKhoaPhong($row);
            $i++;
        }
        $statement->close();
        return $arrList;
    }

    function save($_khoaphong, $pass = 0)
    {
        global $connect;
        $tenKhoaPhong = $_khoaphong->get("TenKhoaPhong");
        if ($_khoaphong->get("MaKhoaPhong") == 0 || $_khoaphong->get("MaKhoaPhong") == "") {
            $statement = $connect->prepare("INSERT INTO `khoaphong` (`TenKhoaPhong`) VALUES (?)");
            $statement->bind_param('s', $tenKhoaPhong);
        } else {
            $maKhoaPhong = (int) $_khoaphong->get("MaKhoaPhong");
            $statement = $connect->prepare(
                "UPDATE `khoaphong` SET `TenKhoaPhong` = ? WHERE `MaKhoaPhong` = ?"
            );
            $statement->bind_param('si', $tenKhoaPhong, $maKhoaPhong);
        }

        $this->setlog($_khoaphong);
        $statement->execute();
        $insertId = $statement->insert_id;
        $statement->close();
        return ($insertId == 0) ? $_khoaphong->get("MaKhoaPhong") : $insertId;
    }

    function deleteKhoaPhong($MaKhoaPhong)
    {
        global $connect;
        $MaKhoaPhong = (int) $MaKhoaPhong;
        $this->kp->set("MaKhoaPhong", $MaKhoaPhong);
        $this->setLog($this->kp, "Xóa khoa phòng");
        $statement = $connect->prepare("DELETE FROM khoaphong WHERE MaKhoaPhong = ?");
        $statement->bind_param('i', $MaKhoaPhong);
        $statement->execute();
        $statement->close();
        return true;
    }

    function getListTenPhongKhoa()
    {
        $sSql = "SELECT TenKhoaPhong FROM khoaphong";
        $result = $this->dbsql->query($sSql);

        $tenPhongKhamList = array();
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $tenPhongKhamList[] = $row['TenKhoaPhong'];
            }
        }

        return $tenPhongKhamList;
    }


}
?>