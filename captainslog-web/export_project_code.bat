@echo off
setlocal

set "OUTPUT=%~dp0captains_log_project_code.txt"
set "ROOT=%~dp0"

if exist "%OUTPUT%" del "%OUTPUT%"

echo ============================================================>>"%OUTPUT%"
echo CAPTAIN'S LOG - PROJECT CODE EXPORT>>"%OUTPUT%"
echo Root: %ROOT%>>"%OUTPUT%"
echo Created: %DATE% %TIME%>>"%OUTPUT%"
echo ============================================================>>"%OUTPUT%"
echo.>>"%OUTPUT%"

for %%E in (php js css html htm sql json md) do (
    for /r "%ROOT%" %%F in (*.%%E) do (
        echo %%F | findstr /i /c:"\.git\" /c:"\node_modules\" /c:"\vendor\" /c:"\uploads\" /c:"\dist\" /c:"\build\" /c:"\.idea\" /c:"\.vscode\" >nul

        if errorlevel 1 (
            echo.>>"%OUTPUT%"
            echo ============================================================>>"%OUTPUT%"
            echo FILE: %%F>>"%OUTPUT%"
            echo ============================================================>>"%OUTPUT%"
            type "%%F">>"%OUTPUT%" 2>nul
            echo.>>"%OUTPUT%"
        )
    )
)

echo.>>"%OUTPUT%"
echo ============================================================>>"%OUTPUT%"
echo EXPORT FINISHED>>"%OUTPUT%"
echo ============================================================>>"%OUTPUT%"

echo.
echo Export abgeschlossen:
echo "%OUTPUT%"
echo.
pause