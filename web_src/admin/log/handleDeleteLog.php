<?PHP
	$logPeer = new LogPeer();
	//Xoa Lop
	$maLog = $request->getParameter("id");	
	// xoa du lieu tren database	
	$logPeer->deleteLog($maLog);
	
	$request->setAttribute("msg","Xóa Log đã thành công.");	
	
	include("web_src/admin/log/manageLog.php");
?>