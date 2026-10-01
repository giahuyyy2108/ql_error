<?PHP
require_once ("web_src/common/PHPExcel/IOFactory.php");

require_once ("web_src/bean/khoPeer.php");
require_once ("web_src/bean/hangPeer.php");

// class ImportPeer {
// 	var $dbsql;

// 	function ImportPeer() {
// 		$this->dbsql = new db_mysql;
// 		$this->dbsql->connect();
// 		$this->dbsql->selectdb();
// 	}	

// 	function setLog($chucnang){
// 		$log = new Log;			

// 		$log->set("chucnang",$chucnang);		

// 		$logPeer = new LogPeer;		
// 		$logPeer->ghiLog($log);
// 	}		

// 	function import($filename){
// 		//date_default_timezone_set('Asia/Saigon');
// 		$inputFileName = "./import/".$filename;
// 		//echo $inputFileName;
// 		//  Read your Excel workbook
// 		try {
// 			$inputFileType = PHPExcel_IOFactory::identify($inputFileName);
// 			$objReader = PHPExcel_IOFactory::createReader($inputFileType);
// 			$objPHPExcel = $objReader->load($inputFileName);
// 		} catch(Exception $e) {
// 			$msg = 'Error loading file "'.pathinfo($inputFileName,PATHINFO_BASENAME).'": '.$e->getMessage();
// 			die('Error loading file "'.pathinfo($inputFileName,PATHINFO_BASENAME).'": '.$e->getMessage());
// 		}

// 		//  Get worksheet dimensions
// 		$sheet = $objPHPExcel->getSheet(0); 
// 		$highestRow = $sheet->getHighestRow(); 
// 		//$highestColumn = $sheet->getHighestColumn();
// 		$Id_kho = "";
// 		// nhập kho hàng
// 		if($highestRow > 1){
// 			$value = $sheet->getCellByColumnAndRow(1, 1)->getValue();
// 			// tạo kho mới
// 			$khoPeer = new khoPeer;	
// 			$kho = $khoPeer->getTenKhoHang($value);			
// 			if($kho==null) {
// 				$msg = "Tên kho hàng không có thật, vui lòng kiểm tra lại dữ liệu nếu có vấn đề hãy liên hệ admin.";
// 				return $msg;
// 			}	
// 			$Id_kho = $kho->get("Id_kho");	
// 		}

// 		$num = 0;	
// 		//  Loop through each row of the worksheet in turn
// 		for ($row = 3; $row <= $highestRow; $row++){ 			
// 			//  Insert row data array into your database of choice here			
// 			$tenLoaiSanPham = $sheet->getCellByColumnAndRow(1, $row)->getValue();
// 			$tenSanPham = $sheet->getCellByColumnAndRow(2, $row)->getValue();
// 			$giaBan = $sheet->getCellByColumnAndRow(3, $row)->getValue();
// 			$tonDau = $sheet->getCellByColumnAndRow(4, $row)->getValue();
// 			$giaVon = $sheet->getCellByColumnAndRow(5, $row)->getValue();
// 			$tonMin = $sheet->getCellByColumnAndRow(6, $row)->getValue();
// 			$tonMax = $sheet->getCellByColumnAndRow(7, $row)->getValue();
// 			$dvt = $sheet->getCellByColumnAndRow(8, $row)->getValue();			

// 			// xử lý dữ liệu
// 			if($giaBan == "") $giaBan = 0;
// 			if($tonDau == "") $tonDau = 0;
// 			if($giaVon == "") $giaVon = 0;
// 			if($tonMin == "") $tonMin = 0;
// 			if($tonMax == "") $tonMax = 0;

// 			if($tenLoaiSanPham == "" || $tenSanPham == ""){
// 				continue;
// 			}

// 			// them loai san pham	
// 			$maLoaiSanPham = "";	

// 			$LoaiSanPhamPeer = new LoaiSanPhamPeer;	
// 			$maLoaiSanPham = $LoaiSanPhamPeer->createLoaiHang($tenLoaiSanPham,$maKhoHang);

// 			// thêm/update sản phẩm			
// 			$SanPham = new SanPham;				

// 			$SanPham->set("tenSanPham",$tenSanPham);		
// 			$SanPham->set("donViTinh",$dvt);
// 			$SanPham->set("giaVon",$giaVon);				
// 			$SanPham->set("giaBan",$giaBan);

// 			$SanPhamPeer = new SanPhamPeer;	
// 			$maSanPham = $SanPhamPeer->createSanPham($SanPham);

// 			// thêm/update sản phẩm kho
// 			$SanPhamKho = new SanPhamKho;

// 			$SanPhamKho->set("maSanPham",$maSanPham);
// 			$SanPhamKho->set("maKhoHang",$maKhoHang);
// 			$SanPhamKho->set("maLoaiSanPham",$maLoaiSanPham);
// 			$SanPhamKho->set("tenSanPham",$tenSanPham);		
// 			$SanPhamKho->set("donViTinh",$dvt);			
// 			$SanPhamKho->set("tonCuoi",$tonDau);
// 			$SanPhamKho->set("tonMin",$tonMin);
// 			$SanPhamKho->set("tonMax",$tonMax);
// 			$SanPhamKho->set("giaVon",$giaVon);
// 			$SanPhamKho->set("giaBan",$giaBan);	

// 			$SanPhamKhoPeer = new SanPhamKhoPeer;	
// 			$maSanPhamKho = $SanPhamKhoPeer->createSanPham($SanPhamKho);

// 			// tao phien
// 			//date_default_timezone_set('Asia/Saigon');
// 			$ngay = date("d/m/Y");

// 			$PhienPeer = new PhienPeer;
// 			$Phien = $PhienPeer->createPhien($ngay);

// 			$Ton = new Ton;
// 			$Ton->set("maPhien",$Phien->get("maPhien"));	
// 			$Ton->set("maSanPham",$maSanPhamKho);			
// 			$Ton->set("tonDau",$tonDau);					
// 			$Ton->set("tonCuoi",$tonDau);
// 			$Ton->set("tongNhap",0);			
// 			$Ton->set("tongXuat",0);					
// 			$Ton->set("kiemTra",0);				

// 			$TonPeer = new TonPeer();
// 			$TonPeer->save($Ton);				

// 			// cập nhật báo giá
// 			$ctbgPeer = new ChiTietBaoGiaPeer();
// 			$chitietBG = new ChiTietBaoGia;		

// 			$chitietBG->set("maBG","1");
// 			$chitietBG->set("maSanPham",$maSanPham);
// 			$chitietBG->set("giaBan",$giaBan);

// 			if(!$ctbgPeer->layBaoGiaChiTietSP("1",$maSanPham)) {
// 				$ctbgPeer->save($chitietBG);
// 			}
// 			else {					
// 				$ctbgPeer->updateSanPhamBaoGia($chitietBG);
// 			}

// 			$num++;	
// 		}

// 		if($msg==""){
// 			$msg = "Cập nhật thành công ".$num." dòng dữ liệu. Kiểm tra lại dữ liệu nếu có vấn đề hãy liên hệ admin.";
// 		}

// 		$this->setLog("Import");

// 		return $msg;
// 	}
// }
?>