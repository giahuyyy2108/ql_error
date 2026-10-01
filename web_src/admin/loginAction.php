<?PHP
require_once ("web_src/bean/UserPeer.php");

class loginAction
{

	function index()
	{
		$request = new Request;
		$util = new Util;
		$_SESSION["sPassword"] = $util->createKey(9);
		//$_SESSION["sUser"] = $util->createKey(9);
		$request->setAttribute("pass", $_SESSION["sPassword"]);
		//$request->setAttribute("sUser",$_SESSION["sUser"]);
		include ("www/admin/login.htm");
		return null;
	}
	function login()
	{

		$request = new Request;
		$util = new Util;
		//$_SESSION = array();

		$username = $request->getParameter("txtUserName");
		if (!isset($_SESSION["sPassword"])) {
			return $this->index();
		}
		$pass = ($_SESSION["sPassword"] != "") ? $request->getParameter($_SESSION["sPassword"]) : "";

		$_SESSION["sPassword"] = $util->createKey(9);
		//$_SESSION["sUser"] = $util->createKey(9);
		$_SESSION["sUserName"] = "";
		$_SESSION["sUserLogin"] = false;

		$request->setAttribute("pass", $_SESSION["sPassword"]);
		//$request->setAttribute("sUser",$_SESSION["sUser"]);

		// login quá 3 lần
		//echo dirname(__FILE__);
		$userPeer = new UserPeer;
		/*$count = $userPeer->getIP();
					if($count > 3){						
						$request->setAttribute("msg","Đăng nhập quá 3 lần. Vui lòng thử lại sau 10 phút.");	
						$request->setAttribute("login-disabled","disabled");	
						$request->setAttribute("username",$username);
						include("www/admin/login.htm");
						return null;			
					}		
					*/
		if ($username != "" && $pass != "") {
			try {
				//kiem tra login
				$userPeer = new UserPeer;
				if ($user = $userPeer->getUserLogin($username, $pass)) {
					$_SESSION["sUserID"] = $user->getUser("id");
					$_SESSION["sUserName"] = $user->getUser("username");
					$_SESSION["FullName"] = $user->getUser("hoTen");
					$_SESSION["AdminType"] = $user->getUser("adminType");
					// if($user->getUser("adminType") == 1){
					// 	$_SESSION["ToTiem"] = "0 OR 1=1";
					// }
					// else{
					// 	if($user->getUser("toId") == -1){
					// 		$_SESSION["ToTiem"] = "0 OR 1=1";
					// 	}
					// 	else{
					// 		$_SESSION["ToTiem"] = $user->getUser("toId");
					// 	}
					// }

					$_SESSION["sUserLogin"] = true;
					$_SESSION['discard_after'] = time() + 10;
					$_SESSION["sToken"] = $util->createKey(9);

					$userPeer->setToken($user->getUser("id"), $_SESSION["sToken"]);

					$request->setRole($user->getUser("quyen"));

					// chuyen den trang home
					$class = _FILE_HANDLE_ . _DEFAULT_HANDLE_ . _CLASS_HANDLE_;

					include ($class . ".php");
					$class = _DEFAULT_HANDLE_ . _CLASS_HANDLE_;
					$class_handle = new $class();
					$method = "index";
					return $class_handle->$method();
				} else {
					$userPeer = new UserPeer;

					//$userPeer->saveIP();

					//$count = $userPeer->getIP();
					$count = 0;
					if ($count > 3) {
						$request->setAttribute("msg", "Đăng nhập quá 3 lần. Vui lòng thử lại sau 10 phút.");
						$request->setAttribute("login-disabled", "disabled");
					} else {
						$request->setAttribute("msg", "Đăng nhập không thành công. Vui lòng thử lại.");
					}
					$request->setAttribute("username", $username);
					include ("www/admin/login.htm");
					return null;
				}
			} catch (Throwable $e) {
				echo $e->getMessage();
			}
		}

		//$request->setAttribute("pass",$_SESSION["sPassword"]);
		include ("www/admin/login.htm");
		return null;
	}
	function logout()
	{
		$request = new Request;
		$_SESSION["sUserID"] = "";
		$_SESSION["sUserName"] = "";
		$_SESSION["FullName"] = "";
		$_SESSION["AdminType"] = 0;
		$_SESSION["sUserLogin"] = false;
		$_SESSION["sToken"] = "";
		$_SESSION['discard_after'] = time() + 10;
		//echo(123);
		$request->setRole("");
		//$request->setAttribute("pass",$_SESSION["sPassword"]);
		//include("www/admin/login.htm");
		$_SESSION = array();
		session_destroy();
		session_unset();
		session_write_close();
		setcookie(session_name(), '', 0, '/');
		setcookie("PHPSESSID", '', 0, '/');
		session_regenerate_id(true);

		header('Location: ' . _DEFAULT_URL_ . 'login/');
		return null;
	}
	function changePass()
	{
		$request = new Request;
		$userPeer = new UserPeer;

		$user = $userPeer->getUser($_SESSION["sUserName"]);

		if ($user == false) {
			return $this->index();
		} else {
			$request->setAttribute("user", $user);
			$request->setModel("www/admin/changePass.htm");
			return true;
		}
	}

	function savePass()
	{
		$request = new Request;
		$userPeer = new UserPeer;

		$oldPass = $request->getParameter("oldpass");
		$newPass = $request->getParameter("newpass");
		$verifyPass = $request->getParameter("verifypass");

		$userPeer = new UserPeer;

		$user = $userPeer->getUser($_SESSION["sUserName"]);

		if ($user == false) {
			return $this->index();
		} else {
			if ((md5($oldPass) != $user->getUser("password")) || ($newPass != $verifyPass) || ($newPass == "")) {
				$msg = "L&#7895;i khi thay &#273;&#7893;i m&#7853;t kh&#7849;u. Y&ecirc;u c&#7847;u nh&#7853;p ch&iacute;nh x&aacute;c c&aacute;c tr&#432;&#7901;ng (*)";
				$request->setAttribute("user", $user);
				$request->setAttribute("msg", $msg);
				$request->setModel("www/admin/changePass.htm");
				return true;
			} else {
				$user->setUser("password", $newPass);
				$userPeer->save($user, 1);
				$request->setModel("www/admin/changePassSuccess.htm");
				return true;
			}
		}
	}
}
?>