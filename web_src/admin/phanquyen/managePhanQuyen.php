<?PHP	
require_once("web_src/bean/UserPeer.php");
$username = $request->getParameter("username");

$userPeer = new UserPeer;
$user = $userPeer->getUser($username);

$quyen = explode(",", $user->get("quyen"));
//print_r($_SESSION["quyen"]);
$request->setAttribute("content","www/admin/phanquyen/viewListPhanQuyen.htm");
?>