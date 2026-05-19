@echo off
title Offline Scan OpenAuditClassic txt im gleichen Pfad wie der Aufruf
powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%~dp0\audit.ps1" %*

rem *** alt *** %windir%\system32\cscript.exe "%~dp0\audit.vbs"
