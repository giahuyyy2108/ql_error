<?PHP	
ini_set('display_errors', TRUE);
ini_set('display_startup_errors', TRUE);

date_default_timezone_set('Asia/Saigon');

define('EOL',(PHP_SAPI == 'cli') ? PHP_EOL : '<br />');

/** Include PHPExcel */
require_once 'web_src/common/PHPExcel.php';

require_once("web_src/bean/AuthorPeer.php");
require_once("web_src/bean/LoaiBaiVietPeer.php");
require_once("web_src/bean/ClassificationPeer.php");
require_once("web_src/bean/ImagePeer.php");

$ArticlePeer = new ArticlePeer();

$startDate = $request->getParameter("startDate");
//echo "aaaa".$startDate;
function getNameFromNumber($num) {
	$numeric = ($num - 1) % 26;
	$letter = chr(65 + $numeric);
	$num2 = intval(($num - 1) / 26);
	if ($num2 > 0) {
		return getNameFromNumber($num2) . $letter;
	} else {
		return $letter;
	}
}

// khoi tao css
$styleArray = array(
	'font'    => array(
		'size' => 10,
		'name'  => 'Times New Roman'
	)
);
$styleTitle = array(
	'alignment' => array(
		'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
		'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER	
	),
	'font' => array(
		'bold' => true,
		'size' => 11,
		'name'  => 'Times New Roman'
	)	
);
$styleTitle1 = array(
	'alignment' => array(
		'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
		'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER		
	),
	'font' => array(
		'bold' => true,
		'size' => 14,
		'name'  => 'Times New Roman'
	)	
);
$styleCenter = array(
	'alignment' => array(
		'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
		'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER	
	)				
);
$styleCenter1 = array(
	'alignment' => array(		
		'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER	
	)				
);
$styleCenter2 = array(
	'alignment' => array(	
		'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
		'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER	
	),
	'font' => array(
		'italic'=> true,
		'size' => 13,
		'name'  => 'Times New Roman'
	)				
);

$styleCenter3 = array(
	'alignment' => array(	
		'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
		'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER	
	),
	'font' => array(
		'bold' => true,		
		'size' => 13,
		'name'  => 'Times New Roman'
	)				
);

$styleBorder = array(
	'borders' => array(
		'allborders' => array(
		  'style' => PHPExcel_Style_Border::BORDER_THIN
		)
	)
);
// ket thuc khoi tao

// Create new PHPExcel object
//echo date('H:i:s') , " Create new PHPExcel object" , EOL;
$objPHPExcel = new PHPExcel();

// Set document properties
$objPHPExcel->getProperties()->setCreator("Tran Viet Khoi")
							 ->setLastModifiedBy("Tran Viet Khoi")
							 ->setTitle("Danh sach chi tiet tin bai")
							 ->setSubject("Danh sach chi tiet tin bai")
							 ->setDescription("Danh sach chi tiet tin bai")
							 ->setKeywords("Danh sach chi tiet tin bai")
							 ->setCategory("Danh sach chi tiet tin bai");
							 
$objPHPExcel->getActiveSheet(0)->setTitle('ThongKe');

$objPHPExcel->getDefaultStyle()->applyFromArray($styleArray);

$sheet = $objPHPExcel->getActiveSheet(0);
//$sheet -> getRowDimension(1)->setRowHeight(-1);

// Tao tieu de thong ke
$sheet -> setCellValueByColumnAndRow(0, 1, "BCĐ CÁC CHƯƠNG TRÌNH MTQG TỈNH BẾN TRE")
	   -> mergeCells('A1:F1')
	   -> getStyle("A1:F1")->applyFromArray($styleTitle);

$sheet -> setCellValueByColumnAndRow(0, 2, "VĂN PHÒNG ĐIỀU PHỐI CHƯƠNG TRÌNH XD NTM")
	   -> mergeCells('A2:F2')
	   -> getStyle("A2:F2")->applyFromArray($styleTitle);

$sheet -> setCellValueByColumnAndRow(9, 1, "CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM")
	   -> mergeCells('J1:R1')
	   -> getStyle("J1:R1")->applyFromArray($styleTitle);

$sheet -> setCellValueByColumnAndRow(9, 2, "Độc lập - Tự do - Hạnh phúc")
	   -> mergeCells('J2:R2')
	   -> getStyle("J2:R2")->applyFromArray($styleTitle);
	   
$sheet -> setCellValueByColumnAndRow(0, 4, "DANH SÁCH CHI TIẾT TIN, BÀI CỔNG THÔNG TIN ĐIỆN TỬ\nNÔNG THÔN MỚI TỈNH BẾN TRE THÁNG ".$startDate)
	   -> mergeCells('A4:R4')
	   -> getStyle("A4:R4")->applyFromArray($styleTitle1);
