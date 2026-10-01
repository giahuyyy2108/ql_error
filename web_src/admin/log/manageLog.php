<?PHP	
require_once("web_src/common/PageNav.php");
require_once("web_src/bean/UserPeer.php");

$UserPeer = new UserPeer();
$arrUser = $UserPeer->getListUser();
$request->setAttribute("listNguoiDung",$arrUser);

$page = ($request->getParameter("page") == "" ) ? 0 : $request->getParameter("page");

$username = $request->getParameter("select_user");
$chucnang = $request->getParameter("txtChucNang");

$logPeer = new LogPeer();

$arrLog = $logPeer->getListLog($username,$chucnang,$page, _ITEMS_PER_PAGE_ADMIN_);
$numLog = $logPeer->getCount($username,$chucnang);
$pageNav = new PageNav($numLog,_ITEMS_PER_PAGE_ADMIN_,$page,"page","cmd=200&select_user=$username&txtChucNang=$chucnang");
$sPage = $pageNav->renderNav();


$request->setAttribute("listLog",$arrLog);

$request->setAttribute("content","www/admin/log/viewListLog.htm");
?>