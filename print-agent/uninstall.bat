@echo off
echo ===================================================
echo Uninstalling Inventory Print Agent
echo ===================================================
echo.
echo NOTE: Please run this script as Administrator!
echo.

set SERVICE_NAME=InventoryPrintAgent

:: Stop the service
sc stop %SERVICE_NAME% >nul 2>&1
echo [INFO] Service stopped.
timeout /t 2 /nobreak >nul

:: Delete the service
sc delete %SERVICE_NAME% >nul 2>&1
if %errorlevel% neq 0 (
    echo [ERROR] Failed to delete service. Did you run as Administrator?
    pause
    exit /b %errorlevel%
)

echo.
echo [SUCCESS] Inventory Print Agent uninstalled successfully.
echo You can now delete this folder.
pause