$sheet -> getStyle('A4:R4')->getAlignment()->setWrapText(true);
$sheet -> getRowDimension('4')->setRowHeight(50);	   

// ket thuc
$sheet -> setCellValueByColumnAndRow(0, 5, "STT")
	   -> getStyle("A5:R5")->applyFromArray($styleTitle);
$sheet -> getStyle("A5:R5")->getAlignment()->setWrapText(true);
$sheet->getColumnDimension('A')->setWidth(6);

$sheet -> setCellValueByColumnAndRow(1, 5, "Họ và Tên");
$sheet->getColumnDimension('B')->setWidth(15);

$sheet -> setCellValueByColumnAndRow(2, 5, "Tác giả");
$sheet->getColumnDimension('C')->setWidth(10);

$sheet -> setCellValueByColumnAndRow(3, 5, "Tiêu đề");
$sheet->getColumnDimension('D')->setWidth(30);

$sheet -> setCellValueByColumnAndRow(4, 5, "Ngày đăng");
$sheet->getColumnDimension('E')->setWidth(11);

$sheet -> setCellValueByColumnAndRow(5, 5, "Đường dẫn");
$sheet->getColumnDimension('F')->setWidth(15);

$sheet -> setCellValueByColumnAndRow(6, 5, "Độ dài (từ)");
$sheet->getColumnDimension('G')->setWidth(10);

$sheet -> setCellValueByColumnAndRow(7, 5, "Loại tin");
$sheet->getColumnDimension('H')->setWidth(6);

$sheet -> setCellValueByColumnAndRow(8, 5, "Hệ số");
$sheet->getColumnDimension('I')->setWidth(5);

$sheet -> setCellValueByColumnAndRow(9, 5, "Thành tiền");
$sheet->getColumnDimension('J')->setWidth(12);

$sheet -> setCellValueByColumnAndRow(10, 5, "Loại bài");
$sheet->getColumnDimension('K')->setWidth(6);

$sheet -> setCellValueByColumnAndRow(11, 5, "Hệ số");
$sheet->getColumnDimension('L')->setWidth(5);

$sheet -> setCellValueByColumnAndRow(12, 5, "Thành tiền");
$sheet->getColumnDimension('M')->setWidth(12);

$sheet -> setCellValueByColumnAndRow(13, 5, "Loại ảnh");
$sheet->getColumnDimension('N')->setWidth(6);

$sheet -> setCellValueByColumnAndRow(14, 5, "SL");
$sheet->getColumnDimension('O')->setWidth(5);

$sheet -> setCellValueByColumnAndRow(15, 5, "Hệ số");
$sheet->getColumnDimension('P')->setWidth(5);

$sheet -> setCellValueByColumnAndRow(16, 5, "Thành tiền");
$sheet->getColumnDimension('Q')->setWidth(12);

$sheet -> setCellValueByColumnAndRow(17, 5, "Tổng cộng");
$sheet->getColumnDimension('R')->setWidth(12);

$AuthorPeer = new AuthorPeer();
$LoaiBaiVietPeer = new LoaiBaiVietPeer();
$classPeer = new ClassificationPeer();
$imagePeer = new ImagePeer();

