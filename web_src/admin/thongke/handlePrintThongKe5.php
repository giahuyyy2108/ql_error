<?PHP	
ini_set('display_errors', TRUE);
ini_set('display_startup_errors', TRUE);

date_default_timezone_set('Asia/Saigon');

define('EOL',(PHP_SAPI == 'cli') ? PHP_EOL : '<br />');

/** Include PHPExcel */
require_once 'web_src/common/PHPExcel.php';

require_once("web_src/bean/AuthorPeer.php");

$startDate = $request->getParameter("startDate");
$fromDate = $request->getParameter("fromDate");

if($startDate=="" || $fromDate==""){
	echo "Dữ liệu không hợp lệ";
	exit();
}
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
//kiem tra tu ngay den ngay
$date1 = explode("/",$startDate);
$date2 = explode("/",$fromDate);

$thang1 = (int)$date1[0];
$nam1= (int)$date1[1];
$thang2 = (int)$date2[0];
$nam2= (int)$date2[1];

if($nam2>$nam1) $thang2+=12;

$sothang = ($thang2-$thang1)+1;

$error = false;

if($thang1 > $thang2){
	echo "Dữ liệu không hợp lệ";
	exit();
}

// khoi tao css
$styleArray = array(
	'font'    => array(
		'size' => 12,
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
		'size' => 12,
		'name'  => 'Times New Roman'
	)	
);
$styleTitle2 = array(
	'alignment' => array(
		'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_LEFT,
		'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER	
	),
	'font' => array(
		'bold' => true,
		'size' => 12,
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
		'size' => 12,
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
		'size' => 12,
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
		'size' => 12,
		'name'  => 'Times New Roman'
	)				
);

$styleBorder = array(
	'borders' => array(
		'allborders' => array(
		  'style' => PHPExcel_Style_Border::BORDER_THIN
		)
	),
	'alignment' => array(			
		'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER	
	)
);
// ket thuc khoi tao

// Create new PHPExcel object
//echo date('H:i:s') , " Create new PHPExcel object" , EOL;
$objPHPExcel = new PHPExcel();

// Set document properties
$objPHPExcel->getProperties()->setCreator("Tran Viet Khoi")
							 ->setLastModifiedBy("Tran Viet Khoi")
							 ->setTitle("Danh sach chi boi duong ban bien tap")
							 ->setSubject("Danh sach chi boi duong ban bien tap")
							 ->setDescription("Danh sach chi boi duong ban bien tap")
							 ->setKeywords("Danh sach chi boi duong ban bien tap")
							 ->setCategory("Danh sach chi boi duong ban bien tap");
							 
$objPHPExcel->getActiveSheet(0)->setTitle('ThongKe');

$objPHPExcel->getDefaultStyle()->applyFromArray($styleArray);

$sheet = $objPHPExcel->getActiveSheet(0);
//$sheet -> getRowDimension(1)->setRowHeight(-1);

// Tao tieu de thong ke
$sheet -> setCellValueByColumnAndRow(0, 1, "ĐƠN VỊ: VĂN PHÒNG ĐIỀU PHỐI CHƯƠNG TRÌNH XÂY DỰNG NÔNG THÔN MỚI")
	   -> mergeCells('A1:'.getNameFromNumber($sothang+5).'1')
	   -> getStyle('A1:'.getNameFromNumber($sothang+5).'1')->applyFromArray($styleTitle2);

$sheet -> setCellValueByColumnAndRow(0, 2, "DANH SÁCH CHI BOI DƯỠNG BAN BIÊN TẬP VÀ TRẢ NHUẬN BÚT CỘNG TÁC VIÊN\nCỔNG THÔNG TIN ĐIỆN TỬ NÔNG THÔN MỚI TỈNH TỪ THÁNG ". $startDate." ĐẾN THÁNG ".$fromDate)
	   -> mergeCells('A2:'.getNameFromNumber($sothang+5).'2')
	   -> getStyle('A2:'.getNameFromNumber($sothang+5).'2')->applyFromArray($styleTitle);
$sheet -> getStyle('A2:'.getNameFromNumber($sothang+5).'2')->getAlignment()->setWrapText(true);
$sheet -> getRowDimension('2')->setRowHeight(50);

