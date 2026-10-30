@echo off
setlocal EnableDelayedExpansion
echo ===================================================
echo Installing Inventory Print Agent
echo ===================================================
echo.

set TASK_NAME=InventoryPrintAgent
set DIR=%~dp0
cd /d "%DIR%"

:: Stop old services or processes if any
sc stop %TASK_NAME% >nul 2>&1
sc delete %TASK_NAME% >nul 2>&1
taskkill /F /IM InventoryPrintAgent.exe >nul 2>&1
taskkill /F /IM InventoryPrintAgent-32bit.exe >nul 2>&1
schtasks /Delete /TN %TASK_NAME% /F >nul 2>&1

:: Detect 32-bit vs 64-bit Windows
if "%PROCESSOR_ARCHITECTURE%"=="x86" if not defined PROCESSOR_ARCHITEW6432 (
    echo [INFO] Detected 32-bit Windows system.
    if exist "%DIR%InventoryPrintAgent-32bit.exe" (
        copy /y "%DIR%InventoryPrintAgent-32bit.exe" "%DIR%InventoryPrintAgent.exe" >nul
        echo [INFO] Switched to 32-bit executable.
    )
)

:: Create the hidden launcher VBS script
set VBS=%DIR%run-hidden.vbs
(
echo Set sh = CreateObject^("WScript.Shell"^)
echo sh.CurrentDirectory = "%DIR%"
echo sh.Run """%DIR%InventoryPrintAgent.exe""", 0, False
) > "%VBS%"

:: Create Scheduled Task to run at user logon
schtasks /Create /TN %TASK_NAME% /TR "wscript.exe \"%VBS%\"" /SC ONLOGON /RL LIMITED /F >nul 2>&1
if %errorlevel% neq 0 (
    echo [INFO] Scheduled task restricted. Adding to Startup folder...
    set "STARTUP_DIR=%APPDATA%\Microsoft\Windows\Start Menu\Programs\Startup"
    (
    echo @echo off
    echo start "" /B wscript.exe "%VBS%"
    ) > "%STARTUP_DIR%\InventoryPrintAgent.bat"
)

:: Start the agent now
start "" wscript.exe "%VBS%"

echo.
echo ===================================================
echo [SUCCESS] Inventory Print Agent installed and started!
echo It will now run automatically in the background when you log in.
echo (Do not move or delete this folder)
echo ===================================================
echo.
pause
