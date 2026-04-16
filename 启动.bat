@echo off
chcp 65001 >nul
title 百度搜索 API - 多引擎

echo ╔══════════════════════════════════════╗
echo ║   多引擎搜索 API  启动中...          ║
echo ║   引擎: 百度 / 必应 / 搜狗 / 360 / 夸克 ║
echo ╚══════════════════════════════════════╝
echo.

cd /d "%~dp0"

:: 检查 PHP 是否存在
where php >nul 2>nul
if %errorlevel% neq 0 (
    set "PHP=C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe"
    if not exist "%PHP%" (
        echo [错误] 找不到 PHP，请确认 Laragon 已安装或 PHP 已加入 PATH
        pause
        exit /b 1
    )
) else (
    set "PHP=php"
)

echo [信息] 使用 PHP: %PHP%
echo [信息] 启动地址: http://localhost:8080
echo.
echo 按 Ctrl+C 停止服务器
echo.

"%PHP%" -S localhost:8080 router.php
pause
