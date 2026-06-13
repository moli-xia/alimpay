#!/bin/sh
set -eu

cd /var/www/html

mkdir -p data logs qrcodes

if [ ! -f config/alipay.php ] && [ -f config/alipay.example.php ]; then
    cp config/alipay.example.php config/alipay.php
fi

chown -R www-data:www-data data logs qrcodes config || true

exec "$@"
