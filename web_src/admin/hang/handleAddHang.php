<?PHP
require("web_src/bean/hangPeer.php");

// lay ra danh sach mon hoc
$hangPeer = new hangPeer;
// lay so phan tu hien co tren form
$count = ($request->getParameter("htxtCount")!="") ? $request->getParameter("htxtCount") : 0;

$msg="";

for($i=1;$i<=$count;$i++){			
	$edit = ($request->getParameter("edit".$i)!="") ? $request->getParameter("edit".$i) :0;
	
	if($edit==1){
		$txtId_hang = ($request->getParameter("txtId_hang".$i)!="") ? $request->getParameter("txtId_hang".$i) : 0;
		$txtTen_hang = $request->getParameter("txtTen_hang".$i);
		$txtDVT = $request->getParameter("txtDVT".$i);
		$txtKho = $request->getParameter("txtKho".$i);	
		$txtLo = $request->getParameter("txtLo".$i);
		$txtDate = $request->getParameter("txtDate".$i);
		$txtSL = $request->getParameter("txtSL".$i);
		$txtGia = $request->getParameter("txtGia".$i);
		$txtNguon = $request->getParameter("txtNguon".$i);	
				
		if(($txtTen_hang !="" && $txtId_hang != 0) || ($txtTen_hang !="" && $txtDVT !="" && $txtId_hang ==0)){
			$hang = new hang;
			$hang->set("id",$txtId_hang);	
			$hang->set("Ten",$txtTen_hang);
			$hang->set("dvt",$txtDVT);
			$hang->set("kho",$txtKho);		
			$hang->set("lo",$txtLo);
			$hang->set("date",$txtDate);			
			$hang->set("sl",$txtSL);
			$hang->set("gia",$txtGia);			
			$hang->set("nguon",$txtNguon);	
			
			$hangPeer->save($hang);
		}
	}
}
$msg="C&#7853;p nh&#7853;t người dùng th&agrave;nh c&ocirc;ng.";

include("./web_src/admin/hang/managehang.php");
$request->setAttribute("msg",$msg);
?>