@echo off
chcp 65001 >nul
set PHP_EXE=C:\Users\poseidon\php\php.exe
cd /d "%~dp0"
echo.
echo  Reset the admin username and password.
set "NEWUSER="
set /p NEWUSER= Username (press Enter for "admin"): 
if "%NEWUSER%"=="" set "NEWUSER=admin"
"%PHP_EXE%" lib\reset_admin.php "%NEWUSER%"
pause
