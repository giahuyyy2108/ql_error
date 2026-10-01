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
        $sql_select = "SELECT * FROM khoaphong WHERE MaKhoaPhong='" . $khoaPhongID . "'";

        $this->dbsql->query($sql_select);

        if ($this->dbsql->num_rows() > 0) {
            $result = $this->dbsql->fetch_array();
            return $this->setKhoaPhong($result);
        }
        return false;
    }

    function getListKhoaPhong($MaKhoaPhong = "")
    {
        $sSQL = "SELECT * FROM khoaphong ORDER BY MaKhoaPhong DESC";

        if (!empty($MaKhoaPhong)) {
            $sSQL .= " AND MaKhoaPhong = '" . $MaKhoaPhong . "'";
        }


        $result = $this->dbsql->query($sSQL);
        $arrList = [];
        $i = 0;
        while ($row = $this->dbsql->fetch_Array($result)) {
            $arrList[$i] = $this->setKhoaPhong($row);
            $i++;
        }
        return $arrList;

    }

    function save($_khoaphong, $pass = 0)
    {
        if ($_khoaphong->get("MaKhoaPhong") == 0 || $_khoaphong->get("MaKhoaPhong") == "") {
            $sql = "INSERT INTO `khoaphong` (`TenKhoaPhong`) 
					VALUES (
                        '" . $_khoaphong->get("TenKhoaPhong") . "')";
        } else {
            $sql = "UPDATE `khoaphong` 
            SET 
            `TenKhoaPhong` = '" . $_khoaphong->get("TenKhoaPhong") . "'
			WHERE `MaKhoaPhong` = '" . $_khoaphong->get("MaKhoaPhong") . "'";
        }
        $this->setlog($_khoaphong);
        $this->dbsql->query($sql);
        return ($this->dbsql->insert_id() == 0) ? $_khoaphong->get("MaKhoaPhong") : $this->dbsql->insert_id();
    }

    function deleteKhoaPhong($MaKhoaPhong)
    {
        $this->kp->set("MaKhoaPhong", $MaKhoaPhong);
        $this->setLog($this->kp, "Xóa khoa phòng");
        $sSQL = "DELETE FROM khoaphong WHERE MaKhoaPhong='" . $MaKhoaPhong . "'";
        $this->dbsql->query($sSQL);
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