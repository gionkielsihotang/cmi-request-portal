@ECHO OFF
SETLOCAL

WHERE php >nul 2>nul
IF %ERRORLEVEL% EQU 0 (
    SET "PHP_BIN=php"
) ELSE IF EXIST "D:\laragon\bin\php\php-8.3.36-Win32-vs16-x64\php.exe" (
    SET "PHP_BIN=D:\laragon\bin\php\php-8.3.36-Win32-vs16-x64\php.exe"
) ELSE IF EXIST "C:\laragon\bin\php\php-8.3.36-Win32-vs16-x64\php.exe" (
    SET "PHP_BIN=C:\laragon\bin\php\php-8.3.36-Win32-vs16-x64\php.exe"
) ELSE (
    SET "PHP_BIN=php"
)

"%PHP_BIN%" "%~dp0phpunit" %*
