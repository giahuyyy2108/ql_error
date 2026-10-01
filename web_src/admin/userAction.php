<?PHP
require_once ("web_src/bean/UserPeer.php");
require_once ("web_src/bean/ChucNangPeer.php");
require_once ("web_src/bean/NhomQuyenPeer.php");

class userAction
{
	var $request;
	var $userPeer;
	public static $listRole = "user,saveUser,deleteUser,phanquyen,lock";
	//var $quyen = explode(",", $listRole);
	public function __construct()
	{
		$this->request = new Request;
		$this->userPeer = new UserPeer();
		$this->request->setTitle("Danh sách người dùng");
	}

	function index()
	{
		$this->request->setAttribute('script', '<script src="' . _DEFAULT_URL_ . 'js/user.js?' . _DEFAULT_VERSION_JS_CSS_ . '"></script>');
		$this->request->setAttribute('css', '<link href="' . _DEFAULT_URL_ . 'css/style.css?' . _DEFAULT_VERSION_JS_CSS_ . '" rel="stylesheet">');
		$nhomquyenPeer = new NhomQuyenPeer();
		$arrNQ = $nhomquyenPeer->getListNQ();

		$this->request->setAttribute("listNQ", $arrNQ);

		$this->request->setModel("www/admin/nguoidung/nguoiDung.htm");
		return true;
	}

	function getData()
	{

		$arrUser = $this->userPeer->getListUser();
		$data["data"] = $arrUser;

		$myJSON = json_encode($data);
		return $this->request->json_response($myJSON);
	}

	function saveUser()
	{
		$userId = ($this->request->getParameter("id") != "") ? $this->request->getParameter("id") : 0;
		$data = $this->request->getParameter("data", true);
		$arrayData = json_decode($data, true);
		$remainingData = array_slice($arrayData, 1);
		if (empty($remainingData)) {
			$message = new Message();
			$message->set("flag", false);
			$message->set("errorMessage", "Bạn chưa sửa dữ liệu gì trên dòng này. Vui lòng sửa trước khi lưu.");
			$response["message"] = $message;

			$myJSON = json_encode($response);
			return $this->request->json_response($myJSON);
		}

		if ($arrayData[0] == "" || $arrayData[1] == "" || $arrayData[2] == "" || $arrayData[3] == "") {
			$message = new Message();
			$message->set("flag", false);
			$message->set("errorMessage", "");

			$response["message"] = $message;

			$myJSON = json_encode($response);
			//echo $myJSON;
			return $this->request->json_response($myJSON);
		}
		// if (!preg_match('/^[a-zA-ZÀ-ỹĂăÂâĐđÊêÔôƠơƯư0-9\s]+$/u', $arrayData[0])) {
		// 	$message = new Message();
		// 	$message->set("flag", false);
		// 	$message->set("errorMessage", "Tên người dùng chỉ được nhập chữ và số. Vui lòng nhập lại.");
		// 	$response["message"] = $message;

		// 	$myJSON = json_encode($response);
		// 	return $this->request->json_response($myJSON);
		// }
		// if (!preg_match('/^[a-zA-ZÀ-ỹĂăÂâĐđÊêÔôƠơƯư0-9\s]+$/u', $arrayData[1])) {
		// 	$message = new Message();
		// 	$message->set("flag", false);
		// 	$message->set("errorMessage", "Username chỉ được nhập chữ và số. Vui lòng nhập lại.");
		// 	$response["message"] = $message;

		// 	$myJSON = json_encode($response);
		// 	return $this->request->json_response($myJSON);
		// }
		if (!preg_match('/^[0-9]+$/', $arrayData[3])) {
			$message = new Message();
			$message->set("flag", false);
			$message->set("errorMessage", "Nhóm quyền chỉ được nhập số. Vui lòng nhập lại.");
			$response["message"] = $message;

			$myJSON = json_encode($response);
			return $this->request->json_response($myJSON);
		}
		$User = new User;
		$nhomquyenPeer = new NhomQuyenPeer;
		$nhomquyen = new NhomQuyen;
		$id = 0;
		if ($userId == 0) { // them moi		
			$arrUS = $this->userPeer->getListUser();
			foreach ($arrUS as $item) {
				if ($item->get('username') == $arrayData[1]) {
					$message = new Message();
					$message->set("flag", false);
					$message->set("errorMessage", "Tên đăng nhập đã tồn tại. Vui lòng nhập mã khác.");
					$response["message"] = $message;
					$myJSON = json_encode($response);
					return $this->request->json_response($myJSON);
				}
			}

			$User->set("id", $userId);
			$User->set("hoTen", $arrayData[0]);
			$User->set("username", $arrayData[1]);
			$User->set("password", $arrayData[2]);
			$User->set("maNQ", $arrayData[3]);

			$nhomquyen = $nhomquyenPeer->getNQID($arrayData[3]);
			if ($nhomquyen != false)
				$User->set("quyen", $nhomquyen->get("quyen"));

			$id = $this->userPeer->save($User);
		} else { // sửa user				
			$User->set("id", $userId);
			$User->set("hoTen", $arrayData[0]);

			$User->set("password", "");
			if ($arrayData[2] != "***")
				$User->set("password", $arrayData[2]);

			$User->set("maNQ", $arrayData[3]);

			$userOld = $this->userPeer->getUserID($userId);
			$User->set("quyen", $userOld->get("quyen"));

			if ($arrayData[3] != $userOld->get("maNQ")) {
				$nhomquyen = $nhomquyenPeer->getNQID($arrayData[3]);
				if ($nhomquyen != false)
					$User->set("quyen", $nhomquyen->get("quyen"));
			}
			$id = $this->userPeer->save($User);
		}

		$message = new Message();
		$message->set("flag", true);
		$message->set("succesMessage", "Cập nhật người dùng thành công");

		$response["id"] = $id;
		$response["message"] = $message;

		$myJSON = json_encode($response);
		//echo $myJSON;
		return $this->request->json_response($myJSON);
	}

