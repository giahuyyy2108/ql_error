<?PHP
require_once ("web_src/common/PageNav.php");
require_once ("web_src/bean/UserPeer.php");

class logAction
{
	var $request;
	var $userPeer;
	var $logPeer;
	public static $listRole = "log,deleteLog";

	public function __construct()
	{
		$this->request = new Request;
		$this->userPeer = new UserPeer();
		$this->logPeer = new LogPeer();
		$this->request->setTitle("Danh sách log người dùng");
	}

	function index()
	{

		$arrUser = $this->userPeer->getListUser();

		$page = ($this->request->getParameter("page", false) == "") ? 0 : $this->request->getParameter("page", false);
		// print_r($page);
		$username = $this->request->getParameter("select_user");
		$chucnang = $this->request->getParameter("txtChucNang");

		$arrLog = $this->logPeer->getListLog($username, $chucnang, $page, _ITEMS_PER_PAGE_ADMIN_);
		$numLog = $this->logPeer->getCount($username, $chucnang);
		$pageNav = new PageNav($numLog, _ITEMS_PER_PAGE_ADMIN_, $page, "page", "select_user=$username&txtChucNang=$chucnang");
		// print_r($pageNav);
		$sPage = $pageNav->renderNav();

		$this->request->setAttribute("listNguoiDung", $arrUser);
		$this->request->setAttribute("listLog", $arrLog);
		$this->request->setAttribute("numLog", $numLog);
		$this->request->setAttribute("sPage", $sPage);
		$this->request->setAttribute("page", $page);
		$this->request->setAttribute("username", $username);
		$this->request->setAttribute("chucnang", $chucnang);


		$this->request->setModel("www/admin/log/viewListLog.htm");
		$this->request->setAttribute('script', '<script src="' . _DEFAULT_URL_ . 'js/log.js?' . _DEFAULT_VERSION_JS_CSS_ . '"></script>');
		return true;
	}
	function deleteLog()
	{
		$logId = ($this->request->getParameter("id") != "") ? $this->request->getParameter("id") : 0;
		$this->logPeer->deleteLog($logId);

		$myJSON = json_encode(true);
		//echo $myJSON;
		return $this->request->json_response($myJSON);
	}
}

?>