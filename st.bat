@echo off
set OUTPUT=projekt_code.txt

if exist %OUTPUT% del %OUTPUT%

for /r %%F in (*.php *.js *.css) do (
    echo.>>%OUTPUT%
    echo ====================================================>>%OUTPUT%
    echo DATEI: %%F>>%OUTPUT%
    echo ====================================================>>%OUTPUT%
    type "%%F" >> %OUTPUT%
    echo.>>%OUTPUT%
)

echo.
echo Fertig! Datei erstellt:
echo %OUTPUT%
pause