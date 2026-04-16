# 多引擎搜索 API — 线上部署指南

> 目标：`api.你的域名.com` → 搜索服务

---

## 第一步：安装 Nginx（如果没装）

SSH 登录服务器后执行：

```bash
# 更新包列表
sudo apt update

# 安装 Nginx
sudo apt install nginx -y

# 启动并设置开机自启
sudo systemctl start nginx
sudo systemctl enable nginx

# 验证：浏览器访问服务器 IP，看到 Nginx 欢迎页就 OK
```

---

## 第二步：确认 PHP + 必要扩展

```bash
# 查看 PHP 版本
php -v

# 安装必要扩展（大概率需要装）
sudo apt install php-curl php-dom php-mbstring php-openssl -y

# 验证 curl 扩展已加载
php -m | grep curl   # 应该输出 curl
```

> ⚠️ **PHP 版本要求 ≥ 8.0**。如果是 7.x，搜索建议功能会报错。

---

## 第三步：上传项目文件

### 方式 A：SCP 直接上传（推荐）

在你**本地电脑的 PowerShell** 里执行：

```powershell
# 把整个 web-search 文件夹上传到服务器
scp -r web-search\ root@你的服务器IP:/var/www/search-api/
```

### 方式 B：先压缩再传

```powershell
# 本地压缩
cd web-search
Compress-Archive -Path web-search -DestinationPath web-search.zip

# 上传
scp web-search.zip root@你的服务器IP:/tmp/

# SSH 到服务器解压
ssh root@你的服务器IP
sudo mkdir -p /var/www/search-api
sudo unzip /tmp/web-search.zip -d /var/www/search-api/
rm /tmpweb-search.zip
```

### 设置权限

```bash
# 设置目录所有者为 www-data（Nginx 用户）
sudo chown -R www-data:www-data /var/www/search-api
sudo chmod -R 755 /var/www/search-api

# 缓存目录需要写入权限
sudo chmod -R 777 /var/www/search-api/cache
```

---

## 第四步：配置 Nginx 子域名

创建站点配置文件：

```bash
sudo nano /etc/nginx/sites-available/search-api
```

粘贴以下内容（把 `api.你的域名.com` 改成你的实际子域名）：

```nginx
server {
    listen 80;
    server_name api.你的域名.com;

    root /var/www/search-api;
    index index.php index.html;

    # 安全头
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-Frame-Options "DENY" always;
    add_header X-XSS-Protection "1; mode=block" always;

    # PHP 处理
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php-fpm.sock;
        # 如果上面报错，改成下面这行：
        # fastcgi_pass 127.0.0.1:9000;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # 静态资源缓存
    location ~* \.(css|js|png|jpg|jpeg|gif|ico|svg|woff2)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
    }

    # 禁止访问敏感文件
    location ~ /\.(ht|git|env) {
        deny all;
    }
}
```

保存退出（Ctrl+O → Enter → Ctrl+X），然后启用：

```bash
# 启用站点
sudo ln -s /etc/nginx/sites-available/search-api /etc/nginx/sites-enabled/

# 测试配置是否正确
sudo nginx -t

# 重载 Nginx 使配置生效
sudo systemctl reload nginx
```

---

## 第五步：域名 DNS 解析

去你域名的 DNS 管理面板（阿里云/腾讯云/Cloudflare 等），添加一条 A 记录：

| 记录类型 | 主机记录 | 记录值 |
|---|---|---|
| A | `api` | 你的服务器 IP |

等 DNS 生效（通常几分钟到几小时），可以用这个命令检查：

```bash
nslookup api.你的域名.com
# 应该返回你服务器的 IP
```

---

## 第六步：申请 SSL 证书（HTTPS，免费）

```bash
# 安装 Certbot
sudo apt install certbot python3-certbot-nginx -y

# 自动申请证书并配置 Nginx
sudo certbot --nginx -d api.你的域名.com
```

按提示输入邮箱，同意条款即可。Certbot 会自动：
1. 申请 Let's Encrypt 免费证书
2. 修改 Nginx 配置启用 HTTPS
3. 设置 HTTP 自动跳转 HTTPS

证书有效期 90 天，自动续期：

```bash
sudo certbot renew --dry-run   # 测试续期是否正常
```

---

## 第七步：验证

全部完成后，浏览器打开 `https://api.你的域名.com`，搜个词测试。

也可以用命令行测试 API：

```bash
curl "https://api.你的域名.com/search?q=PHP&format=json"
```

应该返回 JSON 格式的搜索结果。

---

## 常见问题排查

### 502 Bad Gateway
→ PHP-FPM 没装或没启动：
```bash
sudo apt install php-fpm -y
sudo systemctl start php-fpm
sudo systemctl enable php-fpm
```
注意 `nginx.conf` 里的 `fastcgi_pass` 要和 php-fpm 的 socket 路径一致。

### 搜索返回空结果
→ 服务器 IP 可能被搜索引擎反爬。查看日志：
```bash
tail -f /var/log/nginx/error.log
```
解决方法：配代理或换 IP。

### 权限问题（403 Forbidden）
→ 重新设置目录权限：
```bash
sudo chown -R www-data:www-data /var/www/search-api
sudo chmod -R 755 /var/www/search-api
```

### PHP 版本过低
→ 升级到 PHP 8.x：
```bash
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
sudo apt install php8.3 php8.3-fpm php8.3-curl php8.3-dom php8.3-mbstring -y
```

---

## 完整的一键初始化脚本（全新 Ubuntu 服务器可用）

```bash
#!/bin/bash
# 在服务器上执行：sudo bash init.sh
set -e

echo "=== 安装环境 ==="
apt update && apt install -y nginx php php-fpm php-curl php-dom \
    php-mbstring php-json php-openssl unzip certbot python3-certbot-nginx

echo "=== 创建目录 ==="
mkdir -p /var/www/search-api/cache

echo "=== 设置权限 ==="
chown -R www-data:www-data /var/www/search-api
chmod -R 777 /var/www/search-api/cache

echo "=== 写入 Nginx 配置 ==="
cat > /etc/nginx/sites-available/search-api << 'NGINX'
server {
    listen 80;
    server_name _;   # 改成你的子域名
    root /var/www/search-api;
    index index.html index.php;

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
    
    location ~* \.(css|js|png|jpg|ico|svg|woff2)$ { expires 30d; add_header Cache-Control "public"; }
    location ~ /\.(ht|git|env) { deny all; }
}
NGINX

ln -sf /etc/nginx/sites-available/search-api /etc/nginx/sites-enabled/
rm -f /etc/nginx/sites-enabled/default
nginx -t && systemctl reload nginx

echo "=== 完成！==="
echo "1. 上传 baidu-search 文件夹内容到 /var/www/search-api/"
echo "2. 修改 /etc/nginx/sites-available/search-api 中的 server_name"
echo "3. 配置 DNS 解析"
echo "4. sudo certbot --nginx -d api.你的域名.com"
```
