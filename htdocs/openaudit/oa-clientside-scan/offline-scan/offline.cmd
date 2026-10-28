@echo off
setlocal
title OpenAudit Classic - Offline Scan
cd /d "%~dp0"
echo Starte lokalen Offline-Scan auf %COMPUTERNAME% ...
echo.
powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%~dp0audit.ps1" "."
set "SCAN_EXIT=%ERRORLEVEL%"
echo.
echo Scan beendet. Exit-Code: %SCAN_EXIT%
echo Erwartete Ausgabedatei: %~dp0%COMPUTERNAME%.txt
if not exist "%~dp0%COMPUTERNAME%.txt" echo WARNUNG: Ausgabedatei nicht gefunden.
pause
exit /b %SCAN_EXIT%
