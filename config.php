<?php
/**
 * 百度搜索工具 - 配置文件
 * 兼容 SearXNG API 格式
 *
 * 重要：如果百度触发验证码，请在 engines.baidu.cookie 中填入浏览器Cookie
 * 获取方法：Chrome 打开 baidu.com → F12 → Network → 搜索 → 点任意请求 → 复制 Cookie 头
 */

return [
    // 搜索引擎配置（支持多引擎，按顺序自动降级）
    'engines' => [
        'baidu' => [
            'name' => '百度',
            'enabled' => true,
            'base_url' => 'https://www.baidu.com/s',
            'suggest_url' => 'https://suggestion.baidu.com/su',
            'timeout' => 15,
            'max_results' => 20,
            'user_agent' => '',
            // ⚠️ 关键！填入百度Cookie可绕过验证码
            // 从 Chrome 复制：F12 → Network → 搜索 → 请求头 → Cookie
            'cookie' => '',
        ],
        'bing' => [
            'name' => '必应',
            'enabled' => true,
            'base_url' => 'https://www.bing.com/search',
            'timeout' => 15,
            'max_results' => 20,
            'user_agent' => '',
            'cookie' => '',
            // 必应国际版（中文结果更好）
            'setlang' => 'zh-Hans',
            'cc' => 'CN',
        ],
        'sogou' => [
            'name' => '搜狗',
            'enabled' => true,
            'base_url' => 'https://www.sogou.com/web',
            'timeout' => 15,
            'max_results' => 20,
            'user_agent' => '',
            'cookie' => '',
        ],
        'so360' => [
            'name' => '360搜索',
            'enabled' => true,
            'base_url' => 'https://www.so.com/s',
            'timeout' => 15,
            'max_results' => 20,
            'user_agent' => '',
            'cookie' => '',
        ],
        'quark' => [
            'name' => '夸克',
            'enabled' => true,
            'base_url' => 'https://quark.sm/',
            'timeout' => 15,
            'max_results' => 20,
            'user_agent' => '',
            'cookie' => '',
        ],
    ],

    // 默认引擎（baidu 或 bing）
    'default_engine' => 'baidu',

    // API 配置
    'api' => [
        'default_format' => 'json',  // json | html | csv | rss
        'max_page' => 5,
        'rate_limit' => 30,
    ],

    // 缓存配置
    'cache' => [
        'enabled' => true,
        'driver' => 'file',
        'path' => __DIR__ . '/cache',
        'ttl' => 3600,
        'redis' => [
            'host' => '127.0.0.1',
            'port' => 6379,
        ],
    ],

    // 安全配置
    'security' => [
        'api_key' => '',
        'allowed_origins' => ['*'],
    ],

    // 站点信息（SearXNG 兼容）
    'instance' => [
        'name' => 'Baidu Search API',
        'version' => '1.0.0',
        'description' => '基于百度的私有搜索API，兼容SearXNG格式',
    ],
];
