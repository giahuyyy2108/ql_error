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
							 ->setTitle("Danh sach chi tra thu lao")
							 ->setSubject("Danh sach chi tra thu lao")
							 ->setDescription("Danh sach chi tra thu lao")
							 ->setKeywords("Danh sach chi tra thu lao")
							 ->setCategory("Danh sach chi tra thu lao");
							 
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

$sheet -> setCellValueByColumnAndRow(8, 1, "CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM")
	   -> mergeCells('I1:N1')
	   -> getStyle("I1:N1")->applyFromArray($styleTitle);

$sheet -> setCellValueByColumnAndRow(8, 2, "Độc lập - Tự do - Hạnh phúc")
	   -> mergeCells('I2:N2')
	   -> getStyle("I2:N2")->applyFromArray($styleTitle);
	   
$sheet -> setCellValueByColumnAndRow(0, 4, "DANH SÁCH CHI TIẾT TIN,DANH SÁCH CHI TRẢ THÙ LAO BAN BIÊN TẬP VÀ NHUẬN BÚT\nCỔNG THÔNG TIN ĐIỆN TỬ NÔNG THÔN MỚI TỈNH THÁNG ".$startDate)
	   -> mergeCells('A4:N4')
	   -> getStyle("A4:N4")->applyFromArray($styleTitle1);
$sheet -> getStyle('A4:N4')->getAlignment()->setWrapText(true);
$sheet -> getRowDimension('4')->setRowHeight(50);

$sheet -> setCellValueByColumnAndRow(0, 5, "(Thực hiện theo Công văn số 1578/UBND-TCĐT ngày 07 tháng 4 năm 2015\ncủa UBND tỉnh Bến Tre về việc định mức chi huận bút)")
	   -> mergeCells('A5:N5')
	   -> getStyle("A5:N5")->applyFromArray($styleTitle);
$sheet -> getStyle('A5:N5')->getAlignment()->setWrapText(true);
$sheet -> getRowDimension('5')->setRowHeight(50);	
// ket thuc
$sheet -> getStyle("A6:N7")->getAlignment()->setWrapText(true);

$sheet -> setCellValueByColumnAndRow(0, 6, "STT")
	   -> mergeCells('A6:A7')
	   -> getStyle("A6:N7")->applyFromArray($styleTitle);
$sheet->getColumnDimension('A')->setWidth(7);	   

$sheet -> setCellValueByColumnAndRow(1, 6, "Họ và Tên")
	   -> mergeCells('B6:B7');
$sheet->getColumnDimension('B')->setWidth(22);

$sheet -> setCellValueByColumnAndRow(2, 6, "CHỨC VỤ\n/BÚT DANH")
	   -> mergeCells('C6:C7');
$sheet->getColumnDimension('C')->setWidth(20);

$sheet -> setCellValueByColumnAndRow(3, 6, "MÃ NGHẠCH")
	   -> mergeCells('D6:L6');

$sheet -> setCellValueByColumnAndRow(3, 7, "Biên tập");
$sheet->getColumnDimension('D')->setWidth(15);	   
	   
$sheet -> setCellValueByColumnAndRow(4, 7, "Số tin");
$sheet->getColumnDimension('E')->setWidth(10);

$sheet -> setCellValueByColumnAndRow(5, 7, "Thành tiền");
$sheet->getColumnDimension('F')->setWidth(15);

$sheet -> setCellValueByColumnAndRow(6, 7, "Số bài");
$sheet->getColumnDimension('G')->setWidth(10);

$sheet -> setCellValueByColumnAndRow(7, 7, "Thành tiền");
$sheet->getColumnDimension('D')->setWidth(15);	   
	   
$sheet -> setCellValueByColumnAndRow(4, 7, "Số tin");
$sheet->getColumnDimension('E')->setWidth(10);

$sheet -> setCellValueByColumnAndRow(5, 7, "Thành tiền");
$sheet->getColumnDimension('F')->setWidth(15);

