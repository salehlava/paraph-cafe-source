@echo off
set PHP_EXE=C:\Users\poseidon\php\php.exe
cd /d "%~dp0"

rem If the site is already running (start.bat), just open the admin panel.
netstat -ano | findstr /R /C:"127.0.0.1:9000 .*LISTENING" /C:"\[::1\]:9000 .*LISTENING" >nul
if %errorlevel%==0 (
    echo Server is already running. Opening admin panel...
    start "" http://localhost:9000/admin/
    exit /b
)

echo Starting Paraph Cafe Admin Panel at http://localhost:9000/admin/
start "" http://localhost:9000/admin/
"%PHP_EXE%" -d extension=curl -d extension=openssl -d extension=mbstring -S localhost:9000 router.php
