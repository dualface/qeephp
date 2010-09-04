@echo off
setlocal
set SCRIPT_DIR=%~dp0
php %SCRIPT_DIR%lib\cligen_app.php %CD% %*

