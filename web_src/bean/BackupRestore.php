<?PHP
class BackupRestore {	
  
	function BackupRestore(){		
			
	}	
	
	function set($key,$value){
		$this->$key = $value;		
	}
	
	function get($key){
		return $this->$key ;
	}		
}
?>