$sheet -> setCellValueByColumnAndRow(6, 7, "Số bài");
$sheet->getColumnDimension('G')->setWidth(10);

$sheet -> setCellValueByColumnAndRow(7, 7, "Số ảnh");
$sheet->getColumnDimension('H')->setWidth(10);

$sheet -> setCellValueByColumnAndRow(8, 7, "Số ảnh");
$sheet->getColumnDimension('I')->setWidth(10);

$sheet -> setCellValueByColumnAndRow(9, 7, "Thành tiền");
$sheet->getColumnDimension('J')->setWidth(15);

$sheet -> setCellValueByColumnAndRow(10, 7, "Dựng Web");
$sheet->getColumnDimension('K')->setWidth(10);

$sheet -> setCellValueByColumnAndRow(11, 7, "Nhập liệu và scan văn bản");
$sheet->getColumnDimension('L')->setWidth(10);

$sheet -> setCellValueByColumnAndRow(12, 7, "Tổng cộng");
$sheet->getColumnDimension('M')->setWidth(15);

$sheet -> setCellValueByColumnAndRow(13, 7, "Ký nhận");
$sheet->getColumnDimension('N')->setWidth(20);

for($i=1;$i<14;$i++){
	$sheet -> setCellValueByColumnAndRow($i, 8, $i);
}
$sheet -> getStyle("A8:N8")->applyFromArray($styleTitle);

