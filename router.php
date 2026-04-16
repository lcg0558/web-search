<?php
/**
 * PHP 内置服务器路由文件
 * 用于 php -S 开发模式
 */

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// 静态文件直接返回
$publicFiles = ['/index.html', '/favicon.ico'];
if (in_array($uri, $publicFiles) && file_exists(__DIR__ . $uri)) {
    return false; // 让内置服务器处理静态文件
}

// 其他请求都走 index.php
require __DIR__ . '/index.php';
