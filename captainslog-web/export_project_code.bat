@echo off
setlocal EnableExtensions

set "ROOT=%~dp0"
set "OUTPUT=%ROOT%captains_log_required_files.txt"

if exist "%OUTPUT%" del "%OUTPUT%"

(
    echo ============================================================
    echo CAPTAIN'S LOG - REQUIRED FILES EXPORT
    echo Root: %ROOT%
    echo Created: %DATE% %TIME%
    echo ============================================================
    echo.
) >> "%OUTPUT%"

call :ExportFile "dashboard\js\project_plan.js"
call :ExportFile "dashboard\views\project_plan.php"

call :ExportFile "dashboard\js\issues.js"
call :ExportFile "dashboard\views\issues.php"

call :ExportFile "dashboard\js\app.js"
call :ExportFile "dashboard\js\state.js"

call :ExportFile "dashboard\js\project_team.js"

call :ExportFile "api\get_tasks.php"
call :ExportFile "api\get_issues.php"
call :ExportFile "api\get_project_team.php"

(
    echo.
    echo ============================================================
    echo EXPORT FINISHED
    echo ============================================================
) >> "%OUTPUT%"

echo.
echo Export abgeschlossen:
echo "%OUTPUT%"
echo.
pause
exit /b


:ExportFile
set "RELATIVE_FILE=%~1"
set "FULL_FILE=%ROOT%%RELATIVE_FILE%"

(
    echo.
    echo ============================================================
    echo FILE: %RELATIVE_FILE%
    echo FULL PATH: %FULL_FILE%
    echo ============================================================
) >> "%OUTPUT%"

if exist "%FULL_FILE%" (
    type "%FULL_FILE%" >> "%OUTPUT%" 2>nul
    echo. >> "%OUTPUT%"
) else (
    (
        echo [DATEI NICHT GEFUNDEN]
        echo Erwarteter Pfad: %FULL_FILE%
        echo.
    ) >> "%OUTPUT%"
)

exit /b