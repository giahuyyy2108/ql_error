<?PHP
require_once ("web_src/bean/NhomQuyenPeer.php");
require_once ("web_src/bean/ChucNangPeer.php");

class nhomquyenAction
{
	var $request;
	var $nhomquyenPeer;
	public static $listRole = "nhomquyen,save,delete,phanquyen";

	public function __construct()
	{
		$this->request = new Request;
		$this->nhomquyenPeer = new NhomQuyenPeer();
		$this->request->setTitle("Danh sách Nhóm quyền");
	}
	// duong dan trang html
	function index()
	{
		$this->request->setAttribute('script', '<script src="' . _DEFAULT_URL_ . 'js/nhomquyen.js?' . _DEFAULT_VERSION_JS_CSS_ . '"></script>');
		$this->request->setAttribute('css', '<link href="' . _DEFAULT_URL_ . 'css/style.css?' . _DEFAULT_VERSION_JS_CSS_ . '" rel="stylesheet">');

		$this->request->setModel("www/admin/nguoidung/nhomquyen.htm");
		return true;
	}
	// get date form tim kiem
	function getData()
	{
		$tenNQ = $this->request->getParameter("tenNQ");

		$arrNQ = $this->nhomquyenPeer->getListNQ($tenNQ);

		$data["data"] = $arrNQ;
		$myJSON = json_encode($data);

		return $this->request->json_response($myJSON);
	}

	function save()
	{
		$nqId = ($this->request->getParameter("id") != "") ? $this->request->getParameter("id") : 0;
		$data = $this->request->getParameter("data", true);

		$arrayData = json_decode($data, true);
		if (empty($arrayData)) {
			$message = new Message();
			$message->set("flag", false);
			$message->set("errorMessage", "Bạn chưa sửa dữ liệu gì trên dòng này. Vui lòng sửa trước khi lưu.");
			$response["message"] = $message;

			$myJSON = json_encode($response);
			return $this->request->json_response($myJSON);
		}
		// if (!preg_match('/^[a-zA-ZÀ-ỹĂăÂâĐđÊêÔôƠơƯư0-9\s]+$/u', $arrayData[0])) {
		// 	$message = new Message();
		// 	$message->set("flag", false);
		// 	$message->set("errorMessage", "Tên nhóm quyền chỉ được nhập chữ và số. Vui lòng nhập lại.");
		// 	$response["message"] = $message;

		// 	$myJSON = json_encode($response);
		// 	return $this->request->json_response($myJSON);
		// }
		if ($arrayData[0] != "") {
			$nhomquyen = new NhomQuyen;
			$nhomquyen->set("maNQ", $nqId);
			$nhomquyen->set("tenNQ", $arrayData[0]);
			$id = $this->nhomquyenPeer->save($nhomquyen);

			$message = new Message();
			$message->set("flag", true);
			$message->set("succesMessage", "Cập nhật nhóm quyền thành công");

			$response["id"] = $id;
			$response["message"] = $message;

			$myJSON = json_encode($response);
			//echo $myJSON;
			return $this->request->json_response($myJSON);
		}

		$message = new Message();
		$message->set("flag", false);
		$message->set("errorMessage", "");

		$response["message"] = $message;

		$myJSON = json_encode($response);
		//echo $myJSON;
		return $this->request->json_response($myJSON);
	}

	function delete()
	{
		$nqId = ($this->request->getParameter("id") != "") ? $this->request->getParameter("id") : 0;
		$this->nhomquyenPeer->delete($nqId);

		$myJSON = json_encode(true);

		return $this->request->json_response($myJSON);
	}

	function phanquyen()
	{
		$maNQ = $this->request->getParameter("maNQ");

		$chucNangPeer = new ChucNangPeer;

		$nhomquyen = new NhomQuyen;
		$nhomquyen = $this->nhomquyenPeer->getNQID($maNQ);

		$quyen = explode(",", $nhomquyen->get("quyen"));
		$listChucNang = $chucNangPeer->getChucNang();
		//print_r($_SESSION["quyen"]);		
		include ("www/admin/phanquyen/viewListPhanQuyen.htm");

		return "";
	}

	function savePhanquyen()
	{
		$maNQ = $this->request->getParameter("username");
		$numCheck = $this->request->getParameter("numCheck");
		$quyen = "";

		$phay = "";

		for ($i = 0; $i < $numCheck; $i++) {
			//$checkbox = $_POST['checkbox'.$i];

			if (isset($_POST['checkbox' . $i])) {
				$quyen = $quyen . $phay . implode(",", $_POST['checkbox' . $i]);
				$phay = ",";
			}
		}

		$nhomquyen = $this->nhomquyenPeer->getNQID($maNQ);
		$nhomquyen->set("quyen", $quyen);

		$this->nhomquyenPeer->update($nhomquyen);

		$myJSON = json_encode("Đã lưu");

		return $this->request->json_response($myJSON);
	}
}
?>