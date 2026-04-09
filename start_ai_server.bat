@echo off
cd /d "%~dp0"
title SUSTAIN-U AI SERVER

echo ========================================================
echo     SUSTAIN-U AI BACKGROUND SERVER (Flask/YOLO) - PORT 5056
echo ========================================================
echo Working Directory: %cd%
echo.

"C:\Users\Administrator\AppData\Local\Programs\Python\Python311\python.exe" api\safety_server.py

if %errorlevel% neq 0 (
    echo.
    echo [CRITICAL] AI Server crashed or failed to start.
    pause
)