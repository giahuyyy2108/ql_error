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
		global $connect;
		$nqID = (int) $nqID;
		$statement = $connect->prepare("SELECT * FROM nhomquyen WHERE maNQ = ? LIMIT 1");
		$statement->bind_param('i', $nqID);
		$statement->execute();
		$queryResult = $statement->get_result();
		if ($queryResult && $queryResult->num_rows > 0) {
			$result = $queryResult->fetch_array(MYSQLI_BOTH);
			$statement->close();
			return $this->setNhomQuyen($result);
		}
		$statement->close();
		return false;
	}

	function save($_nhomquyen)
	{
		global $connect;
		$maNQ = (int) $_nhomquyen->get("maNQ");
		$tenNQ = $_nhomquyen->get("tenNQ");

		if ($_nhomquyen->get("maNQ") == 0 || $_nhomquyen->get("maNQ") == "") {
			$statement = $connect->prepare("INSERT INTO `nhomquyen` (`maNQ`, `tenNQ`) VALUES (?, ?)");
			$statement->bind_param('is', $maNQ, $tenNQ);
		} else {
			$statement = $connect->prepare("UPDATE `nhomquyen` SET `tenNQ` = ? WHERE `maNQ` = ?");
			$statement->bind_param('si', $tenNQ, $maNQ);
		}

		$this->setLog($_nhomquyen);
		$statement->execute();
		$insertId = $statement->insert_id;
		$statement->close();
		return ($insertId == 0) ? $maNQ : $insertId;
	}

	function update($_nhomquyen)
	{
		global $connect;
		$maNQ = (int) $_nhomquyen->get("maNQ");
		$quyen = $_nhomquyen->get("quyen");

		$this->setLog($_nhomquyen, "Đổi quyền");
		$statement = $connect->prepare("UPDATE `nhomquyen` SET `quyen` = ? WHERE `maNQ` = ?");
		$statement->bind_param('si', $quyen, $maNQ);
		$statement->execute();
		$statement->close();
		return $maNQ;
	}

	// hiển thị danh sách từ form tìm kiếm
	function getListNQ($tenNQ = "")
	{
		global $connect;
		if ($tenNQ != "") {
			$pattern = '%' . $tenNQ . '%';
			$statement = $connect->prepare(
				"SELECT * FROM nhomquyen WHERE UPPER(`tenNQ`) LIKE UPPER(?) ORDER BY `tenNQ` ASC"
			);
			$statement->bind_param('s', $pattern);
		} else {
			$statement = $connect->prepare("SELECT * FROM nhomquyen ORDER BY `tenNQ` ASC");
		}

		$statement->execute();
		$result = $statement->get_result();
		$arrList = [];
		$i = 0;
		while ($row = $result->fetch_array(MYSQLI_BOTH)) {
			$arrList[$i] = $this->setNhomQuyen($row);
			$i++;
		}
		$statement->close();
		return $arrList;
	}

	function delete($maNQ)
	{
		global $connect;
		$maNQ = (int) $maNQ;
		$nhomquyen = new NhomQuyen;
		$nhomquyen->set("maNQ", $maNQ);
		$this->setLog($nhomquyen, "Xóa nhóm quyền");

		$statement = $connect->prepare("DELETE FROM nhomquyen WHERE maNQ = ?");
		$statement->bind_param('i', $maNQ);
		$statement->execute();
		$statement->close();
	}
}
?>