$ArticlePeer = new ArticlePeer();
$AuthorPeer = new AuthorPeer();
$LoaiBaiVietPeer = new LoaiBaiVietPeer();
$imagePeer = new ImagePeer();
$dong = 9;
if($startDate!=""){			
	// view thong ke
	$tongtien = $ArticlePeer->getSumThongKe1($startDate);
	// lay ds tac gia
	$AuthorPeer = new AuthorPeer();
	$arrAuthor = $AuthorPeer->getListAuthor();	
	if($tongtien>0) {
		$arrayData = explode(";;",_BTV_);
		$tongbientap = $tongtien*_BBT_/100;
		
		$sheet 	-> setCellValue('A'.$dong, "I")
				-> getStyle('A'.$dong)->applyFromArray($styleTitle);
		
		$sheet 	-> setCellValueByColumnAndRow(1, $dong, "Chi bồi dưỡng ban biên tập")
				-> mergeCells('B'.$dong.':C'.$dong)
				-> getStyle('B'.$dong.':C'.$dong)->applyFromArray($styleTitle);
				
		
		$dong++;
		
		$quyduphong = $tongbientap * _QUY_DU_PHONG_/100;
		$tientruquy = $tongbientap - $quyduphong;
		
		$i=1;	
		foreach($arrayData as $data){
			$bt = explode(";",$data);
			if($bt!=false){
				$bientap = $tientruquy * $bt[2]/100;				
				
				$sheet 	-> setCellValue('A'.$dong, $i);
				$sheet 	-> setCellValue('B'.$dong, $bt[0]);
				$sheet 	-> setCellValue('C'.$dong, $bt[1]);
				$sheet 	-> setCellValue('D'.$dong, $bientap);
				$sheet	->getStyle('D'.$dong)->getNumberFormat()->setFormatCode('#,##0');
				
				$sheet 	-> setCellValue('M'.$dong, $bientap)
						->getStyle('M'.$dong)->getNumberFormat()->setFormatCode('#,##0');
				$i++;
				$dong++;
			}
		}
		
		$sheet 	-> setCellValue('A'.$dong, $i);
		$sheet 	-> setCellValueByColumnAndRow(1, $dong, "Trích "._QUY_DU_PHONG_."% quỹ dự phòng")
				-> mergeCells('B'.$dong.':C'.$dong);				
				
		$sheet 	-> setCellValue('D'.$dong, $quyduphong);
		$sheet	->getStyle('D'.$dong)->getNumberFormat()->setFormatCode('#,##0');
		
		$sheet 	-> setCellValue('M'.$dong, $quyduphong)
				->getStyle('M'.$dong)->getNumberFormat()->setFormatCode('#,##0');
				
		$sheet 	-> setCellValue('D9', "=SUM(D10:D".$dong.")")
				-> getStyle('D9')->applyFromArray($styleTitle);	
		$sheet 	->getStyle('D9')->getNumberFormat()->setFormatCode('#,##0'); 
		
		$sheet 	-> setCellValue('M9',  "=SUM(M10:M".$dong.")")
				-> getStyle('M9')->applyFromArray($styleTitle);	
		$sheet 	->getStyle('M9')->getNumberFormat()->setFormatCode('#,##0'); 
		
		$dong++;
	}
		
	if($arrAuthor!=false){	
		$dongchitra = $dong;
		$sheet 	-> setCellValue('A'.$dong, "II")
				-> getStyle('A'.$dong)->applyFromArray($styleTitle);
		
		$sheet 	-> setCellValueByColumnAndRow(1, $dong, "Chi trả nhuận bút cộng tác viên")
				-> mergeCells('B'.$dong.':C'.$dong)
				-> getStyle('B'.$dong.':C'.$dong)->applyFromArray($styleTitle);
		$dong++;
		$i=1;
		foreach($arrAuthor as $Author){
			$tin = 0;
			$bai= 0;
			$anh = 0;
			
			$ntin = 0;
			$nbai = 0;
			$nanh = 0;
			// lay ds bai viet theo tac gia
			$arrArticle = $ArticlePeer->getListThongKe2($startDate,$Author->get("authorID"));
			// tinh nhuan but cua tac gia theo thang
			if($arrArticle!=false){
				foreach($arrArticle as $article){
					$theloai =  $LoaiBaiVietPeer->getLoaiBaiViet($article->get("loaibaivietID"));
					if($theloai->get("number")==250){ // tin
						$tin+= $article->get("tongnhuanbut");
						$ntin++;
					}
					else{ // bai
						$bai+= $article->get("tongnhuanbut");
						$nbai++;
					}
					if($article->get("tongnhuananh")>0) {
						$anh+=$article->get("tongnhuananh");
						// lay so anh cua bai						
						$lstImage = $imagePeer->getListSeleteImageByArticle($article->get("articleID"));						
						if($lstImage!=false) {
							$nanh += count($lstImage);							
						}
					}
				}				
				
				$sheet 	-> setCellValue('A'.$dong, $i);
				$sheet 	-> setCellValue('B'.$dong, $Author->get("fullname"));
				$sheet 	-> setCellValue('C'.$dong, $Author->get("pseudonym"));
				
				$sheet 	-> setCellValue('E'.$dong, $ntin);
				$sheet	->getStyle('E'.$dong)->getNumberFormat()->setFormatCode('#,##0');
				
				$sheet 	-> setCellValue('F'.$dong, $tin);
				$sheet	->getStyle('F'.$dong)->getNumberFormat()->setFormatCode('#,##0');
								
				$sheet 	-> setCellValue('G'.$dong, $nbai);
				$sheet	->getStyle('G'.$dong)->getNumberFormat()->setFormatCode('#,##0');
				
				$sheet 	-> setCellValue('H'.$dong, $bai);
				$sheet	->getStyle('H'.$dong)->getNumberFormat()->setFormatCode('#,##0');
				
				$sheet 	-> setCellValue('I'.$dong, $nanh);
				$sheet	->getStyle('I'.$dong)->getNumberFormat()->setFormatCode('#,##0');
				
				$sheet 	-> setCellValue('J'.$dong, $anh);
				$sheet 	->getStyle('J'.$dong)->getNumberFormat()->setFormatCode('#,##0');
						
				$sheet 	-> setCellValue('M'.$dong, ($tin+$bai+$anh));
				$sheet	->getStyle('M'.$dong)->getNumberFormat()->setFormatCode('#,##0');		
				
				$i++;
				$dong++;
			}
		}
		
		$sheet 	-> setCellValue('E'.$dongchitra, "=SUM(E".($dongchitra+1).":E".($dong-1).")")
				-> getStyle('E'.$dongchitra)->applyFromArray($styleTitle);	
		$sheet 	->getStyle('E'.$dongchitra)->getNumberFormat()->setFormatCode('#,##0'); 
		
		$sheet 	-> setCellValue('F'.$dongchitra, "=SUM(F".($dongchitra+1).":F".($dong-1).")")
				-> getStyle('F'.$dongchitra)->applyFromArray($styleTitle);	
		$sheet 	->getStyle('F'.$dongchitra)->getNumberFormat()->setFormatCode('#,##0');  
		
		$sheet 	-> setCellValue('G'.$dongchitra, "=SUM(G".($dongchitra+1).":G".($dong-1).")")
				-> getStyle('G'.$dongchitra)->applyFromArray($styleTitle);	
		$sheet 	->getStyle('G'.$dongchitra)->getNumberFormat()->setFormatCode('#,##0'); 
		
		$sheet 	-> setCellValue('H'.$dongchitra, "=SUM(H".($dongchitra+1).":H".($dong-1).")")
				-> getStyle('H'.$dongchitra)->applyFromArray($styleTitle);	
		$sheet 	->getStyle('H'.$dongchitra)->getNumberFormat()->setFormatCode('#,##0'); 
		
		$sheet 	-> setCellValue('I'.$dongchitra, "=SUM(I".($dongchitra+1).":I".($dong-1).")")
				-> getStyle('I'.$dongchitra)->applyFromArray($styleTitle);	
		$sheet 	->getStyle('I'.$dongchitra)->getNumberFormat()->setFormatCode('#,##0'); 
		
		$sheet 	-> setCellValue('J'.$dongchitra, "=SUM(J".($dongchitra+1).":J".($dong-1).")")
				-> getStyle('J'.$dongchitra)->applyFromArray($styleTitle);	
		$sheet 	->getStyle('J'.$dongchitra)->getNumberFormat()->setFormatCode('#,##0'); 
		
		$sheet 	-> setCellValue('M'.$dongchitra, "=SUM(M".($dongchitra+1).":M".($dong-1).")")
				-> getStyle('M'.$dongchitra)->applyFromArray($styleTitle);	
		$sheet 	->getStyle('M'.$dongchitra)->getNumberFormat()->setFormatCode('#,##0'); 
	}
	// tong cong
	
	$sheet 	-> setCellValueByColumnAndRow(0, $dong, "Tổng Cộng")
			-> mergeCells('A'.$dong.':C'.$dong)
			-> getStyle('A'.$dong.':C'.$dong)->applyFromArray($styleTitle);	
	
	$sheet 	-> setCellValue('D'.$dong, "=D9")
				-> getStyle('D'.$dong)->applyFromArray($styleTitle);	
	$sheet 	->getStyle('D'.$dong)->getNumberFormat()->setFormatCode('#,##0');
	
	$sheet 	-> setCellValue('E'.$dong, "=E".$dongchitra)
				-> getStyle('E'.$dong)->applyFromArray($styleTitle);	
	$sheet 	->getStyle('E'.$dong)->getNumberFormat()->setFormatCode('#,##0'); 
	
	$sheet 	-> setCellValue('F'.$dong, "=F".$dongchitra)
			-> getStyle('F'.$dong)->applyFromArray($styleTitle);	
	$sheet 	->getStyle('F'.$dong)->getNumberFormat()->setFormatCode('#,##0');  
	
	$sheet 	-> setCellValue('G'.$dong, "=G".$dongchitra)
			-> getStyle('G'.$dong)->applyFromArray($styleTitle);	
	$sheet 	->getStyle('G'.$dong)->getNumberFormat()->setFormatCode('#,##0'); 
	
	$sheet 	-> setCellValue('H'.$dong, "=H".$dongchitra)
			-> getStyle('H'.$dong)->applyFromArray($styleTitle);	
	$sheet 	->getStyle('H'.$dong)->getNumberFormat()->setFormatCode('#,##0'); 
	
	$sheet 	-> setCellValue('I'.$dong, "=I".$dongchitra)
			-> getStyle('I'.$dong)->applyFromArray($styleTitle);	
	$sheet 	->getStyle('I'.$dong)->getNumberFormat()->setFormatCode('#,##0'); 
	
	$sheet 	-> setCellValue('J'.$dong, "=J".$dongchitra)
			-> getStyle('J'.$dong)->applyFromArray($styleTitle);	
	$sheet 	->getStyle('J'.$dong)->getNumberFormat()->setFormatCode('#,##0'); 
	
	$sheet 	-> setCellValue('M'.$dong, "=M9+M".$dongchitra)
			-> getStyle('M'.$dong)->applyFromArray($styleTitle);	
	$sheet 	->getStyle('M'.$dong)->getNumberFormat()->setFormatCode('#,##0');
	
	$sheet -> getStyle("A6:N".$dong)->applyFromArray($styleBorder);
	$sheet -> getStyle("A6:N".$dong)->getAlignment()->setWrapText(true);
}

