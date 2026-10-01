<?PHP	
require_once("web_src/bean/hangPeer.php");

$hangPeer = new hangPeer();
$arrhang = $hangPeer->getListhang();
$request->setAttribute("listhang",$arrhang);

$request->setAttribute("content","www/admin/hang/viewListhang.htm");
?>