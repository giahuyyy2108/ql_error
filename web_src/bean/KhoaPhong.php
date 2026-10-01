<?php
class KhoaPhong{   
    var $MaKhoaPhong;
    var $TenKhoaPhong;

    function KhoaPhong(){      
        $this->MaKhoaPhong= 0;
        $this->TenKhoaPhong = "";
    
    }
    
    function setKhoaPhong($key,$value){
        $this->$key = $value;       
    }
    
    function getKhoaPhong($key){
        return $this->$key ;
    }   
    function set($key,$value){
		$this->$key = $value;		
	}
	
	function get($key){
		return $this->$key ;
	}	
}
?>
