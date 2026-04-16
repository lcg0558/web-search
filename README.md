# 多引擎搜索 API — SearXNG 兼容

> 基于 PHP 的私有搜索引擎，兼容 SearXNG API 格式，支持 5 大中文搜索引擎聚合。

[![PHP](https://img.shields.io/badge/PHP-8.0%2B-blue.svg)](https://php.net)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

---

## ✨ 功能特性

### 🔍 五引擎聚合搜索

| 引擎 | 接口 | 解析方式 | 标识色 |
|------|------|----------|--------|
| **百度** Baidu | `tn=json`（优先）/ HTML DOM | 结构化 JSON / XPath | 🟣 紫色 |
| **必应** Bing | 国际版 + 中文参数 | DOM (`b_algo`) | 🟠 橙色 |
| **搜狗** Sogou | Web 搜索 | DOM (`vrwrap`) | 🟢 绿色 |
| **360** So.com | 综合搜索 | DOM (`res-list`) | 🔴 红色 |
| **夸克** Quark | 搜索引擎 | DOM (`card-content`) POST | 🔵 蓝色 |

**自动降级链：** 百度失败 → 必应 → 搜狗 → 360 → 夸克 → 全部失败才报错

### 📡 SearXNG API 完全兼容

```
GET /search?q={关键词}&format=json&pageno=1
GET /search?q={关键词}&format=csv          # CSV 下载
GET /search?q={关键词}&format=rss          # RSS 订阅
GET /suggest?q=关键词                       # 搜索建议
GET /api                                   # 实例信息
GET /health                                # 健康检查
```

### 🛠️ 其他功能

- 🎨 深色科技风格前端界面，响应式设计，移动端适配
- 💡 实时搜索建议（百度 Suggestion）
- 🚀 文件缓存 / Redis 缓存双模式
- 🔒 API Key 鉴权 + IP 限流
- 🌐 子目录部署支持（自动检测基地址）
- ⚡ 8 个随机 UA 轮换 + 反爬检测

---

## 📁 项目结构

```
web-search/
├── index.php              # 入口文件 + 路由（PHP 版核心）
├── index.html             # 前端搜索界面（深色科技风）
├── config.php             # 配置文件（引擎、缓存、安全）
├── 启动.bat               # Windows 一键启动脚本
├── .htaccess              # Apache URL 重写
├── router.php             # PHP 内置服务器路由
├── DEPLOY.md              # 线上部署指南（Nginx/云服务器）
│
├── src/                   # PHP 版核心代码
│   ├── BaiduSearch.php    # 多引擎搜索核心（5 引擎实现）
│   ├── ApiResponse.php    # SearXNG 兼容响应格式化
│   └── Cache.php          # 缓存层（文件/Redis 双模式
│
└── HTML/             # ★ 纯前端版（ 静态托管用）
    └── index.html         # 单文件部署，零后端依赖
```

**两个版本对比：**

| | PHP 版（根目录） | 纯前端版（`HTML/`） |
|---|---|---|
| **部署位置** | 支持 PHP 的服务器 | HTML 静态托管 / GitHub Pages / Vercel |
| **后端依赖** | PHP 8.0+ + curl/dom 扩展 | ❌ 无需任何后端 |
| **搜索引擎** | 自建 5 个中文引擎（百度/必应/搜狗/360/夸克） | 公共 SearXNG 实例（Google/Bing/DDG 等） |
| **数据可控性** | ✅ 完全自控 | 取决于公共实例 |
| **适用场景** | 私有部署、生产环境 | 免费托管、快速上线 |

---

## 🚀 快速开始（本地）

### 环境要求

| 要求 | 说明 |
|------|------|
| **PHP** | ≥ 8.0（推荐 8.3+） |
| **扩展** | curl、openssl、dom、mbstring |
| **可选** | Redis（用于缓存加速） |

> ⚠️ Laragon 用户注意：默认可能缺少 `php.ini`，项目已提供配置参考（见 `DEPLOY.md`）。
> Laragon一键布署PHP环境：https://laragon.org/download
> 手动布署PHP环境：https://getcomposer.org/Composer-Setup.exe
> https://windows.php.net/download/ （下载 x64 Thread Safe 的Zip）

### 一键启动

**Windows：双击 `启动.bat`**

或手动执行：

```bash
cd web-search
php -S localhost:8080 router.php
```

浏览器打开 `http://localhost:8080` 即可使用。

---

## 📡 API 使用文档

### 搜索接口

```http
GET /search?q={关键词}&format=json&pageno=1&language=zh-cn
```

**参数说明（完全兼容 SearXNG）：**

| 参数 | 类型 | 必填 | 默认值 | 说明 |
|------|------|:----:|--------|------|
| `q` | string | ✅ | - | 搜索关键词 |
| `format` | string | ❌ | `json` | 输出格式：`json` / `csv` / `rss` / `html` |
| `pageno` | int | ❌ | `1` | 页码（从 1 开始） |
| `language` | string | ❌ | `zh-CN` | 语言偏好 |
| `categories` | string | ❌ | `general` | 分类：`general` / `images` / `videos` / `news` |

**JSON 响应格式（SearXNG 标准）：**

```json
{
  "query": "PHP 教程",
  "number_of_results": 19,
  "results": [
    {
      "title": "PHP 教程 | runoob 菜鸟教程",
      "url": "https://www.runoob.com/php/php-tutorial.html",
      "content": "PHP 是一种创建动态交互性站点的强有力的服务器端脚本语言...",
      "engine": "baidu",
      "engines": ["baidu"],
      "score": 1.0,
      "category": "general",
      "parsed_url": ["https", "www.runoob.com", "/php/php-tutorial.html", "", ""],
      "template": "default.html"
    }
  ],
  "answers": [],
  "corrections": [],
  "infoboxes": [],
  "suggestions": [],
  "unresponsive_engines": []
}
```

### 其他接口

```http
# 搜索建议（百度联想词）
GET /suggest?q=PHP

# 实例信息
GET /api

# 健康检查
GET /health
```

### 调用示例

**JavaScript / fetch：**
```javascript
const resp = await fetch('https://your-domain.com/search?q=PHP教程&format=json');
const data = await resp.json();
console.log(data.results); // 搜索结果数组
data.results.forEach(r => console.log(r.title, r.url));
```

**Python / requests：**
```python
import requests
resp = requests.get('https://your-domain.com/search', params={
    'q': 'PHP教程', 'format': 'json', 'pageno': 1
})
data = resp.json()
for r in data['results']:
    print(f"{r['title']}\n  {r['url']}\n  {r['content'][:60]}...")
```

**cURL：**
```bash
curl "https://your-domain.com/search?q=PHP教程&format=json" | jq '.results[] | {title, url}'
```

---

## ⚙️ 配置说明

编辑 `config.php`：

```php
<?php
return [
    // ===== 搜索引擎配置 =====
    'engines' => [
        'baidu'   => ['enabled' => true,  'cookie' => '',  'timeout' => 8],  // 百度
        'bing'    => ['enabled' => true,  'timeout' => 10],                    // 必应
        'sogou'   => ['enabled' => true,  'timeout' => 8],                    // 搜狗
        'so360'   => ['enabled' => true,  'timeout' => 8],                    // 360搜索
        'quark'   => ['enabled' => true,  'timeout' => 10],                   // 夸克
    ],

    // ===== 缓存配置 =====
    'cache' => [
        'driver'     => 'file',       // file | redis
        'path'        => __DIR__ . '/cache/',
        'ttl'         => 300,         // 缓存有效期（秒）
        // 'redis' => ['host' => '127.0.0.1', 'port' => 6379],
    ],

    // ===== 安全配置 =====
    'security' => [
        'api_key'    => '',           // 留空 = 不鉴权
        'rate_limit' => 30,           // 每分钟请求限制
    ],
];
```

---

## 🌐 部署方式

### 方式一：Laragon 本地开发

直接双击 `启动.bat`，访问 `http://localhost:8080`

### 方式二：Nginx + Ubuntu 云服务器

详见 [DEPLOY.md](DEPLOY.md)，包含完整的一键初始化脚本。

### 方式三：Apache 虚拟主机

`.htaccess` 已内置，上传即可运行。

### 方式四：子目录部署（已支持）

将整个文件夹放到网站任意子目录，前端会自动检测路径。例如：

```
你的网站/
└── web-search/     ← 放这里
    ├── index.php
    ├── src/
    └── ...
```
访问 `https://你的域名/web-search/index.html`

### 方式五： 静态托管（纯前端版）

只需上传 **1 个文件** `/index.html`，零后端依赖：

```bash
# 通过 FTP 上传
```

或在腾讯云开发控制台 → 静态网站托管 → 手动上传该文件。

---

## ⚠️ 注意事项

1. **反爬机制**：各搜索引擎都有反爬策略，建议开启缓存减少请求频率
2. **IP 封禁**：高频请求可能导致 IP 被临时封禁，已内置降级机制自动切换引擎
3. **Cookie 可选**：在 `config.php` 中为百度配置 Cookie 可提高抓取成功率
4. **法律合规**：仅用于个人学习和研究目的
5. **SSL 证书**：线上部署强烈建议启用 HTTPS（Let's Encrypt 免费证书）

---

## 🔄 更新日志

### v1.3.0 (2026-04-16)

- 🆕 新增 **搜狗**、**360搜索**、**夸克** 三个中文搜索引擎
- 🆕 自动引擎降级链：5 个引擎依次尝试
- 🆕 前端引擎颜色标识（每条结果显示来源引擎标签）
- 🆕 纯前端版本（`cloudbase/`），可直接部署到静态托管
- 🐛 修复子目录部署时 API 路由 404 问题
- 🔧 重写 `BaiduSearch.php` 为多引擎架构

### v1.2.0 (2026-04-15)

- 🆕 必应 Bing 作为备用搜索引擎
- 🔧 百度接口改为 `tn=json`（结构化 JSON，更可靠）
- 🔧 8 个随机 UA 轮换
- 🐛 解决 Laragon PHP 缺少 php.ini 导致的 500 错误

### v1.0.0 (2026-04-15)

- 初始版本，百度搜索 + SearXNG API 兼容

---

## License

[MIT](LICENSE)
