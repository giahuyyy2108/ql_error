<?PHP

class Message{
	var $errorMessage;
	var $succesMessage;
	var $flag;
	function Message(){		
		$this->errorMessage = "";
		$this->succesMessage = "";
		$this->flag = true;		
	}
	
	function set($key,$value){
		$this->$key = $value;		
	}
	
	function get($key){
		return $this->$key ;
	}		
}

?>