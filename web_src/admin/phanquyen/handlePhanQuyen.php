<?PHP	
require_once("web_src/bean/UserPeer.php");
$username = $request->getParameter("username");
$quyen = "";

$userPeer = new UserPeer;
// lay du lieu phan quyen
$checkbox1 = $_POST['checkbox1'];
$checkbox2 = $_POST['checkbox2'];
$checkbox3 = $_POST['checkbox3'];
$checkbox4 = $_POST['checkbox4'];
$checkbox5 = $_POST['checkbox5'];
$checkbox6 = $_POST['checkbox6'];
$checkbox7 = $_POST['checkbox7'];
$checkbox8 = $_POST['checkbox8'];
$checkbox9 = $_POST['checkbox9'];
$checkbox10 = $_POST['checkbox10'];

//hethong
$checkbox50 = $_POST['checkbox50'];
$checkbox51 = $_POST['checkbox51'];
$checkbox52 = $_POST['checkbox52'];

$phay = "";

// danh muc
if($checkbox1!="" || $checkbox2!=""|| $checkbox3!=""|| $checkbox4!=""){
	$quyen = $quyen .$phay."danhmuc";
	$phay = ",";
}
if($checkbox1!=""){
	$quyen = $quyen . $phay . implode(",", $checkbox1);
	$phay = ",";
}
if($checkbox2!=""){
	$quyen = $quyen . $phay . implode(",", $checkbox2);
	$phay = ",";
}
if($checkbox3!=""){
	$quyen = $quyen . $phay . implode(",", $checkbox3);
	$phay = ",";
}
if($checkbox4!=""){
	$quyen = $quyen . $phay . implode(",", $checkbox4);
	$phay = ",";
}
// Nhan tin bai
if($checkbox5!=""){
	$quyen = $quyen .$phay."nhantinbai";
	$phay = ",";
}
if($checkbox5!=""){
	$quyen = $quyen . $phay . implode(",", $checkbox5);
	$phay = ",";
}
// bien tap tin bai
if($checkbox6!=""){
	$quyen = $quyen .$phay."bientap";
	$phay = ",";
}
if($checkbox6!=""){
	$quyen = $quyen . $phay . implode(",", $checkbox6);
	$phay = ",";
}
// Duyet tin bai
if($checkbox7!=""){
	$quyen = $quyen .$phay."duyet";
	$phay = ",";
}
if($checkbox7!=""){
	$quyen = $quyen . $phay . implode(",", $checkbox7);
	$phay = ",";
}
// Dang tin bai
if($checkbox8!=""){
	$quyen = $quyen .$phay."dang";
	$phay = ",";
}
if($checkbox8!=""){
	$quyen = $quyen . $phay . implode(",", $checkbox8);
	$phay = ",";
}
// tim kiem tin bai
if($checkbox9!=""){
	$quyen = $quyen .$phay."timkiem";
	$phay = ",";
}
if($checkbox9!=""){
	$quyen = $quyen . $phay . implode(",", $checkbox9);
	$phay = ",";
}
// thong ke tin bai
if($checkbox10!=""){
	$quyen = $quyen .$phay."thongke";
	$phay = ",";
}
if($checkbox10!=""){
	$quyen = $quyen . $phay . implode(",", $checkbox10);
	$phay = ",";
}

// hethong
if($checkbox50!="" || $checkbox51!=""||$checkbox52!=""){
	$quyen = $quyen .$phay."hethong";
	$phay = ",";
}
if($checkbox50!=""){
	$quyen = $quyen . $phay . implode(",", $checkbox50);
	$phay = ",";
}
if($checkbox51!=""){
	$quyen = $quyen . $phay . implode(",", $checkbox51);
	$phay = ",";
}
if($checkbox52!=""){
	$quyen = $quyen . $phay . implode(",", $checkbox52);
	$phay = ",";
}

$user = $userPeer->getUser($username);
$user->set("quyen",$quyen);
$user->set("password","");

$userPeer->save($user);

require_once("web_src/admin/phanquyen/managePhanQuyen.php");
?>