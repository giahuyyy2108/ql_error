<?PHP
class Util{
	
	function createKey($number){
		$chars = array( 'a', 'A', 'b', 'B', 'c', 'C', 'd', 'D', 'e', 'E', 'f', 'F', 'g', 'G', 'h', 'H', 'i', 'I', 'j', 'J',  'k', 'K', 'l', 'L', 'm', 'M', 'n', 'N', 'o', 'O', 'p', 'P', 'q', 'Q', 'r', 'R', 's', 'S', 't', 'T',  'u', 'U', 'v', 'V', 'w', 'W', 'x', 'X', 'y', 'Y', 'z', 'Z', '1', '2', '3', '4', '5', '6', '7', '8', '9', '0');
		$max_chars = count($chars) - 1;
		for($i = 0; $i < $number; $i++)
		{
				 $key = ( $i == 0 ) ? $chars[rand(0, $max_chars)] : $key . $chars[rand(0, $max_chars)];
		}
		return $key;
	}
	
	function is_num($var) {
		 for ($i=0;$i<strlen($var);$i++) {
				 $ascii_code=ord($var[$i]);
				 if (intval($ascii_code) >=48 && intval($ascii_code) <=57) {
						 continue;
				 } else {          
						 return false;
				 }
		 } 
		 return true;
 	} 
	
	function bienNhan($number,$max){
		$length = strlen($number);		
		$str = $number;
		for($i=$length;$i<$max;$i++){
			$str = "0".$str;
		}
		return $str;
	}	
	
	function SoNgayTheoThu($tuNgay,$denNgay,$thu){
		$toDate = $this->time($tuNgay);
		$formDate = $this->time($denNgay);
		
		$ngayTrongTuan = "";
		
		if(strtolower($thu) != "cn"){
			$ngayTrongTuan = $thu - 1;
		}
		else {
			$ngayTrongTuan = 0;
		}
		
		$soNgay = 0;		
		while($toDate <= $formDate){
			list($day, $month, $year) = explode("/", date("d/m/Y",$toDate));

			// Get the weekday of the given date
			$wkday = date('w',mktime('0','0','0', $month, $day, $year));
			
			if($wkday==$ngayTrongTuan){
				$soNgay++;
			}			
			
			$toDate = mktime('0','0','0', $month, ($day+1), $year);

		}
		return $soNgay;		
	}
	// doi ngay dd/mm/yyyy sang ngay dd thang mm nam yyyy
	function ngaythangnam($ngay){
		$toDate = $this->time($ngay);
		list($day, $month, $year) = explode("/", date("d/m/Y",$toDate));
		$sNgay = "Ngày " + $day + " tháng " + $month + " năm " + $year;
		return $sNgay;
	}
		
	function time($time=""){
		if($time=="")	$today = date("d/m/Y");
		else $today = $time;
		$explodetoday = explode("/", $today);
		$stoday = mktime(0, 0, 0, $explodetoday[1], $explodetoday[0], $explodetoday[2]);
		return $stoday;
	}
	function isFlashFile($file)
	{
		if(strpos(strtoupper($file),".SWF")>0)
			return true;
		return false;
	}
	
	function createKeyRefresh(){
		$key = $this->createKey(32);
		if ( !isset($_SESSION["sKeyRefresh"]) ){		
			session_register("sKeyRefresh");	
			$_SESSION["sKeyRefresh"] = "";	
		}		
	return $key;		
	}
	
	function isRefresh($key){
		if ( !isset($_SESSION["sKeyRefresh"]) ){
			session_register("sKeyRefresh");	
			$_SESSION["sKeyRefresh"] = $key;	
			return false;
		}		
		if($key == $_SESSION["sKeyRefresh"]) return true;
		$_SESSION["sKeyRefresh"] = $key;
		return false;
	}
	
	// doc so thanh chu
	var $ChuSo = array(" không "," một "," hai "," ba "," bốn "," năm "," sáu "," bảy "," tám "," chín ");
	var $Tien = array( "", " nghìn", " triệu", " tỷ", " nghìn tỷ", " triệu tỷ");
	 
	//1. Hàm đọc số có ba chữ số;
	function DocSo3ChuSo($baso)
	{		
		$tram;
		$chuc;
		$donvi;
		$KetQua="";
		$tram=intval($baso/100);
		$chuc=intval(($baso%100)/10);
		$donvi=$baso%10;
		
		if($tram==0 && $chuc==0 && $donvi==0) return "";
		if($tram!=0)
		{
			$KetQua .= $this->ChuSo[$tram] . " trăm ";
			if (($chuc == 0) && ($donvi != 0)) $KetQua .= " linh ";
		}
		if (($chuc != 0) && ($chuc != 1))
		{
				$KetQua .= $this->ChuSo[$chuc] . " mươi";
				if (($chuc == 0) && ($donvi != 0)) $KetQua = $KetQua . " linh ";
		}
		if ($chuc == 1) $KetQua .= " mười ";
		switch ($donvi)
		{
			case 1:
				if (($chuc != 0) && ($chuc != 1))
				{
					$KetQua .= " mốt ";
				}
				else
				{
					$KetQua .= $this->ChuSo[$donvi];
				}
				break;
			case 5:
				if ($chuc == 0)
				{
					$KetQua .= $this->ChuSo[$donvi];
				}
				else
				{
					$KetQua .= " lăm ";
				}
				break;
			default:
				if ($donvi != 0)
				{
					$KetQua .= $this->ChuSo[$donvi];
				}
				break;
			}
		return $KetQua;
	}
	 
