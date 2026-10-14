@echo off
chcp 65001 >nul
set PHP_EXE=C:\Users\poseidon\php\php.exe
cd /d "%~dp0"
title Paraph Cafe - ONLINE link (close this window to go offline)

rem A separate server on port 9001 for the public link. TRUST_PROXY=1 makes the site read each
rem visitor's real IP from the tunnel, so internet visitors are never treated as "local".
set TRUST_PROXY=1
start "Paraph tunnel server (port 9001)" /min "%PHP_EXE%" -d extension=curl -d extension=openssl -d extension=mbstring -S 127.0.0.1:9001 router.php

echo.
echo  Creating a public link (free service: localhost.run)...
echo  Look below for a line ending in  .lhr.life  - that is your link.
echo.
echo      Admin panel  =  that link + /admin/
echo.
echo  Keep this window open. Close it to take the link offline.
echo.
ssh -o StrictHostKeyChecking=accept-new -o ServerAliveInterval=30 -R 80:127.0.0.1:9001 nokey@localhost.run

rem Link closed: stop the tunnel server too
taskkill /FI "WINDOWTITLE eq Paraph tunnel server (port 9001)*" /F >nul 2>&1
