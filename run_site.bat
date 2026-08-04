@echo off
title Keystone Notice Board - Dev Server
echo ===================================================
echo   Starting Keystone Notice Board Local Dev Server   
echo ===================================================
echo.
echo  Opening http://127.0.0.1:8000 in your browser...
echo  Press Ctrl+C in this window to stop the server.
echo.
start http://127.0.0.1:8000
"C:\xampp\php\php.exe" -S 127.0.0.1:8000 api/index.php
