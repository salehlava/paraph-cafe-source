@echo off
set PHP_EXE=C:\Users\poseidon\php\php.exe
cd /d "%~dp0"
echo Starting Paraf Cafe Menu at http://localhost:9000
echo Admin panel:                http://localhost:9000/admin/
start "" http://localhost:9000
"%PHP_EXE%" -d extension=curl -d extension=openssl -d extension=mbstring -S localhost:9000 router.php
