<?PHP
class ChucNang {		
	var $maChucNang;
	var $tenChucNang;
	var $parent;
	var $url;
	var $parentQuyen;
	var $tenChucNangCon;
	var $urlChucNangCon;
	var $order;
	var $level;
	var $logo;

	function ChucNang(){		
		$this->maChucNang = 0;
		$this->tenChucNang = "";
		$this->parent = "";
		$this->url = "";
		$this->parentQuyen = "";
		$this->tenChucNangCon = "";
		$this->urlChucNangCon = "";
		$this->order = "";
		$this->level="";
		$this->logo="";
	}
		
	function set($key,$value){
		$this->$key = $value;		
	}
	
	function get($key){
		return $this->$key ;
	}		
}
?>