@echo off
setlocal
cd /d "%~dp0"

echo Starting MyFlix on http://localhost:8080
echo Raised upload limits: upload_max_filesize=2G, post_max_size=2G
echo.

php -d upload_max_filesize=2G -d post_max_size=2G -d max_execution_time=0 -d max_input_time=0 -S 0.0.0.0:8080 -t public

pause
