<?PHP 
require_once("web_src/common/smtp.php");

class SendEmail   
 {			
	function Send($email,$name,$title,$msg){		
		$mail = new SMTP;
	
		$mail->TimeOut(10);
		$mail->Priority('high');
		$mail->From(_EMAIL_, _TITLE_SYS_);
		$mail->AddTo($email, $name);
		
		$mail->Html($msg,"UTF-8");
		
		$send = $mail->Send($title);	
	}
}
?>