$sheet -> setCellValueByColumnAndRow(0, 3, "Nguồn : 0212 (NTM)")
	   -> mergeCells('A3:'.getNameFromNumber($sothang+5).'3')
	   -> getStyle('A3:'.getNameFromNumber($sothang+5).'3')->applyFromArray($styleTitle);

	   
// ket thuc
$sheet -> getStyle('A5:'.getNameFromNumber($sothang+5).'5')->getAlignment()->setWrapText(true);
$sheet -> getStyle('A5:'.getNameFromNumber($sothang+5).'5')->applyFromArray($styleTitle);

$sheet -> setCellValueByColumnAndRow(0, 5, "STT")	   
	   ->getColumnDimension('A')->setWidth(7);
$sheet -> setCellValueByColumnAndRow(0, 6, "A");	   

$sheet -> setCellValueByColumnAndRow(1, 5, "Họ và Tên")	  
	   ->getColumnDimension('B')->setWidth(22);
$sheet -> setCellValueByColumnAndRow(1, 6, "B");	   

$sheet -> setCellValueByColumnAndRow(2, 5, "Diễn giải")
	   ->getColumnDimension('C')->setWidth(30);
$sheet -> setCellValueByColumnAndRow(2, 6, "C");
	   
$j=3;
for($i=$thang1;$i<=$thang2;$i++){
	$thangnam = $i;
	if($i>12){
		$thangnam = ($i-12);
	}							
$sheet -> setCellValueByColumnAndRow($j, 5, "Tháng ".$thangnam)
	   -> getColumnDimension(getNameFromNumber($j+1))->setWidth(15);
$sheet -> setCellValueByColumnAndRow($j, 6, ($j-2));

	$j++;	
}
   
$sheet -> setCellValueByColumnAndRow($j, 5, "Tổng cộng");
$sheet->getColumnDimension(getNameFromNumber($j+1))->setWidth(15);
$sheet -> setCellValueByColumnAndRow($j, 6, ($j-2));
$j++;
$sheet -> setCellValueByColumnAndRow($j, 5, "Ghi chú");
$sheet->getColumnDimension(getNameFromNumber($j+1))->setWidth(30);

$sheet -> getStyle('A6:'.getNameFromNumber($sothang+5).'6')->applyFromArray($styleTitle);


$sheet -> setCellValueByColumnAndRow(0, 7, "I");
$sheet 	-> setCellValueByColumnAndRow(1, 7, "Chi bồi dưỡng ban biên tập")
		-> mergeCells('B7:C7');

$sheet -> getStyle('A7:'.getNameFromNumber($sothang+5).'7')->applyFromArray($styleTitle);

$ArticlePeer = new ArticlePeer();
$k=0;
for($i=$thang1;$i<=$thang2;$i++){
	$thangnam = $i."/".$nam1;
	if($i>12){
		$thangnam = ($i-12)."/".$nam2;
	}	
	$listTong[$k++] = $ArticlePeer->getSumThongKe1($thangnam);		
}

$arrayData = explode(";;",_BTV_);						
		
if($arrayData!=false){				
	$dong = 8;
	$stt=1;
	foreach($arrayData as $data){
		$bt = explode(";",$data);
		if($bt!=false){
			$sheet 	-> setCellValue('A'.$dong, $stt);
			$sheet 	-> setCellValue('B'.$dong, $bt[0]);
			$sheet 	-> setCellValue('C'.$dong, "Chi bồi dưỡng ban biên tập từ tháng ".$startDate." đến tháng ".$fromDate);
			
			$c=4;
			foreach($listTong as $tong){
				$tongbientap = $tong*_BBT_/100;
				// Quy du phong
				$quyduphong = $tongbientap * _QUY_DU_PHONG_/100;
				$tientruquy = $tongbientap - $quyduphong;
				
				$bientap = $tientruquy * $bt[2]/100;
				
				$sheet 	-> setCellValue(getNameFromNumber($c).$dong, $bientap);
				$sheet	->getStyle(getNameFromNumber($c).$dong)->getNumberFormat()->setFormatCode('#,##0');
				$c++;	
			}
			$sheet 	-> setCellValue(getNameFromNumber($c).$dong, "=SUM(".getNameFromNumber(4).$dong.":".getNameFromNumber($c-1).$dong.")");		
			$sheet 	->getStyle(getNameFromNumber($c).$dong)->getNumberFormat()->setFormatCode('#,##0');
			$dong++;
			$stt++;
		}
	}
	
	$sheet 	-> setCellValue('A'.$dong, $stt++);
	$sheet 	-> setCellValue('B'.$dong, _DUNG_TEN_DU_PHONG);
	$sheet 	-> setCellValue('C'.$dong, "Chi trích "._QUY_DU_PHONG_."% quỹ dự phòng từ tháng ".$startDate." đến tháng ".$fromDate);
	
	$c=4;
	foreach($listTong as $tong){
		$tongbientap = $tong*_BBT_/100;
		// Quy du phong
		$quyduphong = $tongbientap * _QUY_DU_PHONG_/100;
				
		$sheet 	-> setCellValue(getNameFromNumber($c).$dong, $quyduphong);
		$sheet	->getStyle(getNameFromNumber($c).$dong)->getNumberFormat()->setFormatCode('#,##0');
		$c++;	
	}
	$sheet 	-> setCellValue(getNameFromNumber($c).$dong, "=SUM(".getNameFromNumber(4).$dong.":".getNameFromNumber($c-1).$dong.")");		
	$sheet 	->getStyle(getNameFromNumber($c).$dong)->getNumberFormat()->setFormatCode('#,##0');
	$dong++;	
}

