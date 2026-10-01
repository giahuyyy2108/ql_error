<?PHP
$attribute;
$title = "";
$responseJson = "";
class Request
{

	function setAttribute($key, $value)
	{
		$GLOBALS['attribute'][$key] = $value;
	}

	function getAttribute($key)
	{
		return isset($GLOBALS['attribute'][$key]) ? $GLOBALS['attribute'][$key] : "";
	}

	function setTitle($title)
	{
		$GLOBALS['title'] = $title;
	}

	function getParameter($key, $checkSQL = true)
	{
		global $connect;
		if (isset($_POST[$key])) {
			$value = $_POST[$key];
			if ($key === 'data') {
				$decodedValue = json_decode($value, true);
				if ($decodedValue !== null && json_last_error() === JSON_ERROR_NONE) {
					array_walk_recursive($decodedValue, function (&$item) use ($connect) {
						$item = str_ireplace("script", "blocked", $item);
						$item = htmlspecialchars(stripslashes($item));
						$item = mysqli_real_escape_string($connect, $item);
					});
					$value = json_encode($decodedValue);
				}
			}
			return $value;
		}

		if (isset($_GET[$key])) {
			$value = $_GET[$key];
			if ($key === 'data') {
				$decodedValue = json_decode($value, true);
				if ($decodedValue !== null && json_last_error() === JSON_ERROR_NONE) {
					array_walk_recursive($decodedValue, function (&$item) use ($connect) {
						$item = str_ireplace("script", "blocked", $item);
						$item = htmlspecialchars(stripslashes($item));
						$item = mysqli_real_escape_string($connect, $item);
					});
					$value = json_encode($decodedValue);
				}
			}
			return $value;
		}

		return "";
	}


	function setModel($model)
	{
		$this->setAttribute("content", $model);
	}

	function getModel()
	{
		return $this->getAttribute("content");
	}

	function getRole()
	{
		return $_SESSION["quyen"];
	}
	function setRole($listRole)
	{
		$_SESSION["quyen"] = explode(",", $listRole);
	}

	function checkRoleUser($role, $quyen)
	{
		if ($quyen == "")
			return false;
		return in_array($role, $quyen);
	}

	function checkRole($role)
	{
		if ($_SESSION["quyen"] == null)
			return false;
		if ($_SESSION["AdminType"] == 1)
			return true;
		return in_array($role, $_SESSION["quyen"]);
	}

	function checkRoles($class, $method, $strRole)
	{
		if (!$_SESSION["sUserLogin"])
			return false;
		if ($_SESSION["AdminType"] == 1)
			return true;
		if ($_SESSION["quyen"] == null)
			return false;

		$listRole = explode(",", $strRole);
		if (!in_array($class, $listRole) && !in_array($method, $listRole))
			return true;

		if (!$this->checkRole($class))
			return false;
		if ($this->checkRole($class) && !in_array($method, $listRole))
			return true;

		$role = $class . "." . $method;
		if ($method == "index")
			$role = $class;

		return $this->checkRole($role);
	}

	function checkMethodPrivate($method, $strRole)
	{

		$listRole = explode(",", $strRole);
		if (in_array($method, $listRole))
			return true;


		return false;
	}

	function getHiddenRole($listRole)
	{
		// print_r($listRole);
		if ($listRole != "") {
			$arrRole = explode(",", $listRole);
			$c = count($arrRole);
			for ($i = 1; $i < $c; $i++) {
				$name = "role-" . $arrRole[0] . "-" . $arrRole[$i];
				$value = $this->checkRole($arrRole[0] . "." . $arrRole[$i]) ? 'true' : 'false';
				echo ('<input type="hidden" id="' . $name . '" value="' . $value . '">');
			}
		}
	}

	function json_response($JSON)
	{
		header('Content-Type: application/json');
		//echo('{"data":'.$JSON.'}');
		$GLOBALS['responseJson'] = $JSON;
		echo ($JSON);
		return null;
	}
}
?>