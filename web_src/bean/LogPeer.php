<?
require_once ("web_src/bean/Log.php");

class LogPeer
{
	var $dbsql;

	function __construct()
	{
		$this->dbsql = new db_mysql;
		$this->dbsql->connect();
		$this->dbsql->selectdb();
	}

	function setLog($result)
	{
		$log = new Log;

		$log->set("logID", $result["logID"]);
		$log->set("ngay", $result["ngay"]);
		$log->set("ten", $result["ten"]);
		$log->set("chucnang", $result["chucnang"]);
		$log->set("noidung", $result["noidung"]);
		$log->set("noidungcu", $result["noidungcu"]);

		return $log;
	}

	function save($_log)
	{
		global $connect;
		$statement = $connect->prepare(
			"INSERT INTO `log` (`ngay`,`ten`,`chucnang`,`noidung`,`noidungcu`) VALUES (?, ?, ?, ?, ?)"
		);
		if (!$statement) throw new RuntimeException('Không thể chuẩn bị dữ liệu log.');
		$ngay = $_log->get("ngay");
		$ten = $_log->get("ten");
		$chucnang = $_log->get("chucnang");
		$noidung = $_log->get("noidung");
		$noidungcu = $_log->get("noidungcu");
		$statement->bind_param('sssss', $ngay, $ten, $chucnang, $noidung, $noidungcu);
		$statement->execute();
		$id = $statement->insert_id;
		$statement->close();
		return $id == 0 ? $_log->get("logID") : $id;
	}

	function ghiLog($_log)
	{

		//date_default_timezone_set('Asia/Saigon');
		$ngay = date("d/m/Y h:i:s A");
		$ten = $_SESSION["sUserName"];

		$_log->set("ngay", $ngay);
		$_log->set("ten", $ten);

		return $this->save($_log);
	}

	function ghiLogObj($obj, $obj1, $chucnang)
	{
		$log = new Log;
		$noidung = "";
		$noidungcu = "";
		$daucach = "";
		if ($obj != false) {
			foreach ($obj as $key => $value) {
				$noidung .= $daucach . $key . " = " . $value;
				$daucach = " | ";
				//echo("key=".$key ." value=". $value);
			}
		}
		$daucach = "";
		if ($obj1 != "" && $obj1 != false) {
			foreach ($obj1 as $key => $value) {
				$noidungcu .= $daucach . $key . " = " . $value;
				$daucach = " | ";
				//echo("key=".$key ." value=". $value);
			}
		}
		$ngay = date("d/m/Y h:i:s A");
		$ten = $_SESSION["sUserName"];

		$log->set("ngay", $ngay);
		$log->set("ten", $ten);
		$log->set("chucnang", $chucnang);
		$log->set("noidung", $noidung);
		$log->set("noidungcu", $noidungcu);

		return $this->save($log);
	}

	function ghiLogArrObj($obj, $obj1, $chucnang)
	{
		$log = new Log;
		$noidung = "";
		$noidungcu = "";
		$daucach = "";
		if ($obj != false) {
			foreach ($obj as $key => $value) {
				$noidung .= ($key + 1) . " : ";
				foreach ($value as $key1 => $value1) {
					$noidung .= $daucach . $key1 . " = " . $value1;
					$daucach = " | ";
				}
				$daucach = "";
				$noidung .= "</br>";
				//echo("key=".$key ." value=". $value);
			}
		}
		$daucach = "";
		if ($obj1 != "" && $obj != false) {
			foreach ($obj1 as $key => $value) {
				$noidungcu .= ($key + 1) . " : ";
				foreach ($value as $key1 => $value1) {
					$noidungcu .= $daucach . $key1 . " = " . $value1;
					$daucach = " | ";
				}
				$daucach = "";
				$noidungcu .= "</br>";
				//echo("key=".$key ." value=". $value);
			}
		}
		$ngay = date("d/m/Y h:i:s A");
		$ten = $_SESSION["sUserName"];

		$log->set("ngay", $ngay);
		$log->set("ten", $ten);
		$log->set("chucnang", $chucnang);
		$log->set("noidung", $noidung);
		$log->set("noidungcu", $noidungcu);

		return $this->save($log);
	}

	function deleteLog($logID)
	{
		global $connect;
		$logID = (int) $logID;
		$statement = $connect->prepare("DELETE FROM log WHERE logID = ?");
		$statement->bind_param('i', $logID);
		$statement->execute();
		$statement->close();
	}

	function getListLog($_username, $_chucnang, $to = 0, $from = 0)
	{
		global $connect;
		$to = max(0, (int) $to);
		$from = max(0, (int) $from);
		$sql = "SELECT * FROM log
			WHERE UPPER(`ten`) LIKE UPPER(?) AND UPPER(chucnang) LIKE UPPER(?)
			ORDER BY `logID` DESC";
		if ($from > 0) {
			$sql .= " LIMIT " . $to . ", " . $from;
		}
		$usernamePattern = '%' . $_username . '%';
		$chucnangPattern = '%' . $_chucnang . '%';
		$statement = $connect->prepare($sql);
		$statement->bind_param('ss', $usernamePattern, $chucnangPattern);
		$statement->execute();
		$result = $statement->get_result();
		$arrList = [];
		$i = 0;
		while ($row = $result->fetch_array(MYSQLI_BOTH)) {
			$arrList[$i] = $this->setLog($row);
			$i++;
		}
		$statement->close();
		return $arrList;
	}

	function getCount($username, $chucnang)
	{
		global $connect;
		$usernamePattern = '%' . $username . '%';
		$chucnangPattern = '%' . $chucnang . '%';
		$statement = $connect->prepare(
			"SELECT COUNT(*) FROM `log`
			WHERE UPPER(`ten`) LIKE UPPER(?) AND UPPER(chucnang) LIKE UPPER(?)"
		);
		$statement->bind_param('ss', $usernamePattern, $chucnangPattern);
		$statement->execute();
		$statement->bind_result($count);
		$statement->fetch();
		$statement->close();
		return (int) $count;
	}
}
?>
