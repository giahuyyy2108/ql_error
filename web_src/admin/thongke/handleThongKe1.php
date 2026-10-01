<?PHP	
require_once("web_src/bean/AuthorPeer.php");
require_once("web_src/bean/LoaiBaiVietPeer.php");
require_once("web_src/bean/ClassificationPeer.php");
require_once("web_src/bean/ImagePeer.php");

$ArticlePeer = new ArticlePeer();

$txtprint = $request->getParameter("txtprint");
$startDate = $request->getParameter("startDate");

if($startDate!=""){	
	// view thong ke
	$arrArticle = $ArticlePeer->getListThongKe1($startDate);
	$request->setAttribute("listArticle",$arrArticle);	
	$request->setAttribute("content","www/admin/thongke/viewThongKe1.htm");	
}
else{
	$request->setAttribute("listArticle",false);	
	$request->setAttribute("content","www/admin/thongke/viewThongKe1.htm");
}
?>