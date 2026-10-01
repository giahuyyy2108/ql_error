<?PHP
class User
{

	var $id;
	var $username;
	var $password;
	var $hoTen;
	var $diaChi;
	var $email;
	var $dienThoai;
	var $adminType;
	var $quyen;
	var $maNQ;
	var $nd_block;
	var $changeQuyen;
	var $token;

	function User()
	{
		$this->id = 0;
		$this->username = "";
		$this->password = "";
		$this->hoTen = "";
		$this->diaChi = "";
		$this->email = "";
		$this->dienThoai = "";
		$this->adminType = 0;
		$this->quyen = "";
		$this->maNQ = 0;
		$this->nd_block = 0;
		$this->changeQuyen = 0;
		$token = "";
	}

	function setUser($key, $value)
	{
		$this->$key = $value;
	}

	function getUser($key)
	{
		return $this->$key;
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