<?PHP
require_once ("web_src/bean/User.php");
require_once ("web_src/bean/NhomQuyenPeer.php");

class UserPeer
{
	var $dbsql;
	var $nhomquyenpeer;

	function __construct()
	{
		$this->dbsql = new db_mysql;
		$this->nhomquyenpeer = new NhomQuyenPeer;
		$this->dbsql->connect();
	}

	function setUser($result, $pass = 0)
	{
		$user = new User;

		$user->setUser("id", $result["id"]);
		$user->setUser("hoTen", $result["hoTen"]);
		$user->setUser("username", $result["username"]);
		if ($pass == 0) {
			$user->setUser("password", "");
		}
		if ($pass == 1) {
			$user->setUser("password", $result["password"]);
		}
		$user->setUser("diaChi", $result["diaChi"]);
		$user->setUser("email", $result["email"]);
		$user->setUser("dienThoai", $result["dienThoai"]);
		$user->setUser("adminType", $result["adminType"]);
		$user->setUser("quyen", $result["quyen"]);
		$user->setUser("maNQ", $result["maNQ"]);
		$user->setUser("nd_block", $result["nd_block"]);
		//$user->setUser("changeQuyen",$result["changeQuyen"]);			

		return $user;
	}

	function setLog($_User, $chucnang = "")
	{
		$log = new Log;

		if ($chucnang == "") {
			if ($_User->getUser("id") == "" || $_User->getUser("id") == 0)
				$chucnang = "Thêm User";
			else
				$chucnang = "Sửa User";
		}
		$noidung = "";
		$noidungcu = "";
		$nhomquyen = $this->nhomquyenpeer->getNQID($_User->getUser("maNQ"));
		if (empty($nhomquyen)) {
			$tenNQ = "";
		} else {
			$tenNQ = $nhomquyen->get("tenNQ");
		}
		if ($_User->getUser("id") == "" || $_User->getUser("id") == 0) {
			$noidung = "Username: " . $_User->getUser("username")
				. "; Họ và tên: " . $_User->getUser("hoTen")
				. "; Tên nhóm quyền: " . $tenNQ;
		} else {
			if ($chucnang === "Xóa user") {
				$noidung = "Username: " . $_User->getUser("username")
					. "; Họ và tên: " . $_User->getUser("hoTen")
					. "; Tên nhóm quyền: " . $tenNQ;
				$user = $this->getUserID($_User->getUser("id"));
				$nhomQuyenCu = $this->nhomquyenpeer->getNQID($user->getUser("maNQ"));
				$noidungcu = "Username: " . $user->getUser("username")
					. "; Họ và tên: " . $user->getUser("hoTen")
					. "; Tên nhóm quyền: " . $nhomQuyenCu->get("tenNQ");
			} else {
				$noidung = "Username: " . $_User->getUser("username")
					. "; Họ và tên: " . $_User->getUser("hoTen")
					. "; Tên nhóm quyền: " . $tenNQ
					. "; Người dùng khóa: " . ($_User->getUser("nd_block") == 1 ? "Đã xóa" : "Chưa khóa");
				$user = $this->getUserID($_User->getUser("id"));
				$nhomQuyenCu = $this->nhomquyenpeer->getNQID($user->getUser("maNQ"));
				$noidungcu = "Username: " . $user->getUser("username")
					. "; Họ và tên: " . $user->getUser("hoTen")
					. "; Tên nhóm quyền: " . $nhomQuyenCu->get("tenNQ")
					. "; Người dùng khóa: " . ($user->getUser("nd_block") == 1 ? "Đã xóa" : "Chưa khóa");
			}
		}

		$log->set("chucnang", $chucnang);
		$log->set("noidung", $noidung);
		$log->set("noidungcu", $noidungcu);

		$logPeer = new LogPeer;
		$logPeer->ghiLog($log);
	}

