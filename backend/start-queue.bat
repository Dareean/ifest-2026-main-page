@echo off
title I-FEST 2026 - Email Queue Worker
color 0A
echo =========================================
echo   I-FEST 2026 - Email Queue Worker
echo   Antrian pengiriman email ke Gmail
echo =========================================
echo.
echo [INFO] Memulai queue worker...
echo [INFO] Tekan CTRL+C untuk menghentikan.
echo.
cd /d "%~dp0"
php artisan queue:work --verbose --tries=3 --timeout=60 --sleep=3
pause
