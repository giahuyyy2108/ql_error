<?PHP
class NhomQuyen
{

	var $maNQ;
	var $tenNQ;
	var $quyen;

	function __construct()
	{
		$this->maNQ = 0;
		$this->tenNQ = "";
		$this->quyen = "";
	}

	function set($key, $value)
	{
		$this->$key = $value;
	}

	function get($key)
	{
		return $this->$key;
	}
}
?>