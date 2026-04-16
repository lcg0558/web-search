<?php
/**
 * 百度搜索 API - 入口文件
 * 兼容 SearXNG /search API 格式
 * 
 * 使用方式：
 *   /search?q=关键词           → JSON格式搜索结果
 *   /search?q=关键词&format=csv → CSV格式
 *   /search?q=关键词&format=rss → RSS格式
 *   /search?q=关键词&page=2    → 翻页
 *   /api                       → SearXNG 兼容 API 信息
 */

// 自动加载
spl_autoload_register(function (string $class) {
    $prefix = 'BaiduSearch\\';
    $baseDir = __DIR__ . '/src/';
    $len = strlen($prefix);

    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

$config = require __DIR__ . '/config.php';

// CORS 头
$origins = $config['security']['allowed_origins'] ?? ['*'];
if (in_array('*', $origins)) {
    header('Access-Control-Allow-Origin: *');
} else {
    $requestOrigin = $_SERVER['HTTP_ORIGIN'] ?? '';
    if (in_array($requestOrigin, $origins)) {
        header("Access-Control-Allow-Origin: $requestOrigin");
    }
}
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// API Key 验证
if (!empty($config['security']['api_key'])) {
    $providedKey = $_GET['apikey'] ?? $_SERVER['HTTP_X_API_KEY'] ?? '';
    if ($providedKey !== $config['security']['api_key']) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid API key'], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// 简单限流
if ($config['api']['rate_limit'] > 0) {
    $ip = $_SERVER['REMOTE_ADDR'];
    $rateFile = sys_get_temp_dir() . '/baidu_search_rate_' . md5($ip);
    $now = time();
    $requests = [];

    if (file_exists($rateFile)) {
        $requests = json_decode(file_get_contents($rateFile), true) ?: [];
        // 只保留最近60秒的记录
        $requests = array_filter($requests, fn($t) => $now - $t < 60);
    }

    if (count($requests) >= $config['api']['rate_limit']) {
        http_response_code(429);
        echo json_encode(['error' => '请求过于频繁，请稍后再试'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $requests[] = $now;
    file_put_contents($rateFile, json_encode($requests));
}

// 路由
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = rtrim($path, '/');

switch ($path) {
    case '':
    case '/':
        // 首页 → 搜索界面
        include __DIR__ . '/index.html';
        break;

    case '/search':
        handleSearch($config);
        break;

    case '/api':
        handleApiInfo($config);
        break;

    case '/suggest':
        handleSuggest($config);
        break;

    case '/health':
        echo json_encode(['status' => 'ok', 'timestamp' => time()], JSON_UNESCAPED_UNICODE);
        break;

    default:
        http_response_code(404);
        echo json_encode(['error' => 'Not Found'], JSON_UNESCAPED_UNICODE);
        break;
}

/**
 * 处理搜索请求 - SearXNG 兼容
 */
function handleSearch(array $config): void
{
    $query = trim($_GET['q'] ?? '');
    $page = max(1, min(intval($_GET['pageno'] ?? $_GET['page'] ?? 1), $config['api']['max_page']));
    $format = $_GET['format'] ?? $config['api']['default_format'];
    $language = $_GET['language'] ?? 'zh-CN';
    $categories = $_GET['categories'] ?? 'general';

    if (empty($query)) {
        \BaiduSearch\ApiResponse::json([
            'error' => '缺少搜索关键词，请使用 ?q=关键词 参数',
        ], 400);
    }

    $cache = new \BaiduSearch\Cache($config['cache']);
    $cacheKey = $cache->makeKey($query, $page, $language);

    // 检查缓存
    $cached = $cache->get($cacheKey);
    if ($cached !== null) {
        $response = \BaiduSearch\ApiResponse::build($cached, $config);
        outputResponse($response, $format);
        return;
    }

    // 执行搜索（支持 engine 参数选择搜索引擎）
    $engine = $_GET['engine'] ?? '';
    try {
        $search = new \BaiduSearch\BaiduSearch($config);
        $result = $search->search($query, $page, $language);

        // 写入缓存
        $cache->set($cacheKey, $result);

        $response = \BaiduSearch\ApiResponse::build($result, $config);
        outputResponse($response, $format);

    } catch (\Throwable $e) {
        \BaiduSearch\ApiResponse::json([
            'error' => '搜索失败: ' . $e->getMessage(),
            'query' => $query,
            'trace' => $e->getTraceAsString(),
        ], 500);
    }
}

/**
 * 处理搜索建议
 */
function handleSuggest(array $config): void
{
    $query = trim($_GET['q'] ?? '');

    if (empty($query)) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 调用搜索引擎建议API
    $search = new \BaiduSearch\BaiduSearch($config);
    $suggestions = $search->suggest($query);

    // SearXNG 建议格式
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        $query,
        $suggestions,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * API 信息 - SearXNG 兼容
 */
function handleApiInfo(array $config): void
{
    \BaiduSearch\ApiResponse::json([
        'instance_name' => $config['instance']['name'],
        'version' => $config['instance']['version'],
        'description' => $config['instance']['description'],
        'api_endpoints' => [
            'search' => '/search?q={query}&format={json|csv|rss}&pageno={page}',
            'suggest' => '/suggest?q={query}',
            'api_info' => '/api',
            'health' => '/health',
        ],
        'engines' => ['baidu'],
        'formats' => ['json', 'csv', 'rss', 'html'],
        'categories' => ['general'],
    ]);
}

/**
 * 按格式输出响应
 */
function outputResponse(array $response, string $format): void
{
    switch ($format) {
        case 'csv':
            \BaiduSearch\ApiResponse::csv($response);
            break;
        case 'rss':
            \BaiduSearch\ApiResponse::rss($response);
            break;
        case 'html':
            // HTML格式需要前端渲染，这里返回JSON + HTML标记
            header('Content-Type: text/html; charset=utf-8');
            echo buildHtmlResults($response);
            break;
        case 'json':
        default:
            \BaiduSearch\ApiResponse::json($response);
            break;
    }
}

/**
 * 构建HTML搜索结果（format=html时使用）
 */
function buildHtmlResults(array $response): string
{
    $html = '<!DOCTYPE html><html lang="zh-CN"><head><meta charset="UTF-8">';
    $html .= '<title>搜索: ' . htmlspecialchars($response['query'] ?? '') . '</title>';
    $html .= '<style>body{font-family:system-ui;background:#1a1a2e;color:#eee;padding:20px}';
    $html .= '.result{margin:20px 0;padding:15px;background:#16213e;border-radius:8px}';
    $html .= '.result a{color:#4fc3f7;text-decoration:none;font-size:1.2em}';
    $html .= '.result a:hover{text-decoration:underline}';
    $html .= '.snippet{color:#aaa;margin-top:8px}';
    $html .= '.url{color:#666;font-size:0.85em;margin-top:4px}';
    $html .= '</style></head><body>';

    foreach ($response['results'] ?? [] as $item) {
        $html .= '<div class="result">';
        $html .= '<a href="' . htmlspecialchars($item['url']) . '" target="_blank">' . htmlspecialchars($item['title']) . '</a>';
        $html .= '<div class="snippet">' . htmlspecialchars($item['content'] ?? '') . '</div>';
        $html .= '<div class="url">' . htmlspecialchars($item['url']) . '</div>';
        $html .= '</div>';
    }

    $html .= '</body></html>';
    return $html;
}
