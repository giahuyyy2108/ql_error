<?PHP	
require_once("web_src/bean/AuthorPeer.php");
require_once("web_src/bean/LoaiBaiVietPeer.php");
require_once("web_src/bean/ClassificationPeer.php");
require_once("web_src/bean/ImagePeer.php");

$ArticlePeer = new ArticlePeer();
$LoaiBaiVietPeer = new LoaiBaiVietPeer();
$imagePeer = new ImagePeer();

$txtprint = $request->getParameter("txtprint");
$startDate = $request->getParameter("startDate");

$tongtien = 0;
$arrThongKe = false;

//echo "tongtien=".$tongtien."tttttt";

if($startDate!=""){	
	// view thong ke
	$tongtien = $ArticlePeer->getSumThongKe1($startDate);
	// lay ds tac gia
	$AuthorPeer = new AuthorPeer();
	$arrAuthor = $AuthorPeer->getListAuthor();	
	
		
	$i=0;
	if($arrAuthor!=false){		
		foreach($arrAuthor as $Author){
			$tin = 0;
			$bai= 0;
			$anh = 0;
			
			$ntin = 0;
			$nbai = 0;
			$nanh = 0;
			// lay ds bai viet theo tac gia
			$arrArticle = $ArticlePeer->getListThongKe2($startDate,$Author->get("authorID"));
			// tinh nhuan but cua tac gia theo thang
			if($arrArticle!=false){
				foreach($arrArticle as $article){
					$theloai =  $LoaiBaiVietPeer->getLoaiBaiViet($article->get("loaibaivietID"));
					if($theloai->get("number")==250){ // tin
						$tin+= $article->get("tongnhuanbut");
						$ntin++;
					}
					else{ // bai
						$bai+= $article->get("tongnhuanbut");
						$nbai++;
					}
					if($article->get("tongnhuananh")>0) {
						$anh+=$article->get("tongnhuananh");
						// lay so anh cua bai						
						$lstImage = $imagePeer->getListSeleteImageByArticle($article->get("articleID"));						
						if($lstImage!=false) {
							$nanh += count($lstImage);							
						}
					}
				}
				$arrThongKe[$i++] = array($Author->get("fullname"),$Author->get("pseudonym"),$ntin,$tin,$nbai,$bai,$nanh,$anh);
			}
		}
	}	
}

$request->setAttribute("content","www/admin/thongke/viewThongKe2.htm");
?>