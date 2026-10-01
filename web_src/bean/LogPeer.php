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
		$sql = "INSERT INTO `log` (`ngay`,`ten`,`chucnang`,`noidung`,`noidungcu`) 
				VALUES ('" . $_log->get("ngay") . "','" . $_log->get("ten") . "','" . $_log->get("chucnang") . "','" . $_log->get("noidung") . "',
				'" . $_log->get("noidungcu") . "')";

		$this->dbsql->query($sql);

		return ($this->dbsql->insert_id() == 0) ? $_log->get("logID") : $this->dbsql->insert_id();
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
		$sSQL = "DELETE FROM log WHERE logID='" . $logID . "'";
		$this->dbsql->query($sSQL);
	}

	function getListLog($_username, $_chucnang, $to = 0, $from = 0)
	{
		$sSQL = " SELECT * FROM log ";
		//$sSQL.= " WHERE ten LIKE '%" . $_username . "%' AND UPPER(chucnang) LIKE '%" . mb_strtoupper($_chucnang, 'UTF-8') . "%'";
		$sSQL .= " WHERE UPPER(`ten`) LIKE UPPER('%" . $_username . "%') AND UPPER(chucnang) LIKE UPPER('%" . $_chucnang . "%')";
		//$sSQL.= " AND ten != 'vietkhoi' ";	
		$sSQL .= " ORDER BY `logID` DESC";

		if ($to > -1 && $from > 0) {
			$sSQL .= " LIMIT  $to, $from";
		}

		$result = $this->dbsql->query($sSQL);
		$arrList = [];
		$i = 0;
		while ($row = $this->dbsql->fetch_Array($result)) {
			$arrList[$i] = $this->setLog($row);
			$i++;
		}
		return $arrList;
	}

	function getCount($username, $chucnang)
	{
		$sSQL = " SELECT count( * ) FROM `log`";
		//$sSQL.= " WHERE ten LIKE '%" . $username . "%' AND UPPER(chucnang) LIKE '%" . mb_strtoupper($chucnang, 'UTF-8') . "%'";
		$sSQL .= " WHERE UPPER(`ten`) LIKE UPPER('%" . $username . "%') AND UPPER(chucnang) LIKE UPPER('%" . $chucnang . "%')";
		//$sSQL.= " AND ten != 'vietkhoi' ";
		$result = $this->dbsql->query($sSQL);
		$count = 0;

		if ($row = $this->dbsql->fetch_Array($result)) {
			$count = $row[0];
		}

		return $count;
	}
}
?>