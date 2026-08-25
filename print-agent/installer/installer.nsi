; NSIS Installer Script for InventoryPrintAgent
; Requires NSIS 3.x (https://nsis.sourceforge.io)
;
; Build: makensis installer.nsi

!include "MUI2.nsh"

; --- General ---
Name "InventoryPrintAgent"
OutFile "InventoryPrintAgent-Setup.exe"
InstallDir "$PROGRAMFILES\InventoryPrintAgent"
RequestExecutionLevel admin

; --- Interface ---
!define MUI_ABORTWARNING
!define MUI_ICON "${NSISDIR}\Contrib\Graphics\Icons\modern-install.ico"
!define MUI_UNICON "${NSISDIR}\Contrib\Graphics\Icons\modern-uninstall.ico"

; --- Pages ---
!insertmacro MUI_PAGE_WELCOME
!insertmacro MUI_PAGE_DIRECTORY
!insertmacro MUI_PAGE_INSTFILES
!insertmacro MUI_PAGE_FINISH

!insertmacro MUI_UNPAGE_CONFIRM
!insertmacro MUI_UNPAGE_INSTFILES

!insertmacro MUI_LANGUAGE "English"

; --- Install Section ---
Section "Install"
    SetOutPath $INSTDIR

    ; Copy files
    File "..\InventoryPrintAgent.exe"
    File "..\config.yaml"

    ; Stop existing service if running
    nsExec::ExecToLog 'sc stop InventoryPrintAgent'
    Sleep 2000
    nsExec::ExecToLog 'sc delete InventoryPrintAgent'
    Sleep 1000

    ; Create the Windows service
    nsExec::ExecToLog 'sc create InventoryPrintAgent binPath= "$INSTDIR\InventoryPrintAgent.exe" start= auto DisplayName= "Inventory Print Agent"'
    nsExec::ExecToLog 'sc description InventoryPrintAgent "ESC/POS thermal receipt printing agent for Inventory Management System"'

    ; Start the service
    nsExec::ExecToLog 'sc start InventoryPrintAgent'

    ; Create uninstaller
    WriteUninstaller "$INSTDIR\Uninstall.exe"

    ; Add to Add/Remove Programs
    WriteRegStr HKLM "Software\Microsoft\Windows\CurrentVersion\Uninstall\InventoryPrintAgent" \
                     "DisplayName" "InventoryPrintAgent"
    WriteRegStr HKLM "Software\Microsoft\Windows\CurrentVersion\Uninstall\InventoryPrintAgent" \
                     "UninstallString" "$\"$INSTDIR\Uninstall.exe$\""
    WriteRegStr HKLM "Software\Microsoft\Windows\CurrentVersion\Uninstall\InventoryPrintAgent" \
                     "Publisher" "Tokensoft ICT"
    WriteRegStr HKLM "Software\Microsoft\Windows\CurrentVersion\Uninstall\InventoryPrintAgent" \
                     "DisplayVersion" "1.0.0"
SectionEnd

; --- Uninstall Section ---
Section "Uninstall"
    ; Stop and remove the service
    nsExec::ExecToLog 'sc stop InventoryPrintAgent'
    Sleep 2000
    nsExec::ExecToLog 'sc delete InventoryPrintAgent'
    Sleep 1000

    ; Remove files
    Delete "$INSTDIR\InventoryPrintAgent.exe"
    Delete "$INSTDIR\config.yaml"
    Delete "$INSTDIR\Uninstall.exe"
    RMDir "$INSTDIR"

    ; Remove from Add/Remove Programs
    DeleteRegKey HKLM "Software\Microsoft\Windows\CurrentVersion\Uninstall\InventoryPrintAgent"
SectionEnd