$j=4;
for($i=$thang1;$i<=$thang2;$i++){
	$sheet 	-> setCellValue(getNameFromNumber($j).'7', "=SUM(".getNameFromNumber($j)."8:".getNameFromNumber($j).($dong-1).")");		
	$sheet 	->getStyle(getNameFromNumber($j).'7')->getNumberFormat()->setFormatCode('#,##0');	

	$j++;	
}

$sheet 	-> setCellValue(getNameFromNumber($j).'7', '=SUM('.getNameFromNumber($j).'8:'.getNameFromNumber($j).($dong-1).')');		
$sheet 	->getStyle(getNameFromNumber($j).'7')->getNumberFormat()->setFormatCode('#,##0');			

// Cong tac vien
$dongctv = $dong;
$sheet -> setCellValueByColumnAndRow(0, $dong, "II");
$sheet 	-> setCellValueByColumnAndRow(1, $dong, "Chi trả nhuận bút cho cộng tác viên")
		-> mergeCells('B'.$dong.':C'.$dong);

$sheet -> getStyle('A'.$dong.':'.getNameFromNumber($sothang+5).$dong++)->applyFromArray($styleTitle);

$AuthorPeer = new AuthorPeer();
$arrAuthor = $AuthorPeer->getListAuthor();
		
if($arrAuthor!=false){				
	//$dong = 7;
	//$stt=1;
	foreach($arrAuthor as $Author){		
		$sheet 	-> setCellValue('A'.$dong, $stt);
		$sheet 	-> setCellValue('B'.$dong, $Author->get("fullname"));
		$sheet 	-> setCellValue('C'.$dong, "Chi trả nhuận bút công tác viên từ tháng ".$startDate." đến tháng ".$fromDate);
		
		$c=4;
		for($i=$thang1;$i<=$thang2;$i++){
			$nhuanbut = 0;
			$thangnam = $i."/".$nam1;
			if($i>12){
				$thangnam = ($i-12)."/".$nam2;
			}				
			// lay ds bai viet theo tac gia
			$arrArticle = $ArticlePeer->getListThongKe2($thangnam,$Author->get("authorID"));
			// tinh nhuan but cua tac gia theo thang
			if($arrArticle!=false){
				foreach($arrArticle as $article){
					$nhuanbut +=$article->get("tongnhuanbut")+$article->get("tongnhuananh");						
				}
			}
			
			$sheet 	-> setCellValue(getNameFromNumber($c).$dong, $nhuanbut);
			$sheet	->getStyle(getNameFromNumber($c).$dong)->getNumberFormat()->setFormatCode('#,##0');
			$c++;	
		}
		$sheet 	-> setCellValue(getNameFromNumber($c).$dong, "=SUM(".getNameFromNumber(4).$dong.":".getNameFromNumber($c-1).$dong.")");		
		$sheet 	->getStyle(getNameFromNumber($c).$dong)->getNumberFormat()->setFormatCode('#,##0');
		$dong++;
		$stt++;		
	}
	
	$j=4;
	for($i=$thang1;$i<=$thang2;$i++){
		$sheet 	-> setCellValue(getNameFromNumber($j).$dongctv, "=SUM(".getNameFromNumber($j).($dongctv+1).":".getNameFromNumber($j).($dong-1).")");		
		$sheet 	->getStyle(getNameFromNumber($j).$dongctv)->getNumberFormat()->setFormatCode('#,##0');	

		$j++;	
	}

	$sheet 	-> setCellValue(getNameFromNumber($j).$dongctv, '=SUM('.getNameFromNumber($j).($dongctv+1).':'.getNameFromNumber($j).($dong-1).')');	
	$sheet 	->getStyle(getNameFromNumber($j).$dongctv)->getNumberFormat()->setFormatCode('#,##0');
}

	
$sheet 	-> setCellValueByColumnAndRow(0, $dong, "Tổng Cộng")
		-> mergeCells('A'.$dong.':C'.$dong)
		-> getStyle('A'.$dong.':C'.$dong)->applyFromArray($styleTitle);