	function getUser($userID)
	{
		// tao cau truy van
		//$user = new User;

		$sql_select = "SELECT * FROM user WHERE username='" . $userID . "'";

		$this->dbsql->query($sql_select);
		// print_r($this->dbsql);
		if ($this->dbsql->num_rows() > 0) {
			$result = $this->dbsql->fetch_array();
			return $this->setUser($result, 1);
		}
		return false;
	}

	function getUsers($searchString, $arrUser)
	{
		$sSQL = " SELECT * FROM user ";
		$sSQL .= " WHERE (UPPER(`username`) LIKE UPPER('%" . $searchString . "%') OR UPPER(`hoTen`) LIKE UPPER('%" . $searchString . "%'))";
		if ($arrUser != "")
			$sSQL .= " AND `id` not in (" . $arrUser . ")";

		$sSQL .= " AND `id` != '11'";
		$sSQL .= " ORDER BY `hoTen` ASC";

		$result = $this->dbsql->query($sSQL);
		$arrList = [];
		$i = 0;
		while ($row = $this->dbsql->fetch_Array($result)) {
			$arrList[$i] = $this->setUser($row);
			$i++;
		}
		return $arrList;
	}

	function getUserID($userID)
	{
		// tao cau truy van
		//$user = new User;

		$sql_select = "SELECT * FROM user WHERE id='" . $userID . "'";

		$this->dbsql->query($sql_select);

		if ($this->dbsql->num_rows() > 0) {
			$result = $this->dbsql->fetch_array();
			return $this->setUser($result);
		}
		return false;
	}

	function getUserLogin($username, $pass)
	{
		$sql_select = "SELECT * FROM user WHERE username='" . $username . "' AND nd_block = '0'";

		$this->dbsql->query($sql_select);
		if ($this->dbsql->num_rows() > 0) {
			$result = $this->dbsql->fetch_array();
			if (md5($pass) == $result["password"]) {
				return $this->setUser($result);
			}
		}

		return false;
	}

	function checkUsername($id, $username)
	{
		$user = $this->getUser($username);
		if ($user && $user->get("id") != $id) {
			return false;
		}
		return true;
	}

	function getIP()
	{
		$ip = $_SERVER["REMOTE_ADDR"];
		$sql_select = "SELECT COUNT(*) FROM `ip` WHERE `address` LIKE '$ip' AND `timestamp` > (now() - interval 10 minute)";

		$this->dbsql->query($sql_select);
		if ($this->dbsql->num_rows() > 0) {
			$result = $this->dbsql->fetch_array();
			return $result[0];
		}

		return 0;
	}

	function saveIP()
	{
		$ip = $_SERVER["REMOTE_ADDR"];
		$sql = "INSERT INTO `ip` (`address` ,`timestamp`)VALUES ('$ip',CURRENT_TIMESTAMP)";
		$this->dbsql->query($sql);

		return $this->dbsql->insert_id();
	}

