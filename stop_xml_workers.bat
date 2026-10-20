@echo off
echo Dang tat XML workers...

powershell -NoProfile -ExecutionPolicy Bypass -Command "Get-CimInstance Win32_Process -Filter ^"Name = 'php.exe'^" | Where-Object { $_.CommandLine -match 'watch_xml\.php' } | ForEach-Object { Stop-Process -Id $_.ProcessId -Force -ErrorAction SilentlyContinue }"
powershell -NoProfile -ExecutionPolicy Bypass -Command "Get-Process WatcherLauncher -ErrorAction SilentlyContinue | Stop-Process -Force -ErrorAction SilentlyContinue"

echo Da tat tat ca XML workers.
pause