	function deleteUser()
	{
		$userId = ($this->request->getParameter("id") != "") ? $this->request->getParameter("id") : 0;
		$this->userPeer->deleteUser($userId);

		$myJSON = json_encode(true);
		return $this->request->json_response($myJSON);
	}

	function lockUser()
	{
		$userId = $this->request->getParameter("maUser");
		$lock = $this->request->getParameter("lock");

		if ($lock == '0')
			$lock = '1';
		else
			$lock = '0';

		$this->userPeer->updateLock($userId, $lock);

		$myJSON = json_encode($lock);
		return $this->request->json_response($myJSON);
	}

	function getUsers()
	{
		$searchString = $this->request->getParameter("searchString");
		$listUsers = $this->request->getParameter("listUsers", true);

		$arrUser = $this->userPeer->getUsers($searchString, $listUsers);

		$myJSON = json_encode($arrUser);

		return $this->request->json_response($myJSON);
	}

	function phanquyen()
	{
		$maUser = $this->request->getParameter("maUser");

		$chucNangPeer = new ChucNangPeer;

		$user = $this->userPeer->getUserID($maUser);

		$quyen = explode(",", $user->get("quyen"));
		$listChucNang = $chucNangPeer->getChucNang();
		//print_r($_SESSION["quyen"]);		
		include ("www/admin/phanquyen/viewListPhanQuyen.htm");

		return "";
	}

	function savePhanquyen()
	{
		$username = $this->request->getParameter("username");
		$numCheck = $this->request->getParameter("numCheck");
		$quyen = "";

		$phay = "";

		for ($i = 0; $i < $numCheck; $i++) {

			if (isset($_POST['checkbox' . $i])) {
				$quyen = $quyen . $phay . implode(",", $_POST['checkbox' . $i]);
				$phay = ",";
			}
		}

		$user = $this->userPeer->getUser($username);
		$change = 0;
		// kiem tra quyen vùa cập nhật
		$quyencu = $user->get("quyen");
		if (strlen($quyencu) != strlen($quyen))
			$change = 1;
		else {
			$listquyen = explode(",", $quyen);
			$listquyencu = explode(",", $quyencu);
			// for ($i = 0; $i < $numCheck; $i++) {
			// 	if (!in_array($listquyen[$i], $listquyencu)) {
			// 		$change = 1;
			// 		break;
			// 	}
			// }
			foreach ($listquyen as $quyenItem) {
				if (!in_array($quyenItem, $listquyencu)) {
					$change = 1;
					break;
				}
			}
		}

		$user->set("quyen", $quyen);
		$user->set("password", "");
		$user->set("changeQuyen", $change);

		$this->userPeer->updateQuyen($user);

		$myJSON = json_encode("Đã lưu");

		return $this->request->json_response($myJSON);
	}
}
?>