	function save($user, $pass = 0)
	{
		if ($user->getUser("id") == "" || $user->getUser("id") == 0) {
			$sql = "INSERT INTO `user`(`username`, `password`,`hoTen`,`diaChi`,`email`,`dienThoai`,`quyen`,`maNQ`) 
					VALUES ('" . $user->getUser("username") . "','" . md5($user->getUser("password")) . "','" . $user->getUser("hoTen") . "',
					'" . $user->getUser("diaChi") . "','" . $user->getUser("email") . "','" . $user->getUser("dienThoai") . "','" . $user->getUser("quyen") . "','" . $user->getUser("maNQ") . "')";
		} else {
			if ($pass == 0) {
				if ($user->getUser("password") == "") { // sua user khong doi pass
					$sql = "UPDATE `user` SET 
								  `diaChi`='" . $user->getUser("diaChi") . "',
								  `hoTen` ='" . $user->getUser("hoTen") . "',
								  `email` ='" . $user->getUser("email") . "',
								  `dienThoai`='" . $user->getUser("dienThoai") . "',
								  `quyen` = '" . $user->getUser("quyen") . "',
								  `maNQ` = '" . $user->getUser("maNQ") . "'
							WHERE `id` =  '" . $user->getUser("id") . "' ";
				} else { // sua user co doi pass
					$sql = "UPDATE `user` SET 
								  `password` = '" . md5($user->getUser("password")) . "',
								  `diaChi` = '" . $user->getUser("diaChi") . "',
								  `hoTen` = '" . $user->getUser("hoTen") . "',
								  `email` = '" . $user->getUser("email") . "',
								  `dienThoai` = '" . $user->getUser("dienThoai") . "',
								  `quyen` = '" . $user->getUser("quyen") . "',
								  `maNQ` = '" . $user->getUser("maNQ") . "'
							WHERE `id` =  '" . $user->getUser("id") . "' ";
				}
			} else { // doi pass
				$sql = "UPDATE `user` SET `password` = '" . md5($user->getUser("password")) . "'	WHERE `id` =  '" . $user->getUser("id") . "' ";
			}
		}
		//echo ($sql);
		$this->setLog($user);

		if ($this->checkUsername($user->getUser("id"), $user->getUser("username"))) {
			$this->dbsql->query($sql);
			return ($this->dbsql->insert_id() == 0) ? $user->getUser("id") : $this->dbsql->insert_id();
		}

		return false;
	}

	function updateLock($userId, $nd_block = 0)
	{
		$sql = "UPDATE `user` SET `nd_block` = '" . $nd_block . "' WHERE `id` =  '" . $userId . "' ";
		$user = $this->getUserID($userId);
		$user->set('nd_block', $nd_block);
		$this->setLog($user, "Khóa người dùng");
		$this->dbsql->query($sql);
	}

	function setToken($userId, $token)
	{
		$sql = "UPDATE `user` SET `token` = '" . $token . "' WHERE `id` =  '" . $userId . "' ";
		$this->dbsql->query($sql);
	}

	function getToken($userId)
	{
		$sql = "SELECT `token` FROM user WHERE `id` =  '" . $userId . "' ";
		$this->dbsql->query($sql);

		if ($this->dbsql->num_rows() > 0) {
			$result = $this->dbsql->fetch_array();
			return $result['token'];
		}
		return "";
	}

	function updateQuyen($user)
	{
		$sql = "UPDATE `user` SET `quyen` = '" . $user->getUser("quyen") . "' WHERE `id` =  '" . $user->getUser("id") . "' ";

		$this->dbsql->query($sql);
	}

	function getListUser()
	{
		$sSQL = "SELECT * FROM `user` LEFT JOIN `nhomquyen` ON user.maNQ = nhomquyen.maNQ";
		$sSQL .= " WHERE `id` != '11'";

		if ($_SESSION["AdminType"] != 1) {
			$sSQL .= " AND `adminType` = '0'";
		}
		$result = $this->dbsql->query($sSQL);

		$arrList = [];
		$i = 0;
		while ($row = $this->dbsql->fetch_Array($result)) {
			$arrList[$i] = $this->setUser($row);
			$i++;
		}
		return $arrList;
	}

	function getListUserActive()
	{
		$sSQL = " SELECT * FROM user ";
		$sSQL .= " WHERE `id` != '11' AND `nd_block` = '0'";

		$result = $this->dbsql->query($sSQL);
		$arrList = [];
		$i = 0;
		while ($row = $this->dbsql->fetch_Array($result)) {
			$arrList[$i] = $this->setUser($row);
			$i++;
		}
		return $arrList;
	}

	function deleteUser($maUser)
	{
		$user = new User;
		$user->set("id", $maUser);
		$this->setLog($user, "Xóa user");

		$sSQL = "DELETE FROM user WHERE id='" . $maUser . "'";

		$this->dbsql->query($sSQL);
	}
}
?>