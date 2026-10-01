<?PHP
	require_once("web_src/bean/hangPeer.php");

	$hangPeer = new hangPeer();	
	$mahang = $request->getParameter("id");	
	
	// kiem tra hang xoa khong phai la hang admin
	$hang = $hangPeer->gethangID($mahang);
	
	if($hang!=null && $hang->get("adminType")!=1){
		// xoa du lieu tren database	
		$hangPeer->deletehang($mahang);	
		$request->setAttribute("msg","Xóa người dùng thành công đã thành công.");	
	}
	
	include("web_src/admin/nguoidung/manageNguoiDung.php");
?>