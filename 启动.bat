@echo off
chcp 65001 >nul
title Web Search API

cd /d "%~dp0"

echo.
echo   多引擎搜索 API 运行中...
echo.

C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe -S localhost:8080 router.php

echo.
echo   ================================================
echo   [错误] 启动失败！PHP 不存在或路径不对
echo.
echo   请下载安装 Laragon（自带 PHP）：
echo   https://laragon.org/download/
echo.
echo   或确认 PHP 路径正确后修改本文件中的路径
echo   ================================================
echo.
pause
