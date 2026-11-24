@echo off
setlocal
"%SystemRoot%\System32\WindowsPowerShell\v1.0\powershell.exe" -NoLogo -NoProfile -ExecutionPolicy Bypass -File "%~dp0scripts\windows\start.ps1"
set "PURO_RESULT=%ERRORLEVEL%"
if not "%PURO_RESULT%"=="0" pause
exit /b %PURO_RESULT%
