<?PHP	
require_once("web_src/bean/UserPeer.php");

$UserPeer = new UserPeer();
$arrUser = $UserPeer->getListUser();
$request->setAttribute("listNguoiDung",$arrUser);

$request->setAttribute("content","www/admin/nguoidung/viewListNguoiDung.htm");
?>