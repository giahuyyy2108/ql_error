<?PHP
require_once ("web_src/bean/ChucNang.php");

class ChucNangPeer
{
	var $dbsql;

	function __construct()
	{
		$this->dbsql = new db_mysql;
		$this->dbsql->connect();
		// $this->dbsql->selectdb();
	}

	function setChucNang($result)
	{
		$chucnang = new ChucNang;

		//$chucnang->set("id",$result["id"]);

		$chucnang->set("maChucNang", $result["maChucNang"]);
		$chucnang->set("tenChucNang", $result["tenChucNang"]);
		$chucnang->set("parent", $result["parent"]);
		$chucnang->set("url", $result["url"]);
		$chucnang->set("parentQuyen", $result["parentQuyen"]);
		$chucnang->set("tenChucNangCon", $result["tenChucNangCon"]);
		$chucnang->set("urlChucNangCon", $result["urlChucNangCon"]);
		$chucnang->set("order", $result["order"]);
		$chucnang->set("level", $result["level"]);
		$chucnang->set("logo", $result["logo"]);

		return $chucnang;
	}

	function getChucNang()
	{
		$sSQL = " SELECT * FROM chucnang ";
		$sSQL .= " ORDER BY `order` ASC";

		$result = $this->dbsql->query($sSQL);
		$arrList = [];
		$i = 0;
		while ($row = $this->dbsql->fetch_Array($result)) {
			$arrList[$i] = $this->setChucNang($row);
			$i++;
		}
		return $arrList;
	}
}
?>