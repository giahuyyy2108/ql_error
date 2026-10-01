<?PHP
class UseCaseDispatcherLocator {
	var $locator;
	function UseCaseDispatcherLocator(){
	//////////////// trang quan tri ////////////////////////
// login va doi pass
		$this->locator[30] = "./web_src/admin/login.php";
		$this->locator[31] = "./web_src/admin/changePass.php";
		$this->locator[32] = "./web_src/admin/handleChangePass.php";		
	
	
// quan tri danh 
	// Danh muc.
		// Author 
		$this->locator[40] = "./web_src/admin/author/manageAuthor.php";
		$this->locator[41] = "./web_src/admin/author/handleDeleteAuthor.php";
		$this->locator[43] = "./web_src/admin/author/handleAddAuthor.php";		
		
		// cataloge (chuyên mục)
		$this->locator[50] = "./web_src/admin/cataloge/manageCataloge.php";
		$this->locator[51] = "./web_src/admin/cataloge/handleDeleteCataloge.php";
		$this->locator[53] = "./web_src/admin/cataloge/handleAddCataloge.php";	
		
		// quan tri loại bai viết
		$this->locator[60] = "./web_src/admin/loaibaiviet/manageLoaiBaiViet.php";
		$this->locator[61] = "./web_src/admin/loaibaiviet/handleDeleteLoaiBaiViet.php";
		$this->locator[63] = "./web_src/admin/loaibaiviet/handleAddLoaiBaiViet.php";

		// quan tri loại bai viết
		$this->locator[70] = "./web_src/admin/classification/manageClassification.php";
		$this->locator[71] = "./web_src/admin/classification/handleDeleteClassification.php";
		$this->locator[73] = "./web_src/admin/classification/handleAddClassification.php";	
		
	// Ket thuc danh muc
		
	// Quan ly tin bai
		// Danh sách tin bài đã nhận
		$this->locator[80] = "./web_src/admin/article/manageArticle.php";
		// danh sach tin bài để lại
		$this->locator[81] = "./web_src/admin/article/manageArticle.php";
		// xóa tin bài
		$this->locator[82] = "./web_src/admin/article/handleDeleteArticle.php";
		// Nhận tin bài
		$this->locator[83] = "./web_src/admin/article/createArticle.php";		
		$this->locator[84] = "./web_src/admin/article/handleCreateArticle.php";
		// Sửa tin bài
		$this->locator[85] = "./web_src/admin/article/editArticle.php";		
		$this->locator[86] = "./web_src/admin/article/handleEditArticle.php";
		$this->locator[87] = "./web_src/admin/article/changeStatusArticle.php";
		
		// danh sach tin bai đã chon/ chưa biên tập
		$this->locator[88] = "./web_src/admin/article/manageCompilationArticle.php";
		
		// danh sach tin bai đã duyêt
		$this->locator[89] = "./web_src/admin/article/manageArticle.php";
		// danh sach tin bai đã chuyển
		$this->locator[90] = "./web_src/admin/article/manageArticle.php";
		// danh sach tin bài đã đăng
		$this->locator[91] = "./web_src/admin/article/manageArticle.php";
		// danh sach tin bài huy đăng
		$this->locator[92] = "./web_src/admin/article/manageArticle.php";
		
		// Biên soạn bài viết		
		$this->locator[93] = "./web_src/admin/article/compilationArticle.php";
		// lưu biên soạn bài viêt
		$this->locator[94] = "./web_src/admin/article/handleCompilationArticle.php";
		// Luu Biên soạn bài viêt lần 1
		$this->locator[95] = "./web_src/admin/article/handleCompilationArticle.php";
		// danh sach tin bai bien tap lan 1	
		$this->locator[96] = "./web_src/admin/article/manageCompilationArticle.php";
		
		// De xuat nhuan but
		$this->locator[97] = "./web_src/admin/article/nhuanButArticle.php";
		
		// Lấy nội dụng (text) của văn bản
		$this->locator[98] = "./web_src/admin/article/getTextArticle.php";
		// Luu De xuat nhuan but
		$this->locator[99] = "./web_src/admin/article/handleNhuanButArticle.php";
		$this->locator[100] = "./web_src/admin/article/handleNhuanButArticle.php";
		
		// duyệt bài viết
		$this->locator[101] = "./web_src/admin/article/manageDuyetArticle.php";
		//sua tin bai can duyet
		$this->locator[102] = "./web_src/admin/article/duyetArticle.php";
		// duyệt tin bai
		$this->locator[103] = "./web_src/admin/article/handleDuyetArticle.php";
		// chuyển tin bai
		$this->locator[104] = "./web_src/admin/article/handleDuyetArticle.php";
		// để lại tin bai
		$this->locator[105] = "./web_src/admin/article/handleDuyetArticle.php";
		
		// đăng bài viết
		$this->locator[106] = "./web_src/admin/article/manageDangArticle.php";
		// sua bai viet dang
		$this->locator[107] = "./web_src/admin/article/dangArticle.php";
		// Dang tin bai
		$this->locator[108] = "./web_src/admin/article/handleDangArticle.php";
		// Huy dang tin bai
		$this->locator[109] = "./web_src/admin/article/handleDangArticle.php";
		
		// danh sach tin bai da dang
		$this->locator[110] = "./web_src/admin/article/manageDaDangArticle.php";
		
		// tim kiem tin bai
		$this->locator[111] = "./web_src/admin/article/manageSearchArticle.php";
		
		// thong ke bao cao
		$this->locator[112] = "./web_src/admin/thongke/manageThongKeArticle.php";
		// xử lý thông kê chi tiết tin bài theo tháng (report 1)
		$this->locator[113] = "./web_src/admin/thongke/handleThongKe1.php";
		$this->locator[114] = "./web_src/admin/thongke/handlePrintThongKe1.php";
		
		// xu ly thong ke 2
		$this->locator[115] = "./web_src/admin/thongke/handleThongKe2.php";
		$this->locator[116] = "./web_src/admin/thongke/handlePrintThongKe2.php";
		// xu ly thong ke 3
		$this->locator[117] = "./web_src/admin/thongke/handleThongKe3.php";
		$this->locator[118] = "./web_src/admin/thongke/handlePrintThongKe3.php";
		// xu ly thong ke 4
		$this->locator[119] = "./web_src/admin/thongke/handleThongKe4.php";
		$this->locator[120] = "./web_src/admin/thongke/handlePrintThongKe4.php";
		// xu ly thong ke 5
		$this->locator[121] = "./web_src/admin/thongke/handleThongKe5.php";
		$this->locator[122] = "./web_src/admin/thongke/handlePrintThongKe5.php";
		// theo doi tin bai
		$this->locator[123] = "./web_src/admin/article/theodoi.php";
		// dashboard
		$this->locator[124] = "./web_src/admin/dashboard/manageDashboard.php";

		
		// Hệ Thống
		// quan tri nguoi dung
		$this->locator[150] = "./web_src/admin/nguoidung/manageNguoiDung.php";
		$this->locator[151] = "./web_src/admin/nguoidung/handleDeleteNguoiDung.php";
		$this->locator[153] = "./web_src/admin/nguoidung/handleAddNguoiDung.php";
		
		// phan quyen
		$this->locator[160] = "./web_src/admin/phanquyen/managePhanQuyen.php";
		$this->locator[161] = "./web_src/admin/phanquyen/handlePhanQuyen.php";
		
		//backup restore
		$this->locator[172] = "./web_src/admin/backuprestore/manageBackupRestore.php";
		$this->locator[173] = "./web_src/admin/backuprestore/handleBackupRestore.php";		
		
		//log
		$this->locator[200] = "./web_src/admin/log/manageLog.php";
		$this->locator[201] = "./web_src/admin/log/handleDeleteLog.php";		
	}
	function getLocator($key){
		return $this->locator[$key];
	}	
}
?>