<?PHP
class Log{	
  
	var $logID;
	var $ngay; 
	var $ten;	
	var $chucnang;
	var $noidung;
	var $noidungcu;

	function Log(){		
		$this->logID = 0;
		$this->ngay = ""; 
		$this->ten = "";	
		$this->chucnang = "";
		$this->noidung = "";
		$this->noidungcu = "";	
	}
	
	function set($key,$value){
		$this->$key = $value;		
	}
	
	function get($key){
		return $this->$key ;
	}		
}
?>