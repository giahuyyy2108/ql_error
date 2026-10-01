<?PHP
require("web_src/bean/UserPeer.php");

// lay ra danh sach mon hoc
$UserPeer = new UserPeer;
// lay so phan tu hien co tren form
$count = ($request->getParameter("htxtCount")!="") ? $request->getParameter("htxtCount") : 0;

$msg="";

for($i=1;$i<=$count;$i++){			
	$edit = ($request->getParameter("edit".$i)!="") ? $request->getParameter("edit".$i) :0;
	
	if($edit==1){
		$txtMaNguoiDung = ($request->getParameter("txtMaNguoiDung".$i)!="") ? $request->getParameter("txtMaNguoiDung".$i) : 0;
		$txtTenDangNhap = $request->getParameter("txtTenDangNhap".$i);
		$txtTaiKhoang = $request->getParameter("txtTaiKhoang".$i);
		$txtHoTen = $request->getParameter("txtHoTen".$i);	
		$txtDienThoai = $request->getParameter("txtDienThoai".$i);
		$txtEmail = $request->getParameter("txtEmail".$i);
		$txtDiaChi = $request->getParameter("txtDiaChi".$i);	
				
		if(($txtTenDangNhap !="" && $txtMaNguoiDung != 0) || ($txtTenDangNhap !="" && $txtTaiKhoang !="" && $txtMaNguoiDung ==0)){
			$User = new User;
			$User->set("id",$txtMaNguoiDung);	
			$User->set("username",$txtTenDangNhap);
			$User->set("password",$txtTaiKhoang);
			$User->set("hoTen",$txtHoTen);		
			$User->set("dienThoai",$txtDienThoai);
			$User->set("email",$txtEmail);			
			$User->set("diaChi",$txtDiaChi);	
			
			$UserPeer->save($User);
		}
	}
}
$msg="C&#7853;p nh&#7853;t người dùng th&agrave;nh c&ocirc;ng.";

include("./web_src/admin/nguoidung/manageNguoiDung.php");
$request->setAttribute("msg",$msg);
?>