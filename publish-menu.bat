@echo off
chcp 65001 >nul
set PHP_EXE=C:\Users\poseidon\php\php.exe
cd /d "%~dp0"
echo.
echo  Building the online menu (docs/) from your local menu...
"%PHP_EXE%" build-static.php || goto :error
git add docs
git diff --cached --quiet && (echo  No changes to publish. & goto :end)
git commit -m "Update online menu" || goto :error
git push || goto :error
echo.
echo  Published! It will be live in about 1 minute:
echo  https://salehlava.github.io/paraf-cafe-menu/
goto :end
:error
echo.
echo  Something went wrong. Nothing was lost - check the messages above.
:end
echo.
pause
