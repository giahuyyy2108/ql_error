Chạy worker từ PowerShell hoặc CMD:
cd C:\xampp\htdocs\ql_error
C:\xampp\php\php.exe scripts\watch_xml.php
Worker sẽ chạy liên tục và theo dõi thư mục:
C:\xampp\htdocs\ql_error\storage\xml
Chỉ quét một lần rồi thoát:
C:\xampp\php\php.exe scripts\watch_xml.php --once
Chạy ẩn trên Windows:
Start-Process `
  -FilePath "C:\xampp\php\php.exe" `
  -ArgumentList "scripts\watch_xml.php" `
  -WorkingDirectory "C:\xampp\htdocs\ql_error" `
  -WindowStyle Hidden
Kiểm tra worker đang chạy:
Get-CimInstance Win32_Process |
  Where-Object { $_.CommandLine -like "*watch_xml.php*" } |
  Select-Object ProcessId, CommandLine
Theo dõi log:
Get-Content C:\xampp\htdocs\ql_error\storage\xml\watcher.log -Wait
Nếu báo “Đã có một XML watcher khác đang chạy”, nghĩa là worker đã hoạt động; không cần chạy thêm tiến trình thứ hai.