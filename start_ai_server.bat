@echo off
cd /d "%~dp0"
title SUSTAIN-U AI SERVER

echo ========================================================
echo     SUSTAIN-U AI BACKGROUND SERVER (Flask/YOLO) - PORT 5056
echo ========================================================
echo Working Directory: %cd%
echo.

set PYTHON_CMD=python
where python >nul 2>&1
if %errorlevel% neq 0 (
    if exist "C:\Users\%USERNAME%\AppData\Local\Programs\Python\Python311\python.exe" (
        set PYTHON_CMD="C:\Users\%USERNAME%\AppData\Local\Programs\Python\Python311\python.exe"
    ) else if exist "C:\Users\%USERNAME%\AppData\Local\Programs\Python\Python312\python.exe" (
        set PYTHON_CMD="C:\Users\%USERNAME%\AppData\Local\Programs\Python\Python312\python.exe"
    ) else if exist "C:\Users\Administrator\AppData\Local\Programs\Python\Python311\python.exe" (
        set PYTHON_CMD="C:\Users\Administrator\AppData\Local\Programs\Python\Python311\python.exe"
    )
)

%PYTHON_CMD% api\safety_server.py

if %errorlevel% neq 0 (
    echo.
    echo [CRITICAL] AI Server crashed or failed to start.
    pause
)