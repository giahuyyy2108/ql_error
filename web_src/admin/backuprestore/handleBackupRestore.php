<?PHP
require("web_src/bean/BackupRestorePeer.php");
include ('web_src/common/Uploader.php');

// lay ra danh sach mon hoc
$backupRestorePeer = new BackupRestorePeer;


$function = $request->getParameter("function");

switch($function){
	case 'backup':
		$backup = $backupRestorePeer->backup();
		$backupFile = $db_name . date("Y-m-d-H-i-s") . '.sql';
		
		//xuat file
		ob_end_clean();

		header("Content-Type: application/vnd.ms-word"); 
		header("Expires: 0"); 
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0"); 
		header("content-disposition: attachment;filename=".$backupFile); 
		
		echo $backup;
		
		break;
	case 'restore':
		$target_dir = "restore/";
		$target_file = $target_dir . basename($_FILES["file"]["name"]);
		$uploadOk = 1;
		$imageType = pathinfo($target_file,PATHINFO_EXTENSION);

		$msg = "";
		$br = "";
		// Check file size
		if ($_FILES["file"]["size"] > _BACKUP_SIZE_) {
			$msg.= $br."File phục hồi lớn hơn ".(_BACKUP_SIZE_/1024/1024) ."MB. Yêu cầu chọn file khác hoặc liên hệ admin !";			
			$br = "</br>";
			$uploadOk = 0;
		}
		// Allow certain file formats
		if($imageType != "sql") {
			$msg.= $br."File không đúng kiểu .xls hoặc .xlsx. Yêu cầu kiểm tra lại !";			
			$br = "</br>";
			$uploadOk = 0;
		}
		// Check if $uploadOk is set to 0 by an error
		if ($uploadOk == 0) {
			$msg.= $br. "File không thể upload.";
			$br = "</br>";
		// if everything is ok, try to upload file
		} else {
			if (move_uploaded_file($_FILES["file"]["tmp_name"], $target_file)) {
				$msg.= $br."File ". basename( $_FILES["file"]["name"]). " đã được upload.";			
				$br = "</br>";		
			} else {
				$msg.= $br."File không thể upload.";			
				$br = "</br>";
				$uploadOk = 0;
			}
		}
		
		// tien hanh phuc hoi
		if($uploadOk!=0){
			// tien hanh backup lai truoc khi phuc hoi
			$target_dir = "restore";
			$targetPath = dirname(dirname(dirname(dirname(__FILE__)))) . DIRECTORY_SEPARATOR . $target_dir . DIRECTORY_SEPARATOR;
			
			$backup = $backupRestorePeer->backup();
			$backupFile = $targetPath.$db_name . date("Y-m-d-H-i-s") . '.sql';
			
			$fBackup = fopen($backupFile, "w") or die("Unable to open file!");
			
			fwrite($fBackup, $backup);			
			fclose($fBackup);			
			
			// tien hanh phuc hoi
			$restore = $backupRestorePeer->restore($_FILES["file"]["name"]);
			$msg.= $br.$restore;
		}
		include("./web_src/admin/backuprestore/manageBackupRestore.php");
		$request->setAttribute("msg",$msg);
		
		break;
}
?>