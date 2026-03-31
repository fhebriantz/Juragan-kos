@echo off
chcp 65001 >nul 2>&1
setlocal EnableDelayedExpansion

:: ============================================================
:: Build Script — Juragan Kos → PHP Desktop (.exe)
:: ============================================================

echo.
echo   ========================================
echo     Build Juragan Kos → PHP Desktop
echo   ========================================
echo.

set "PROJECT_DIR=%~dp0"
set "PROJECT_DIR=%PROJECT_DIR:~0,-1%"
set "PHPDESKTOP_DIR=%PROJECT_DIR%\phpdesktop"
set "DIST_DIR=%PROJECT_DIR%\dist"
set "APP=JuraganKos"

:: --- Cek PHP Desktop ---
if not exist "%PHPDESKTOP_DIR%\phpdesktop-chrome.exe" (
    if not exist "%PHPDESKTOP_DIR%\phpdesktop-chrome-*.exe" (
        echo   [X] Folder phpdesktop\ belum ada atau exe tidak ditemukan.
        echo.
        echo   Download: https://github.com/nicengi/phpdesktop/releases
        echo   Ekstrak ke: %PHPDESKTOP_DIR%\
        echo.
        pause
        exit /b 1
    )
)
echo   [OK] PHP Desktop ditemukan

:: --- Cek php.ini untuk SQLite ---
if exist "%PHPDESKTOP_DIR%\php\php.ini" (
    findstr /C:";extension=pdo_sqlite" "%PHPDESKTOP_DIR%\php\php.ini" >nul 2>&1
    if !errorlevel!==0 (
        echo   [!] PERINGATAN: pdo_sqlite mungkin belum aktif di php.ini
        echo       Buka: %PHPDESKTOP_DIR%\php\php.ini
        echo       Hapus titik koma di depan: extension=pdo_sqlite
        echo.
    )
)

:: --- Bersihkan dist lama ---
if exist "%DIST_DIR%\%APP%" rmdir /s /q "%DIST_DIR%\%APP%"
mkdir "%DIST_DIR%\%APP%"
echo   [OK] Folder dist\ dibersihkan

:: --- Salin PHP Desktop ---
xcopy "%PHPDESKTOP_DIR%\*" "%DIST_DIR%\%APP%\" /E /I /Q /Y >nul
echo   [OK] PHP Desktop base disalin

:: --- settings.json ---
copy /Y "%PROJECT_DIR%\phpdesktop-settings.json" "%DIST_DIR%\%APP%\settings.json" >nul
echo   [OK] settings.json dikonfigurasi

:: --- Bangun www/ ---
if exist "%DIST_DIR%\%APP%\www" rmdir /s /q "%DIST_DIR%\%APP%\www"
mkdir "%DIST_DIR%\%APP%\www"

:: Salin semua folder & file ke www/
xcopy "%PROJECT_DIR%\config"   "%DIST_DIR%\%APP%\www\config\"   /E /I /Q /Y >nul
xcopy "%PROJECT_DIR%\includes" "%DIST_DIR%\%APP%\www\includes\" /E /I /Q /Y >nul
xcopy "%PROJECT_DIR%\pages"    "%DIST_DIR%\%APP%\www\pages\"    /E /I /Q /Y >nul
xcopy "%PROJECT_DIR%\assets"   "%DIST_DIR%\%APP%\www\assets\"   /E /I /Q /Y >nul
copy /Y "%PROJECT_DIR%\index.php" "%DIST_DIR%\%APP%\www\index.php" >nul
copy /Y "%PROJECT_DIR%\seed.php"  "%DIST_DIR%\%APP%\www\seed.php"  >nul
mkdir "%DIST_DIR%\%APP%\www\database"    2>nul
mkdir "%DIST_DIR%\%APP%\www\uploads"     2>nul
mkdir "%DIST_DIR%\%APP%\www\uploads\ktp" 2>nul
echo   [OK] Aplikasi disalin ke www\

:: --- Rename exe ---
if exist "%DIST_DIR%\%APP%\phpdesktop-chrome.exe" (
    ren "%DIST_DIR%\%APP%\phpdesktop-chrome.exe" "juragan-kos.exe"
    echo   [OK] Exe di-rename → juragan-kos.exe
)

echo.
echo   ========================================
echo   BUILD SELESAI!
echo   ========================================
echo.
echo   Output  : %DIST_DIR%\%APP%\
echo   Jalankan: double-click juragan-kos.exe
echo.
echo   Distribusi: ZIP folder %APP%\ lalu kirim ke client
echo.

pause
