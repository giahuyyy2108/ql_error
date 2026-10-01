<?PHP	
require_once("web_src/bean/AuthorPeer.php");
require_once("web_src/bean/LoaiBaiVietPeer.php");
require_once("web_src/bean/ClassificationPeer.php");
require_once("web_src/bean/ImagePeer.php");

$number = $request->getParameter("txtReport");
//echo "aa=".$number;
switch ($number) {		
	case 1:		
		//$request->setAttribute("content","www/admin/thongke/viewThongKe1.htm");
		include("web_src/admin/thongke/handleThongKe1.php");
		break;		
	case 2:		
		include("web_src/admin/thongke/handleThongKe2.php");
		break;
	case 3:		
		include("web_src/admin/thongke/handleThongKe3.php");
		break;
	case 4:		
		include("web_src/admin/thongke/handleThongKe4.php");
		break;
	case 5:		
		include("web_src/admin/thongke/handleThongKe5.php");
		break;
	default:
		$request->setAttribute("content","www/admin/thongke/viewThongKe.htm");		
}
?>