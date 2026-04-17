@echo off
REM Compile the Tailwind CSS bundle (Windows). Requires node/npm installed.
setlocal
cd /d "%~dp0..\crm"

if not exist node_modules (
  echo Installing Tailwind...
  call npm install
  if errorlevel 1 goto :err
)

echo Building output.css...
call npx tailwindcss -c .\tailwind.config.js -i .\public\assets\css\input.css -o .\public\assets\css\output.css --minify
if errorlevel 1 goto :err

echo Done: crm\public\assets\css\output.css
endlocal
exit /b 0

:err
echo ERROR during tailwind build.
endlocal
exit /b 1
