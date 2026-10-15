@echo off
chcp 65001 >nul
set PHP_EXE=C:\Users\poseidon\php\php.exe
cd /d "%~dp0"
echo.
echo  WARNING: this REPLACES the online menu, cafe info and colours with the ones
echo  from this computer's admin panel (http://localhost:9000/admin/).
echo  Changes made in the ONLINE admin will be lost.
echo.
set /p OK= Type YES to continue: 
if /I not "%OK%"=="YES" (echo  Cancelled. & goto :end)

if not exist public-site\.git (
    git clone https://github.com/salehlava/paraf-cafe-menu.git public-site || goto :error
)
git -C public-site pull --ff-only || goto :error
"%PHP_EXE%" build-static.php public-site --data || goto :error
git -C public-site add -A
git -C public-site diff --cached --quiet && (echo  Nothing changed. & goto :end)
git -C public-site commit -m "Replace online menu with the local admin's menu" || goto :error
git -C public-site push || goto :error
echo.
echo  Done! Live in about 1 minute:  https://salehlava.github.io/paraf-cafe-menu/
goto :end
:error
echo.
echo  Something went wrong. Nothing was lost - check the messages above.
:end
echo.
pause
