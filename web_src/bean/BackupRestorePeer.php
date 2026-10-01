<?PHP

class BackupRestorePeer
{
	var $dbsql;

	function BackupRestorePeer()
	{
		$this->dbsql = new db_mysql;
		$this->dbsql->connect();
		$this->dbsql->selectdb();
	}

	function setLog($chucnang)
	{
		$log = new Log;

		$log->set("chucnang", $chucnang);

		$logPeer = new LogPeer;
		$logPeer->ghiLog($log);
	}

	function backup()
	{
		//open database here
		$tab_status = mysql_query("SHOW TABLE STATUS");
		while ($all = mysql_fetch_assoc($tab_status)) {
			$tbl_stat[$all[Name]] = $all[Auto_increment];
		}
		unset($backup);
		$tables = mysql_list_tables(_DATABASE_NAME_);
		while ($tabs = mysql_fetch_row($tables)) {
			$backup .= "--\n-- Table structure for table  `$tabs[0]`\n--\n\nDROP TABLE IF EXISTS `$tabs[0]`;\nCREATE TABLE IF NOT EXISTS `$tabs[0]` (";
			$res = mysql_query("SHOW CREATE TABLE $tabs[0]");
			while ($all = mysql_fetch_assoc($res)) {
				$str = str_replace("CREATE TABLE `$tabs[0]` (", "", $all['Create Table']);
				// tim UNIQUE KEY

				//$str = str_replace(",", ",&nbsp;", $str);
				$str2 = str_replace("`) ) TYPE=MyISAM ", "`)\n ) TYPE=MyISAM ", $str);
				if ($tbl_stat[$tabs[0]] != "") {
					$backup .= $str2 . " AUTO_INCREMENT=" . $tbl_stat[$tabs[0]] . " ;\n\n";
				} else {
					$backup .= $str2 . ";\n\n";
				}
			}
			$backup .= "--\n-- Dumping data for table  `$tabs[0]`\n--\n\n";
			$data = mysql_query("SELECT * FROM $tabs[0]");

			while ($dt = mysql_fetch_row($data)) {
				$backup .= "INSERT INTO `$tabs[0]` VALUES ('$dt[0]'";
				for ($i = 1; $i < sizeof($dt); $i++) {
					$dt[$i] = addslashes($dt[$i]);
					$dt[$i] = ereg_replace("\n", "\\n", $dt[$i]);
					$backup .= ", '$dt[$i]'";
				}
				$backup .= ");\n";
			}
			$backup .= "\n-- --------------------------------------------------------\n\n";
		}

		$this->setLog("Backup");

		return $backup;
	}

	function restore($filename)
	{
		// Temporary variable, used to store current query
		$templine = '';
		$msg = "";
		// Read in entire file
		$lines = file("restore/" . $filename);
		// Loop through each line
		foreach ($lines as $line) {
			// Skip it if it's a comment
			if (substr($line, 0, 2) == '--' || $line == '')
				continue;

			// Add this line to the current segment
			$templine .= $line;
			// If it has a semicolon at the end, it's the end of the query
			if (substr(trim($line), -1, 1) == ';') {
				// Perform the query
				$result = mysql_query($templine);
				if (!$result) {
					$msg = 'Lỗi thực thi câu truy vấn\'<strong>' . $templine . '\': ' . mysql_error();
					break;
				}
				// Reset temp variable to empty
				$templine = '';
			}
		}
		if ($msg == "") {
			$msg = "Phục hồi thành công. Kiểm tra lại dữ liệu nếu có vấn đề hãy liên hệ admin.";
		}

		$this->setLog("Restore");

		return $msg;
	}
}
?>