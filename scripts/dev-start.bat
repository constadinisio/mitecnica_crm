@echo off
REM Mi Tecnica CRM -- dev launcher (Windows)
REM Runs pending migrations, then opens two terminals: API + CRM.

setlocal
cd /d "%~dp0.."
set ROOT=%cd%

echo ==^> Project root: %ROOT%

REM --- sanity checks ---------------------------------------------------------
if not exist "%ROOT%\api\.env" (
  echo [WARN] api\.env not found. Copying from example.
  copy "%ROOT%\infra\env\api.env.example" "%ROOT%\api\.env" >nul
)
if not exist "%ROOT%\crm\.env" (
  echo [WARN] crm\.env not found. Copying from example.
  copy "%ROOT%\infra\env\crm.env.example" "%ROOT%\crm\.env" >nul
)
if not exist "%ROOT%\api\node_modules" (
  echo ==^> Installing API deps (first run)
  pushd "%ROOT%\api"
  call npm install
  if errorlevel 1 goto :err
  popd
)

REM --- migrations ------------------------------------------------------------
echo ==^> Running pending migrations
pushd "%ROOT%\api"
call npx knex migrate:latest
if errorlevel 1 goto :err
popd

REM --- launch API in its own terminal ---------------------------------------
echo ==^> Launching API (port 4000) in new window
start "mitecnica-api" cmd /k "cd /d %ROOT%\api && npm run dev"

REM --- launch CRM in its own terminal ---------------------------------------
echo ==^> Launching CRM (port 8080) in new window
start "mitecnica-crm" cmd /k "cd /d %ROOT%\crm && php -S localhost:8080 -t public public\router.php"

echo.
echo Both services launched.
echo   API: http://localhost:4000/api/v1
echo   CRM: http://localhost:8080/login
echo   Login: admin@mitecnica.local / Admin123!
echo.
echo To stop: close the two opened terminals (mitecnica-api, mitecnica-crm).

endlocal
exit /b 0

:err
echo ERROR during startup.
popd
endlocal
exit /b 1