$j=4;
for($i=$thang1;$i<=$thang2;$i++){
	$sheet 	-> setCellValue(getNameFromNumber($j).$dong, "=".getNameFromNumber($j)."7 +".getNameFromNumber($j).($dongctv))
			-> getStyle(getNameFromNumber($j).$dong)->applyFromArray($styleTitle);
	$sheet 	->getStyle(getNameFromNumber($j).$dong)->getNumberFormat()->setFormatCode('#,##0');	

	$j++;	
}

$sheet 	-> setCellValue(getNameFromNumber($j).$dong, "=".getNameFromNumber($j)."7 + ".getNameFromNumber($j).($dongctv))
		-> getStyle(getNameFromNumber($j).$dong)->applyFromArray($styleTitle);
		
$sheet 	->getStyle(getNameFromNumber($j).$dong)->getNumberFormat()->setFormatCode('#,##0');			

//dong khung
$sheet -> getStyle("A5:".getNameFromNumber($j+1).$dong)->applyFromArray($styleBorder);
$sheet -> getStyle("A5:".getNameFromNumber($j+1).$dong)->getAlignment()->setWrapText(true);

// header report
$arrayData = explode("/",$fromDate);

$sheet -> setCellValueByColumnAndRow(($sothang+5)-3, $dong+2, "Bến Tre, ngày      tháng ".$arrayData[0]." năm ".$arrayData[1])
	   -> mergeCells(getNameFromNumber(($sothang+5)-2).($dong+2).':'.getNameFromNumber($sothang+5).($dong+2))
	   -> getStyle(getNameFromNumber(($sothang+5)-2).($dong+2).':'.getNameFromNumber($sothang+5).($dong+2))->applyFromArray($styleCenter2);

$sheet -> setCellValueByColumnAndRow(1, $dong+3, "Kế toán trưởng")   
	   -> getStyle('B'.($dong+3))->applyFromArray($styleCenter3);	
	   
$sheet -> setCellValueByColumnAndRow(($sothang+5)-3, $dong+3, "Thủ trưởng đơn vị")
	   -> mergeCells(getNameFromNumber(($sothang+5)-2).($dong+3).':'.getNameFromNumber($sothang+5).($dong+3))
	   -> getStyle(getNameFromNumber(($sothang+5)-2).($dong+3).':'.getNameFromNumber($sothang+5).($dong+3))->applyFromArray($styleCenter3);


// Ten nguoi ky
$sheet -> setCellValueByColumnAndRow(1, $dong+8, "Lê Văn Nam")	   
	   -> getStyle('B'.($dong+8))->applyFromArray($styleCenter3);

$sheet -> setCellValueByColumnAndRow(($sothang+5)-3, $dong+8, "Võ Sĩ Tiến")
	   -> mergeCells(getNameFromNumber(($sothang+5)-2).($dong+8).':'.getNameFromNumber($sothang+5).($dong+8))
	   -> getStyle(getNameFromNumber(($sothang+5)-2).($dong+8).':'.getNameFromNumber($sothang+5).($dong+8))->applyFromArray($styleCenter3);
  
$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
//$objWriter->save("report.xlsx");

//xuat file excel
ob_end_clean();

header("Content-Type: application/vnd.vnd.ms-excel"); 
header("Expires: 0"); 
header("Cache-Control: must-revalidate, post-check=0, pre-check=0"); 
header("content-disposition: attachment;filename=ds-chitraboiduong-".$startDate."-".$fromDate."-.xlsx"); 

$objWriter->save('php://output');
?>