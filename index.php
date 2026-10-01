<?PHP
//error_reporting(0);
ob_start();
date_default_timezone_set('Asia/Saigon');
//ini_set('max_execution_time', '300');
set_time_limit(300);
include("conf/config.php");

include("web_src/common/Request.php");
include("web_src/common/mysql.php");
include("web_src/common/Util.php");
include("web_src/bean/LogPeer.php");
require_once("web_src/bean/UserPeer.php");
require_once("web_src/bean/Message.php");
require_once("web_src/bean/ChucNangPeer.php");

// khoi tao bien luu thuoc tinh
$request = new Request;
$util = new Util;

// kiem tra login
session_name("sAdminID");
session_start();
/*
$now = time();
if (isset($_SESSION['discard_after']) && $now > $_SESSION['discard_after']) {
    // this session has worn out its welcome; kill it and start a brand new one
    session_unset();
    session_destroy();
    session_start();
}
/*
if($_SESSION["sUserLogin"]){
	$userPeer = new UserPeer;
	$token = $userPeer->getToken($_SESSION["sUserID"]);
	if($_SESSION["sToken"]!=$token){
		session_unset();
		session_destroy();
		session_start();
	}
}
*/
// either new or old, it should live at most for another hour
//$_SESSION['discard_after'] = $now + 30;
//echo session_id();
//echo 1;	

if ( !isset($_SESSION["sUserName"]) ){		
	$_SESSION["sUserName"] = "";	
	$_SESSION["sUserLogin"]	 = false;		
}

// lay ma yeu cau
$handle_url = $_GET['handle_url'];

//echo 3;	
// khoi tao lop xu ly
if($handle_url == ""){	
	$handle_url = _DEFAULT_HANDLE_;
}

$listHandle = explode("/",$handle_url);

if($listHandle[0] == "refreshSession"){
	//echo("refreshSession");
	ob_end_clean();
	if(!$_SESSION["sUserLogin"]){		
		echo("Tài khoản này đã được login bởi máy khác");
	}
	return false;
}

// kiem tra login
if(!$_SESSION["sUserLogin"]){
	if($listHandle[0]!=_DEFAULT_LOGIN_ || $listHandle[1]==""){
		//echo 5;	
		$listHandle[0] = _DEFAULT_LOGIN_;
		$listHandle[1] = "index";
		//include("www/admin/login.htm");	
		//return false;
	}
}

$class = _FILE_HANDLE_ . $listHandle[0]._CLASS_HANDLE_; 

// kiem tra yeu cau co khong
if(!file_exists($class.".php")){
	echo "khong co trang"; // xuat trang 404
	return false;
}

include($class.".php" );
// kiem tra class co ton tai khong
if(!class_exists($listHandle[0]._CLASS_HANDLE_)){	
	echo "khong co class";
	return false;
}
$classHander = $listHandle[0]._CLASS_HANDLE_;
// khoi tao class_alias
$class_handle = new $classHander();

// kiem tra method co ton tai khong
if($listHandle[1]!="" && !method_exists($class_handle, $listHandle[1])) {	
	echo "khong co method " . $listHandle[1];	
	return false;
}
// goi method khoi tao
$method = "";
if($listHandle[1]==""){
	$method = "index";	
}
else{
	$method = $listHandle[1];	
}
$strRole = "";

if(property_exists($classHander, 'listRole')==true){
	$strRole = $class_handle::$listRole;
}
// kiem tra ham private
$strRolePrivate = "";
if(property_exists($classHander, 'listRolePrivate')===true){
	$strRolePrivate = $class_handle::$listRolePrivate;
}

if($request->checkMethodPrivate($method,$strRolePrivate)&&$strRolePrivate!=""){	
	$chucNangPeer = new ChucNangPeer;
	$listChucNang = $chucNangPeer->getChucNang();
	
	header('Content-Type: text/html; charset=utf-8');
	$request->setAttribute("NoRight","Không có quyền thực hiện chức năng này, vui lòng liên hệ với quản trị.");
	$request->setModel("www/admin/noRight.htm");
	include("www/admin/admin.htm");
	
	return true;
}

// kiem tra quyen
if(!$request->checkRoles($listHandle[0],$method,$strRole)&&$strRole!=""){	
	$chucNangPeer = new ChucNangPeer;
	$listChucNang = $chucNangPeer->getChucNang();
	
	header('Content-Type: text/html; charset=utf-8');
	$request->setAttribute("NoRight","Không có quyền thực hiện chức năng này, vui lòng liên hệ với quản trị.");
	$request->setModel("www/admin/noRight.htm");
	include("www/admin/admin.htm");
	
	return true;
}
ob_end_clean();	
ob_start("ob_gzhandler");

// thuc hien method
// Return null khi xu ly ajax, print khong can giao dien admin
$method_return = $class_handle->$method();

if($method_return!= null){	
	$chucNangPeer = new ChucNangPeer;
	$listChucNang = $chucNangPeer->getChucNang();	
	header('Content-Type: text/html; charset=utf-8');
	include("www/View/_sharedLayout/index.htm");
	// include("www/admin/admin.htm");
	$request->getHiddenRole($strRole);
	return true;
}
ob_end_flush();
?>