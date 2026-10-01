<?PHP	
require_once("web_src/bean/AuthorPeer.php");

$ArticlePeer = new ArticlePeer();

$startDate = $request->getParameter("startDate");
$fromDate = $request->getParameter("fromDate");
$msg = "";
$listTong =  false;

$thang1 = 0;
$nam1= 0;
$thang2 = 0;
$nam2= 0;

if($startDate!="" && $fromDate!=""){
	//kiem tra tu ngay den ngay
	$date1 = explode("/",$startDate);
	$date2 = explode("/",$fromDate);

	$thang1 = (int)$date1[0];
	$nam1= (int)$date1[1];
	$thang2 = (int)$date2[0];
	$nam2= (int)$date2[1];

	if($nam2>$nam1) $thang2+=12;

	$error = false;

	if($thang1 > $thang2){
		$error = true;
		$msg = "Dữ liệu không hợp lệ";
	}
	
	if($error!=true){			
		// lay ds tac gia
		$AuthorPeer = new AuthorPeer();
		$arrAuthor = $AuthorPeer->getListAuthor();					
	}
}

$request->setAttribute("content","www/admin/thongke/viewThongKe4.htm");
?>