	//2. Hàm đọc số thành chữ (Sử dụng hàm đọc số có ba chữ số)
	 
	function DocTienBangChu($SoTien)
	{	
		$lan=0;
		$i=0;
		$so=0;
		$KetQua="";
		$tmp="";
		$ViTri = array();
		if($SoTien<0) return "Số tiền âm !";
		if($SoTien==0) return "Không đồng !";
		if($SoTien>0)
		{
			$so=$SoTien;
		}
		else
		{
			$so = -$SoTien;
		}
		if ($SoTien > 8999999999999999)
		{
			//SoTien = 0;
			return "Số quá lớn!";
		}
		$ViTri[5] = floor($so / 1000000000000000);
		if(is_nan($ViTri[5]))
			$ViTri[5] = "0";
		$so = $so - floatval($ViTri[5]) * 1000000000000000;
		$ViTri[4] = floor($so / 1000000000000);
		 if(is_nan($ViTri[4]))
			$ViTri[4] = "0";
		$so = $so - floatval($ViTri[4]) * 1000000000000;
		$ViTri[3] = floor($so / 1000000000);
		 if(is_nan($ViTri[3]))
			$ViTri[3] = "0";
		$so = $so - floatval($ViTri[3]) * 1000000000;
		$ViTri[2] = intval($so / 1000000);
		 if(is_nan($ViTri[2]))
			$ViTri[2] = "0";
		$ViTri[1] = intval(($so % 1000000) / 1000);
		 if(is_nan($ViTri[1]))
			$ViTri[1] = "0";
		$ViTri[0] = intval($so % 1000);
	  if(is_nan($ViTri[0]))
			$ViTri[0] = "0";
		if ($ViTri[5] > 0)
		{
			$lan = 5;
		}
		else if ($ViTri[4] > 0)
		{
			$lan = 4;
		}
		else if ($ViTri[3] > 0)
		{
			$lan = 3;
		}
		else if ($ViTri[2] > 0)
		{
			$lan = 2;
		}
		else if ($ViTri[1] > 0)
		{
			$lan = 1;
		}
		else
		{
			$lan = 0;
		}
		for ($i = $lan; $i >= 0; $i--)
		{
		   $tmp = $this->DocSo3ChuSo($ViTri[$i]);
		   $KetQua .= $tmp;
		   if ($ViTri[$i] > 0) $KetQua .= $this->Tien[$i];
		   if (($i > 0) && (strlen($tmp) > 0)) $KetQua .= ',';//&& (!string.IsNullOrEmpty(tmp))
		}
	   if (substr($KetQua,strlen($KetQua) - 1) == ',')
	   {
			$KetQua = substr($KetQua,0, strlen($KetQua) - 1);
	   }
	   $KetQua = strtoupper(substr($KetQua,1,1)). substr($KetQua,2);
	   return $KetQua;//.substring(0, 1);//.toUpperCase();// + KetQua.substring(1);
	}
	function validateEmailAddress($input){
	  $atom = '[a-zA-Z0-9!#$%&\'*+\-\/=?^_`{|}~]+';
	  $quoted_string = '"([\x1-\x9\xB\xC\xE-\x21\x23-\x5B\x5D-\x7F]|\x5C[\x1-\x9\xB\xC\xE-\x7F])*"';
	  $word = "$atom(\.$atom)*";
	  $domain = "$atom(\.$atom)+";
	  return strlen($input) < 256 && preg_match("/^($word|$quoted_string)@${domain}\$/", $input);
	}
	function setCounter(){		
		$counter = $this->getCounter() + 1;
		
		$dbsql = new db_mysql;
		$dbsql->connect();
		$dbsql->selectdb();

		$sSQL="UPDATE counter" ;
		$sSQL.=" SET count='".$counter."'";
		$sSQL.=" WHERE id='1'";
		$dbsql->query($sSQL);
					
		return $counter;
	}
	
	function getCounter(){		
		$count = 0;
		
		$dbsql = new db_mysql;
		$dbsql->connect();
		$dbsql->selectdb();
		
		$sSQL ="SELECT * FROM counter WHERE id='1'";
		$result=$dbsql->query($sSQL);
		
		if($row = $dbsql->fetch_Array($result)){
			$count = $row["count"];
		}
		
		return $count;
	}
	
	function deleteDir($dirPath) {
		if (is_dir($dirPath)) {			
			if (substr($dirPath, strlen($dirPath) - 1, 1) != '/') {
				$dirPath .= '/';
			}
			$files = glob($dirPath . '*', GLOB_MARK);
			foreach ($files as $file) {
				if (is_dir($file)) {
					self::deleteDir($file);
				} else {
					unlink($file);
				}
			}
			rmdir($dirPath);
		}
	}	
}
?>