@echo off
REM Mi Tecnica CRM -- local setup (Windows)

setlocal
cd /d "%~dp0.."
set ROOT=%cd%
echo ==^> Project root: %ROOT%

if not exist "%ROOT%\api\.env" copy "%ROOT%\infra\env\api.env.example" "%ROOT%\api\.env" >nul
if not exist "%ROOT%\crm\.env" copy "%ROOT%\infra\env\crm.env.example" "%ROOT%\crm\.env" >nul

echo ==^> Installing API deps
pushd "%ROOT%\api"
call npm install
if errorlevel 1 goto :err

echo ==^> Running migrations
call npx knex migrate:latest
if errorlevel 1 goto :err

echo ==^> Running seeds
call npx knex seed:run
if errorlevel 1 goto :err

popd
echo.
echo Setup OK.
echo   cd api ^& npm run dev
echo   cd crm ^& php -S localhost:8080 -t public public\router.php
echo   CRM login: admin@mitecnica.local / Admin123!
endlocal
exit /b 0

:err
echo ERROR during setup.
popd
endlocal
exit /b 1