// lay du lieu
$arrArticle = $ArticlePeer->getListThongKe1($startDate);
// ket thuc lay du lieu	`
$i=1;
$dong = 6;		
if($startDate!="" && $arrArticle!=false){
	foreach($arrArticle as $Article){
		$Author = $AuthorPeer->getAuthor($Article->get("authorID"));
		$theloai =  $LoaiBaiVietPeer->getLoaiBaiViet($Article->get("loaibaivietID"));
		//echo "bbb=".$Article->get("loaibaivietID");
		$loaitin =  $classPeer->getClassification($Article->get("classID"));					
		$ngaydang = explode(" ",$Article->get("createDate"));
		
		// lay ds anh
		$lstImage = $imagePeer->getListSeleteImageByArticle($Article->get("articleID"));
		$soluongImage = "";
		$hesoImage = "";
		if($lstImage!=false) {
			$soluongImage = count($lstImage);
			$loaianh =  $classPeer->getClassification($lstImage[0]->get("classID"));
			$hesoImage = $loaianh->get("fomulate");
		}
		
		$cot = 1;
	// du lieu report
		$sheet -> getRowDimension($dong)->setRowHeight(-1);
		$sheet -> getStyle('A'.$dong.':R'.$dong)->getAlignment()->setWrapText(true);
		$sheet -> getStyle('A'.$dong.':R'.$dong)->applyFromArray($styleCenter1);
		// xet du lieu stt
		$sheet -> setCellValue(getNameFromNumber($cot).$dong, $i)		   
		   -> getStyle(getNameFromNumber($cot++).$dong)->applyFromArray($styleCenter);
		
		// xet du lieu Ho vaf ten
		$sheet -> setCellValue(getNameFromNumber($cot++).$dong, $Author->get("fullname"));
		
		// xet du lieu tac gia
		$sheet -> setCellValue(getNameFromNumber($cot++).$dong, $Author->get("pseudonym"));
					
		// xet du lieu tieu de
		$sheet -> setCellValue(getNameFromNumber($cot++).$dong, $Article->get("title"));
		
		// xet du lieu ngay dang				
		$sheet -> setCellValue(getNameFromNumber($cot++).$dong, $ngaydang[0]);
		
		// xet du lieu duong dan				
		$sheet -> setCellValue(getNameFromNumber($cot++).$dong, $Article->get("url"));
		
		// xet du lieu do dai tu				
		$sheet -> setCellValue(getNameFromNumber($cot).$dong, $Article->get("numberText"))
			   -> getStyle(getNameFromNumber($cot).$dong)->applyFromArray($styleCenter);	
		$sheet ->getStyle(getNameFromNumber($cot++).$dong)->getNumberFormat()->setFormatCode('#,##0');
		
		if($theloai->get("number")*1 == 250 ) {
		// 250
			// xet du lieu loai tin				
			$sheet -> setCellValue(getNameFromNumber($cot).$dong, $loaitin->get("name"))
				   -> getStyle(getNameFromNumber($cot++).$dong)->applyFromArray($styleCenter);
			
			// xet du lieu he so				
			$sheet -> setCellValue(getNameFromNumber($cot).$dong, $Article->get("heso"))
				   -> getStyle(getNameFromNumber($cot++).$dong)->applyFromArray($styleCenter);
			
			// xet du lieu thanh tien			
			$sheet -> setCellValue(getNameFromNumber($cot).$dong, $Article->get("tongnhuanbut"))
					->getStyle(getNameFromNumber($cot++).$dong)->getNumberFormat()->setFormatCode('#,##0');
					
			$cot+=3;
		}
		else {
			$cot+=3;
			
		// 500 tu	
			// xet du lieu loai tin				
			$sheet -> setCellValue(getNameFromNumber($cot).$dong, $loaitin->get("name"))
					-> getStyle(getNameFromNumber($cot++).$dong)->applyFromArray($styleCenter);
			
			// xet du lieu he so				
			$sheet -> setCellValue(getNameFromNumber($cot).$dong, $Article->get("heso"))
					-> getStyle(getNameFromNumber($cot++).$dong)->applyFromArray($styleCenter);
			
			// xet du lieu thanh tien			
			$sheet -> setCellValue(getNameFromNumber($cot).$dong, $Article->get("tongnhuanbut"))
					->getStyle(getNameFromNumber($cot++).$dong)->getNumberFormat()->setFormatCode('#,##0');
		}
		
	// anh	
		// xet du lieu loai anh		
		$sheet -> setCellValue(getNameFromNumber($cot).$dong, $loaianh->get("name"))
				-> getStyle(getNameFromNumber($cot++).$dong)->applyFromArray($styleCenter);
		
		// xet du lieu so luong anh				
		$sheet -> setCellValue(getNameFromNumber($cot).$dong, $soluongImage)
				-> getStyle(getNameFromNumber($cot++).$dong)->applyFromArray($styleCenter);
		
		// xet du lieu he so				
		$sheet -> setCellValue(getNameFromNumber($cot).$dong, $hesoImage)
				-> getStyle(getNameFromNumber($cot++).$dong)->applyFromArray($styleCenter);
		
		// xet du lieu thanh tien			
		$sheet -> setCellValue(getNameFromNumber($cot).$dong, $Article->get("tongnhuananh"))
				->getStyle(getNameFromNumber($cot++).$dong)->getNumberFormat()->setFormatCode('#,##0');
	// ket anh						
		
		// xet du lieu duong tong nhuan but				
		$sheet -> setCellValue(getNameFromNumber($cot).$dong, ($Article->get("tongnhuanbut")+$Article->get("tongnhuananh")))
				->getStyle(getNameFromNumber($cot++).$dong)->getNumberFormat()->setFormatCode('#,##0');
		
		$dong++;
		$i++;
	}
}
// tong cong

$sheet -> setCellValueByColumnAndRow(2, $dong, "Tổng cộng")
	   -> mergeCells('C'.$dong.':D'.$dong)
	   -> getStyle('C'.$dong.':D'.$dong)->applyFromArray($styleTitle);

//=SUM(G6:G57)
$sheet -> setCellValue(getNameFromNumber(7).$dong, "=SUM(".getNameFromNumber(7)."6:".getNameFromNumber(7).($dong-1).")" )
		->getStyle(getNameFromNumber(7).$dong)->getNumberFormat()->setFormatCode('#,##0'); 
