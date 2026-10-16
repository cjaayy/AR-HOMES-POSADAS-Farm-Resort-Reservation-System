@echo off
title AR Homes Posadas - Localhost
color 0A

echo.
echo  ============================================
echo   AR Homes Posadas Farm Resort
echo   Reservation System (Localhost)
echo  ============================================
echo.

:: ---- Step 1: Start Apache ----
echo  [1/3] Starting Apache...
start "" /B "C:\xampp\apache\bin\httpd.exe" >nul 2>&1
echo        Done.

:: ---- Step 2: Start MySQL ----
echo  [2/3] Starting MySQL...
start "" /B "C:\xampp\mysql\bin\mysqld.exe" --defaults-file="C:\xampp\mysql\bin\my.ini" --standalone >nul 2>&1
echo        Done.

:: Wait for services to fully start
timeout /t 2 /nobreak >nul

set "APP_URL=http://localhost:8080/AR_Homes_Posadas_Farm_Resort_Reservation_System_Design_2/"

:: ---- Step 3: Open Browser ----
echo  [3/3] Opening browser...
start "" "%APP_URL%"
echo        Localhost: %APP_URL%
echo.
echo  ============================================
echo   Application running at:
echo   %APP_URL%
echo  ============================================
echo.
echo  Press any key to stop services and exit...
pause >nul

:: ---- Cleanup on exit ----
echo.
echo  Stopping services...
call "C:\xampp\apache_stop.bat" >nul 2>&1
call "C:\xampp\mysql_stop.bat" >nul 2>&1
echo  All services stopped. Goodbye!
timeout /t 2 /nobreak >nul
