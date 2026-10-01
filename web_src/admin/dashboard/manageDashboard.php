<?PHP	
/*
$ArticlePeer = new ArticlePeer();

// tong tin
	$tin = 1;
	$tongtin = $ArticlePeer->getCountArticleByLoai($tin);
	// tong bai
	$bai =3;
	$tongbai = $ArticlePeer->getCountArticleByLoai($bai);
	// tong suu tam
	$suutam = 4;
	$tongsuutam = $ArticlePeer->getCountArticleByLoai($suutam);
	// tong tin bai
	$loai = 0;
	$tongtinbai = $tongtin + $tongbai+$tongsuutam;
// do thi
	// tin bai theo nam
	$monthint = (int)date('m');	
	$year = date('Y');
	$datathang = 'var chart_plot_02_data = [';
	for($i=1;$i<=12;$i++){
		$month = $i;
		if($i<10) $month = "0".$i;
		if($i<=$monthint){
			$thangnam = $month."/".$year;
			// get so tin theo thangnam
			$datathang = $datathang . "[".$i.",".$ArticlePeer->getCountArticleByDay($thangnam)."],";			
		}
		else{
			$datathang = $datathang . "[".$i.",0],";
		}
	}
	$datathang = $datathang . "];";

	// tin bai trong thang
	$dayint = (int)date('d');
	$month = date('m');
	$year = date('Y');
	
	$maxDays=date('t');
	$labels = "labels: [";
	$data = "data:[";
	//data: [51, 30, 40, 28, 92, 50, 45,51, 30, 40, 28, 92, 50, 45,92, 50, 45]
	//labels: ["01", "02", "03", "04", "05", "06", "07","08", "09", "10", "11", "12", "13", "14","15","16","17","18", "19", "20", "21", "22","23","24","25","26","27","28","29","30"],
	for($i=1;$i<=$maxDays;$i++){
		$day = $i;
		if($i<10) $day = "0".$i;
		
		if($i < $maxDays) $labels = $labels . '"'.$day.'",';
		if($i == $maxDays) $labels = $labels . '"'.$day.'"]';
		
		if($i<=$dayint){
			$ngaythangnam = $day."/".$month."/".$year;
			// get so tin theo ngaythangnam
			if($i < $dayint) $data = $data . $ArticlePeer->getCountArticleByDay($ngaythangnam).",";
			if($i == $dayint) $data = $data . $ArticlePeer->getCountArticleByDay($ngaythangnam)."]";	
		}
		
	}	

// tong tin bai theo trang thai
	$tongnhan = $ArticlePeer->getCountArticleByStatus(_ARTICLE_NHAN_);
	$tongdelai = $ArticlePeer->getCountArticleByStatus(_ARTICLE_DELAI_);
	$tongbt = $ArticlePeer->getCountArticleByStatus(_ARTICLE_BIENTAP_);
	$tongbt += $ArticlePeer->getCountArticleByStatus(_ARTICLE_CHON_);
	$tongbt+= $ArticlePeer->getCountArticleByStatus(_ARTICLE_CHUYEN_);
	$tongbt+= $ArticlePeer->getCountArticleByStatus(_ARTICLE_HUYDANG_);
	$tongduyet = $ArticlePeer->getCountArticleByStatus(_ARTICLE_CHODUYET_);
	$tongchodang = $ArticlePeer->getCountArticleByStatus(_ARTICLE_DUYET_);
	$tongdang = $ArticlePeer->getCountArticleByStatus(_ARTICLE_DANG_);

//$arrArticle = $ArticlePeer->getListArticleByStatus($style);
*/
echo(1);
$request->setAttribute("content","www/admin/dashboard/viewDashboard.htm");
?>