$sheet -> setCellValue(getNameFromNumber(9).$dong, "=SUM(".getNameFromNumber(9)."6:".getNameFromNumber(9).($dong-1).")" );		
$sheet -> setCellValue(getNameFromNumber(10).$dong, "=SUM(".getNameFromNumber(10)."6:".getNameFromNumber(10).($dong-1).")" )
		->getStyle(getNameFromNumber(10).$dong)->getNumberFormat()->setFormatCode('#,##0');
$sheet -> setCellValue(getNameFromNumber(12).$dong, "=SUM(".getNameFromNumber(12)."6:".getNameFromNumber(12).($dong-1).")" )	
		->getStyle(getNameFromNumber(12).$dong)->getNumberFormat()->setFormatCode('#,##0');
$sheet -> setCellValue(getNameFromNumber(13).$dong, "=SUM(".getNameFromNumber(13)."6:".getNameFromNumber(13).($dong-1).")" )
		->getStyle(getNameFromNumber(13).$dong)->getNumberFormat()->setFormatCode('#,##0');
$sheet -> setCellValue(getNameFromNumber(15).$dong, "=SUM(".getNameFromNumber(15)."6:".getNameFromNumber(15).($dong-1).")" )
		->getStyle(getNameFromNumber(15).$dong)->getNumberFormat()->setFormatCode('#,##0');
$sheet -> setCellValue(getNameFromNumber(17).$dong, "=SUM(".getNameFromNumber(17)."6:".getNameFromNumber(17).($dong-1).")" )
		->getStyle(getNameFromNumber(17).$dong)->getNumberFormat()->setFormatCode('#,##0');
$sheet -> setCellValue(getNameFromNumber(18).$dong, "=SUM(".getNameFromNumber(18)."6:".getNameFromNumber(18).($dong-1).")" )
		->getStyle(getNameFromNumber(18).$dong)->getNumberFormat()->setFormatCode('#,##0');
	   
$sheet -> getStyle("A5:R".$dong)->applyFromArray($styleBorder);
$sheet -> getStyle("A5:R".$dong)->getAlignment()->setWrapText(true);

// header report
$arrayData = explode("/",$startDate);
$sheet -> setCellValueByColumnAndRow(13, $dong+1, "Bến Tre, ngày      tháng ".$arrayData[0]." năm ".$arrayData[1])
	   -> mergeCells('M'.($dong+1).':R'.($dong+1))
	   -> getStyle('M'.($dong+1).':R'.($dong+1))->applyFromArray($styleCenter2);

$sheet -> setCellValueByColumnAndRow(0, $dong+2, "Người lập biểu và duyệt nội dung ")
	   -> mergeCells('A'.($dong+2).':D'.($dong+2))
	   -> getStyle('A'.($dong+2).':D'.($dong+2))->applyFromArray($styleCenter3);

$sheet -> setCellValueByColumnAndRow(6, $dong+2, "Kế toán")
	   -> mergeCells('G'.($dong+2).':I'.($dong+2))
	   -> getStyle('G'.($dong+2).':I'.($dong+2))->applyFromArray($styleCenter3);

$sheet -> setCellValueByColumnAndRow(12, $dong+2, "Thủ trưởng đơn vị")
	   -> mergeCells('M'.($dong+2).':R'.($dong+2))
	   -> getStyle('M'.($dong+2).':R'.($dong+2))->applyFromArray($styleCenter3);

// Ten nguoi ky
$sheet -> setCellValueByColumnAndRow(0, $dong+7, "Trần Văn Thanh")
	   -> mergeCells('A'.($dong+7).':D'.($dong+7))
	   -> getStyle('A'.($dong+7).':D'.($dong+7))->applyFromArray($styleCenter3);

$sheet -> setCellValueByColumnAndRow(6, $dong+7, "Lê Văn Nam")
	   -> mergeCells('G'.($dong+7).':I'.($dong+7))
	   -> getStyle('G'.($dong+7).':I'.($dong+7))->applyFromArray($styleCenter3);

$sheet -> setCellValueByColumnAndRow(12, $dong+7, "Cao Minh Đức")
	   -> mergeCells('M'.($dong+7).':R'.($dong+7))
	   -> getStyle('M'.($dong+7).':R'.($dong+7))->applyFromArray($styleCenter3);
	   

$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
//$objWriter->save("report.xlsx");

//xuat file excel
ob_end_clean();

header("Content-Type: application/vnd.vnd.ms-excel"); 
header("Expires: 0"); 
header("Cache-Control: must-revalidate, post-check=0, pre-check=0"); 
header("content-disposition: attachment;filename=ds-chitiettiennhuanbut-".$startDate.".xlsx"); 

$objWriter->save('php://output');

?>