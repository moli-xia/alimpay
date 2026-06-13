# AliMPay 轻量部署说明

## 环境要求

- PHP 7.4 或更高版本
- Composer 2
- PHP 扩展：bcmath、curl、dom、json、mbstring、openssl、pdo、pdo_sqlite

这版已移除未使用的 `alipaysdk/easysdk`、`vlucas/phpdotenv`、`phpseclib/bcmath_compat` 和强制 PHP 8 的二维码库依赖，并把 Monolog 降到 PHP 7.2+ 可用的 2.x 分支。支付宝 OpenAPI SDK 当前要求 PHP 7.4+，所以项目最低版本保持为 PHP 7.4。

## 普通 VPS 部署

```bash
composer install --no-dev --prefer-dist --optimize-autoloader
cp config/alipay.example.php config/alipay.php
mkdir -p data logs qrcodes
chmod -R 755 data logs qrcodes
```

然后编辑 `config/alipay.php`，填入支付宝应用参数，并把 Web 根目录指向项目根目录。

运行时会生成 `data/`、`logs/`、`qrcodes/`、`config/codepay.json` 和 `container_monitor.php`，这些文件已加入 Git 忽略。

## Docker 部署

```bash
docker build -t superneed/alimpay:latest .
docker run -d --name alimpay -p 8080:80 \
  -v alimpay-data:/var/www/html/data \
  -v alimpay-logs:/var/www/html/logs \
  -v /path/to/alipay.php:/var/www/html/config/alipay.php:ro \
  -v /path/to/business_qr.png:/var/www/html/qrcode/business_qr.png:ro \
  superneed/alimpay:latest
```

如果 VPS 只能使用更老的 PHP 7.4 小版本，默认镜像已经基于 PHP 7.4 构建。PHP 7.3 不建议作为目标版本，因为支付宝 OpenAPI SDK 的公开依赖约束要求 PHP 7.4+。

## 依赖精简记录

- `monolog/monolog`：从 `^3.0` 降为 `^2.5`，避免 PHP 8.1+ 要求。
- `endroid/qr-code`：移除强依赖，二维码改为通过在线 API 生成，减少 GD/二维码库安装压力。
- `alipaysdk/easysdk`、`vlucas/phpdotenv`、`phpseclib/bcmath_compat`：代码未直接使用，已从直接依赖中移除。
- 明确声明 PHP 扩展依赖，Composer 会在缺少扩展时直接提示。
