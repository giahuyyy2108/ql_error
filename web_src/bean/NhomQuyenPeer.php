<?PHP
require_once ("web_src/bean/NhomQuyen.php");
require_once ("web_src/bean/ChucNangPeer.php");

class NhomQuyenPeer
{
	var $dbsql;

	function __construct()
	{
		$this->dbsql = new db_mysql;
		$this->dbsql->connect();
	}

	function setNhomQuyen($result)
	{
		$nhomquyen = new NhomQuyen;

		$nhomquyen->set("maNQ", $result["maNQ"]);
		$nhomquyen->set("tenNQ", $result["tenNQ"]);
		$nhomquyen->set("quyen", $result["quyen"]);

		return $nhomquyen;
	}

	function setLog($_nhomquyen, $chucnang = "")
	{
		$log = new Log;
		if ($chucnang == "") {
			if ($_nhomquyen->get("maNQ") == "" || $_nhomquyen->get("maNQ") == 0)
				$chucnang = "Thêm nhóm quyền";
			else
				$chucnang = "Sửa nhóm quyền";
		}
		$noidung = "";
		$noidungcu = "";
		if ($_nhomquyen->get("maNQ") == "" || $_nhomquyen->get("maNQ") == 0) {
			$noidung = "Tên nhóm quyền: " . $_nhomquyen->get("tenNQ");

		} else {
			$quyen = $_nhomquyen->get("quyen");
			$quyenFormatted = wordwrap($quyen, 50, "\n", true);
			$noidung = "Tên nhóm quyền:  " . $_nhomquyen->get("tenNQ")
				. "; Quyền: " . $quyenFormatted;
			$nhomquyen = $this->getNQID($_nhomquyen->get("maNQ"));
			$quyenCu = $nhomquyen->get("quyen");
			$quyenCuFormatted = wordwrap($quyenCu, 50, "\n", true);
			$noidungcu = "Tên nhóm quyền: " . $nhomquyen->get("tenNQ")
				. "; Quyền: " . $quyenCuFormatted;
		}

		$log->set("chucnang", $chucnang);
		$log->set("noidung", $noidung);
		$log->set("noidungcu", $noidungcu);

		$logPeer = new LogPeer;
		$logPeer->ghiLog($log);
	}

	function getNQID($nqID)
	{
		// tao cau truy van		

		$sql_select = "SELECT * FROM nhomquyen WHERE maNQ='" . $nqID . "'";

		$this->dbsql->query($sql_select);

		if ($this->dbsql->num_rows() > 0) {
			$result = $this->dbsql->fetch_array();
			return $this->setNhomQuyen($result);
		}
		return false;
	}


	function save($_nhomquyen)
	{
		if ($_nhomquyen->get("maNQ") == 0 || $_nhomquyen->get("maNQ") == "") {
			$sql = "INSERT INTO `nhomquyen` (`maNQ` , `tenNQ`) 
					VALUES ('" . $_nhomquyen->get("maNQ") . "' ,'" . $_nhomquyen->get("tenNQ") . "')";
		} else {
			$sql = "UPDATE `nhomquyen` SET `maNQ` = '" . $_nhomquyen->get("maNQ") . "',
											`tenNQ` = '" . $_nhomquyen->get("tenNQ") . "'
						WHERE `maNQ` = '" . $_nhomquyen->get("maNQ") . "'";
		}

		$this->setLog($_nhomquyen);

		$this->dbsql->query($sql);

		return ($this->dbsql->insert_id() == 0) ? $_nhomquyen->get("maNQ") : $this->dbsql->insert_id();
	}

	function update($_nhomquyen)
	{
		$sql = "UPDATE `nhomquyen` SET `quyen` = '" . $_nhomquyen->get("quyen") . "'
				WHERE `maNQ` = '" . $_nhomquyen->get("maNQ") . "'";

		$this->setLog($_nhomquyen, "Đổi quyền");
		$this->dbsql->query($sql);

		return ($this->dbsql->insert_id() == 0) ? $_nhomquyen->get("maNQ") : $this->dbsql->insert_id();
	}

	// hiển thị danh sách từ form tìm kiếm
	function getListNQ($tenNQ = "")
	{
		$sSQL = " SELECT * FROM nhomquyen ";//WHERE loaikhachhangname LIKE '%vietkhoi%'";		

		if ($tenNQ != "") {
			$sSQL = $sSQL . " WHERE ";
			$sSQL .= " UPPER(`tenNQ`) LIKE UPPER('%" . $tenNQ . "%') ";
		}

		$sSQL .= " ORDER BY `tenNQ` ASC";

		$result = $this->dbsql->query($sSQL);
		$arrList = [];
		$i = 0;
		while ($row = $this->dbsql->fetch_Array($result)) {
			$arrList[$i] = $this->setNhomQuyen($row);
			$i++;
		}
		return $arrList;
	}

	function delete($maNQ)
	{
		$nhomquyen = new NhomQuyen;
		$nhomquyen->set("maNQ", $maNQ);
		$this->setLog($nhomquyen, "Xóa nhóm quyền");

		$sSQL = "DELETE FROM nhomquyen WHERE maNQ='" . $maNQ . "'";
		$this->dbsql->query($sSQL);
	}
}
?>