// header report
$arrayData = explode("/",$startDate);
$sheet -> setCellValueByColumnAndRow(11, $dong+2, "Bến Tre, ngày      tháng ".$arrayData[0]." năm ".$arrayData[1])
	   -> mergeCells('L'.($dong+2).':N'.($dong+2))
	   -> getStyle('L'.($dong+2).':N'.($dong+2))->applyFromArray($styleCenter2);

$sheet -> setCellValueByColumnAndRow(0, $dong+3, "Người lập biểu ")
	   -> mergeCells('A'.($dong+3).':D'.($dong+3))
	   -> getStyle('A'.($dong+3).':D'.($dong+3))->applyFromArray($styleCenter3);

$sheet -> setCellValueByColumnAndRow(6, $dong+3, "Kế toán")
	   -> mergeCells('G'.($dong+3).':I'.($dong+3))
	   -> getStyle('G'.($dong+3).':I'.($dong+3))->applyFromArray($styleCenter3);

$sheet -> setCellValueByColumnAndRow(11, $dong+3, "Thủ trưởng đơn vị")
	   -> mergeCells('L'.($dong+3).':N'.($dong+3))
	   -> getStyle('L'.($dong+3).':N'.($dong+3))->applyFromArray($styleCenter3);

// Ten nguoi ky
$sheet -> setCellValueByColumnAndRow(0, $dong+8, "Trần Văn Thanh")
	   -> mergeCells('A'.($dong+8).':D'.($dong+8))
	   -> getStyle('A'.($dong+8).':D'.($dong+8))->applyFromArray($styleCenter3);

$sheet -> setCellValueByColumnAndRow(6, $dong+8, "Lê Văn Nam")
	   -> mergeCells('G'.($dong+8).':I'.($dong+8))
	   -> getStyle('G'.($dong+8).':I'.($dong+8))->applyFromArray($styleCenter3);

$sheet -> setCellValueByColumnAndRow(11, $dong+8, "Cao Minh Đức")
	   -> mergeCells('L'.($dong+8).':N'.($dong+8))
	   -> getStyle('L'.($dong+8).':N'.($dong+8))->applyFromArray($styleCenter3);
	   

$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
//$objWriter->save("report.xlsx");

//xuat file excel
ob_end_clean();

header("Content-Type: application/vnd.vnd.ms-excel"); 
header("Expires: 0"); 
header("Cache-Control: must-revalidate, post-check=0, pre-check=0"); 
header("content-disposition: attachment;filename=ds-chitrathulao-".$startDate.".xlsx"); 

$objWriter->save('php://output');

?>