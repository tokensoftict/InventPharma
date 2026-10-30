@echo off
title Inventory Print Agent Console
echo ===================================================
echo Inventory Print Agent - Console Mode
echo ===================================================
echo.

set DIR=%~dp0
cd /d "%DIR%"

:: Check if 32-bit Windows or 64-bit Windows
set EXE=%DIR%InventoryPrintAgent.exe
if "%PROCESSOR_ARCHITECTURE%"=="x86" if not defined PROCESSOR_ARCHITEW6432 (
    echo Detected 32-bit Windows system.
    if exist "%DIR%InventoryPrintAgent-32bit.exe" (
        set EXE=%DIR%InventoryPrintAgent-32bit.exe
    )
)

echo Starting agent from: %EXE%
echo Press Ctrl+C to stop.
echo ===================================================
echo.

"%EXE%"
pause

