<?PHP
	require_once("web_src/bean/UserPeer.php");

	$UserPeer = new UserPeer();	
	$maUser = $request->getParameter("id");	
	
	// kiem tra user xoa khong phai la user admin
	$user = $UserPeer->getUserID($maUser);
	
	if($user!=null && $user->get("adminType")!=1){
		// xoa du lieu tren database	
		$UserPeer->deleteUser($maUser);	
		$request->setAttribute("msg","Xóa người dùng thành công đã thành công.");	
	}
	
	include("web_src/admin/nguoidung/manageNguoiDung.php");
?>