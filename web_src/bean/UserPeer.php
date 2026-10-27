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
		$user->setUser("id_khoa", isset($result["id_khoa"]) ? $result["id_khoa"] : null);
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
		global $connect;
		$statement = $connect->prepare('SELECT * FROM user WHERE username = ? LIMIT 1');
		$statement->bind_param('s', $userID);
		$statement->execute();
		$queryResult = $statement->get_result();
		if ($queryResult && $queryResult->num_rows > 0) {
			$result = $queryResult->fetch_array(MYSQLI_BOTH);
			$statement->close();
			return $this->setUser($result, 1);
		}
		$statement->close();
		return false;
	}

	function getUsers($searchString, $arrUser)
	{
		global $connect;
		$excludedIds = [];
		foreach (preg_split('/\s*,\s*/', (string) $arrUser, -1, PREG_SPLIT_NO_EMPTY) as $id) {
			$id = (int) $id;
			if ($id > 0) {
				$excludedIds[] = $id;
			}
		}

		$sql = "SELECT * FROM user
			WHERE (UPPER(`username`) LIKE UPPER(?) OR UPPER(`hoTen`) LIKE UPPER(?))";
		if (!empty($excludedIds)) {
			$sql .= " AND `id` NOT IN (" . implode(',', $excludedIds) . ")";
		}
		$sql .= " AND `id` != 11 ORDER BY `hoTen` ASC";

		$searchPattern = '%' . $searchString . '%';
		$statement = $connect->prepare($sql);
		$statement->bind_param('ss', $searchPattern, $searchPattern);
		$statement->execute();
		$result = $statement->get_result();
		$arrList = [];
		$i = 0;
		while ($row = $result->fetch_array(MYSQLI_BOTH)) {
			$arrList[$i] = $this->setUser($row);
			$i++;
		}
		$statement->close();
		return $arrList;
	}

	function getUserID($userID)
	{
		global $connect;
		$userID = (int) $userID;
		$statement = $connect->prepare('SELECT * FROM user WHERE id = ? LIMIT 1');
		$statement->bind_param('i', $userID);
		$statement->execute();
		$queryResult = $statement->get_result();
		if ($queryResult && $queryResult->num_rows > 0) {
			$result = $queryResult->fetch_array(MYSQLI_BOTH);
			$statement->close();
			return $this->setUser($result);
		}
		$statement->close();
		return false;
	}

	function getUserLogin($username, $pass)
	{
		global $connect;
		$statement = $connect->prepare('SELECT * FROM user WHERE username = ? AND nd_block = 0 LIMIT 1');
		$statement->bind_param('s', $username);
		$statement->execute();
		$queryResult = $statement->get_result();
		if ($queryResult && $queryResult->num_rows > 0) {
			$result = $queryResult->fetch_array(MYSQLI_BOTH);
			$statement->close();
			if (md5($pass) == $result["password"]) {
				return $this->setUser($result);
			}
			return false;
		}
		$statement->close();
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
		global $connect;
		$ip = $_SERVER["REMOTE_ADDR"];
		$statement = $connect->prepare(
			"SELECT COUNT(*) FROM `ip` WHERE `address` = ? AND `timestamp` > (NOW() - INTERVAL 10 MINUTE)"
		);
		$statement->bind_param('s', $ip);
		$statement->execute();
		$statement->bind_result($count);
		$statement->fetch();
		$statement->close();
		return (int) $count;
	}

	function saveIP()
	{
		global $connect;
		$ip = $_SERVER["REMOTE_ADDR"];
		$statement = $connect->prepare("INSERT INTO `ip` (`address`, `timestamp`) VALUES (?, CURRENT_TIMESTAMP)");
		$statement->bind_param('s', $ip);
		$statement->execute();
		$id = $statement->insert_id;
		$statement->close();
		return $id;
	}

	function save($user, $pass = 0)
	{
		global $connect;
		$this->setLog($user);

		if (!$this->checkUsername($user->getUser("id"), $user->getUser("username"))) {
			return false;
		}

		if ($user->getUser("id") == "" || $user->getUser("id") == 0) {
			$statement = $connect->prepare(
				"INSERT INTO `user`
				(`username`, `password`, `hoTen`, `diaChi`, `email`, `dienThoai`, `quyen`, `maNQ`, `id_khoa`)
				VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
			);
			$username = $user->getUser("username");
			$passwordHash = md5($user->getUser("password"));
			$hoTen = $user->getUser("hoTen");
			$diaChi = $user->getUser("diaChi");
			$email = $user->getUser("email");
			$dienThoai = $user->getUser("dienThoai");
			$quyen = $user->getUser("quyen");
			$maNQ = $user->getUser("maNQ");
			$idKhoa = $user->getUser("id_khoa");
			$statement->bind_param(
				'sssssssss',
				$username,
				$passwordHash,
				$hoTen,
				$diaChi,
				$email,
				$dienThoai,
				$quyen,
				$maNQ,
				$idKhoa
			);
		} else {
			$userId = (int) $user->getUser("id");
			if ($pass == 0) {
				$diaChi = $user->getUser("diaChi");
				$hoTen = $user->getUser("hoTen");
				$email = $user->getUser("email");
				$dienThoai = $user->getUser("dienThoai");
				$quyen = $user->getUser("quyen");
				$maNQ = $user->getUser("maNQ");
				$idKhoa = $user->getUser("id_khoa");

				if ($user->getUser("password") == "") {
					$statement = $connect->prepare(
						"UPDATE `user` SET
							`diaChi` = ?, `hoTen` = ?, `email` = ?, `dienThoai` = ?,
							`quyen` = ?, `maNQ` = ?, `id_khoa` = ?
						WHERE `id` = ?"
					);
					$statement->bind_param(
						'sssssssi',
						$diaChi,
						$hoTen,
						$email,
						$dienThoai,
						$quyen,
						$maNQ,
						$idKhoa,
						$userId
					);
				} else {
					$passwordHash = md5($user->getUser("password"));
					$statement = $connect->prepare(
						"UPDATE `user` SET
							`password` = ?, `diaChi` = ?, `hoTen` = ?, `email` = ?,
							`dienThoai` = ?, `quyen` = ?, `maNQ` = ?, `id_khoa` = ?
						WHERE `id` = ?"
					);
					$statement->bind_param(
						'ssssssssi',
						$passwordHash,
						$diaChi,
						$hoTen,
						$email,
						$dienThoai,
						$quyen,
						$maNQ,
						$idKhoa,
						$userId
					);
				}
			} else {
				$passwordHash = md5($user->getUser("password"));
				$statement = $connect->prepare("UPDATE `user` SET `password` = ? WHERE `id` = ?");
				$statement->bind_param('si', $passwordHash, $userId);
			}
		}

		$statement->execute();
		$insertId = $statement->insert_id;
		$statement->close();
		return ($insertId == 0) ? $user->getUser("id") : $insertId;
	}

	function updateLock($userId, $nd_block = 0)
	{
		global $connect;
		$userId = (int) $userId;
		$nd_block = (int) $nd_block;
		$user = $this->getUserID($userId);
		$user->set('nd_block', $nd_block);
		$this->setLog($user, "Khóa người dùng");
		$statement = $connect->prepare("UPDATE `user` SET `nd_block` = ? WHERE `id` = ?");
		$statement->bind_param('ii', $nd_block, $userId);
		$statement->execute();
		$statement->close();
	}

	function setToken($userId, $token)
	{
		global $connect;
		$userId = (int) $userId;
		$statement = $connect->prepare("UPDATE `user` SET `token` = ? WHERE `id` = ?");
		$statement->bind_param('si', $token, $userId);
		$statement->execute();
		$statement->close();
	}

	function getToken($userId)
	{
		global $connect;
		$userId = (int) $userId;
		$statement = $connect->prepare("SELECT `token` FROM user WHERE `id` = ? LIMIT 1");
		$statement->bind_param('i', $userId);
		$statement->execute();
		$result = $statement->get_result();
		if ($result && $result->num_rows > 0) {
			$row = $result->fetch_assoc();
			$statement->close();
			return $row['token'];
		}
		$statement->close();
		return "";
	}

	function updateQuyen($user)
	{
		global $connect;
		$userId = (int) $user->getUser("id");
		$quyen = $user->getUser("quyen");
		$statement = $connect->prepare("UPDATE `user` SET `quyen` = ? WHERE `id` = ?");
		$statement->bind_param('si', $quyen, $userId);
		$statement->execute();
		$statement->close();
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
		global $connect;
		$maUser = (int) $maUser;
		$user = new User;
		$user->set("id", $maUser);
		$this->setLog($user, "Xóa user");
		$statement = $connect->prepare("DELETE FROM user WHERE id = ?");
		$statement->bind_param('i', $maUser);
		$statement->execute();
		$statement->close();
	}
}
?>
