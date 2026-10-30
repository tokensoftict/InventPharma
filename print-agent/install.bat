@echo off
echo ===================================================
echo Installing Inventory Print Agent (runs as logged-in user)
echo ===================================================
echo.
echo Runs under YOUR Windows account at logon, so shared/network
echo printers installed for your user are visible to the agent.
echo (No administrator rights needed.)
echo.

set TASK_NAME=InventoryPrintAgent
set DIR=%~dp0
set VBS=%DIR%run-hidden.vbs

:: Remove old Windows service (older versions ran as a service under SYSTEM,
:: which cannot see per-user network printers). Ignored if not present / no admin.
sc stop InventoryPrintAgent >nul 2>&1
sc delete InventoryPrintAgent >nul 2>&1

:: Stop any running instance
taskkill /F /IM InventoryPrintAgent.exe >nul 2>&1
schtasks /Delete /TN %TASK_NAME% /F >nul 2>&1

:: Launcher that starts the exe without a console window
> "%VBS%" echo Set sh = CreateObject("WScript.Shell")
>> "%VBS%" echo sh.CurrentDirectory = "%DIR%"
>> "%VBS%" echo sh.Run """%DIR%InventoryPrintAgent.exe""", 0, False

schtasks /Create /TN %TASK_NAME% /TR "wscript.exe \"%VBS%\"" /SC ONLOGON /RL LIMITED /F
if %errorlevel% neq 0 (
    echo [ERROR] Failed to create the startup task.
    pause
    exit /b %errorlevel%
)

:: Start now
schtasks /Run /TN %TASK_NAME%

echo.
echo [SUCCESS] Print agent installed and started. It will auto-start at every logon.
echo Keep this folder in place (do not move/delete it).
pause
