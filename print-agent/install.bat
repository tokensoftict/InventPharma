@echo off
echo ===================================================
echo Installing Inventory Print Agent as a Windows Service
echo ===================================================
echo.
echo NOTE: Please run this script as Administrator!
echo.

set SERVICE_NAME=InventoryPrintAgent
set BIN_PATH=%~dp0InventoryPrintAgent.exe

:: Stop the service if it's already running
sc stop %SERVICE_NAME% >nul 2>&1
:: Delete the service if it already exists
sc delete %SERVICE_NAME% >nul 2>&1

:: Create the service
sc create %SERVICE_NAME% binPath= "\"%BIN_PATH%\"" start= auto DisplayName= "Inventory Print Agent"
if %errorlevel% neq 0 (
    echo [ERROR] Failed to create service. Did you run as Administrator?
    pause
    exit /b %errorlevel%
)

:: Set description
sc description %SERVICE_NAME% "ESC/POS thermal receipt printing agent for Inventory Management System"

:: Start the service
sc start %SERVICE_NAME%
if %errorlevel% neq 0 (
    echo [ERROR] Failed to start service.
    pause
    exit /b %errorlevel%
)

echo.
echo [SUCCESS] Inventory Print Agent installed and started successfully!
echo You can now print thermal receipts from the web application.
pause
