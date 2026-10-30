@echo off
echo Uninstalling Inventory Print Agent...
taskkill /F /IM InventoryPrintAgent.exe >nul 2>&1
taskkill /F /IM InventoryPrintAgent-32bit.exe >nul 2>&1
schtasks /Delete /TN InventoryPrintAgent /F >nul 2>&1
sc stop InventoryPrintAgent >nul 2>&1
sc delete InventoryPrintAgent >nul 2>&1
del "%~dp0run-hidden.vbs" >nul 2>&1
del "%APPDATA%\Microsoft\Windows\Start Menu\Programs\Startup\InventoryPrintAgent.bat" >nul 2>&1
echo [DONE] Uninstalled successfully. You can delete this folder.
pause
