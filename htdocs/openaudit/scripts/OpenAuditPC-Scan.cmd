@echo off
c:
cd "C:\Program Files (x86)\xampplite\htdocs\openaudit\scripts"
powershell.exe -NoProfile -ExecutionPolicy Bypass -File "C:\Program Files (x86)\xampplite\htdocs\openaudit\scripts\audit.ps1" %1

rem *** alt ***  c:\windows\system32\cscript.exe "C:\Program Files (x86)\xampplite\htdocs\openaudit\scripts\audit.vbs" %1
