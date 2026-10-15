@echo off
chcp 65001 >nul
set PHP_EXE=C:\Users\poseidon\php\php.exe
cd /d "%~dp0"
echo.
echo  Updating the online site's pages and design (your online menu edits are kept).

if not exist public-site\.git (
    git clone https://github.com/salehlava/paraf-cafe-menu.git public-site || goto :error
)
git -C public-site pull --ff-only || goto :error
"%PHP_EXE%" build-static.php public-site || goto :error
git -C public-site add -A
git -C public-site diff --cached --quiet && (echo  Nothing changed. & goto :end)
git -C public-site commit -m "Update online site" || goto :error
git -C public-site push || goto :error
echo.
echo  Published! Live in about 1 minute:  https://salehlava.github.io/paraf-cafe-menu/
goto :end
:error
echo.
echo  Something went wrong. Nothing was lost - check the messages above.
:end
echo.
pause
