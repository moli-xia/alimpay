ARG PHP_VERSION=7.4
FROM php:${PHP_VERSION}-apache

ENV COMPOSER_ALLOW_SUPERUSER=1

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        curl \
        git \
        libcurl4-openssl-dev \
        libonig-dev \
        libsqlite3-dev \
        libxml2-dev \
        unzip \
    && docker-php-ext-install \
        bcmath \
        curl \
        dom \
        mbstring \
        pdo \
        pdo_sqlite \
    && a2enmod headers rewrite \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress --no-scripts

COPY . .
COPY docker-entrypoint.sh /usr/local/bin/alimpay-entrypoint

RUN composer dump-autoload --no-dev --optimize \
    && chmod +x /usr/local/bin/alimpay-entrypoint \
    && mkdir -p data logs qrcodes \
    && chown -R www-data:www-data /var/www/html

EXPOSE 80

HEALTHCHECK --interval=30s --timeout=5s --start-period=15s --retries=3 \
    CMD curl -fsS http://127.0.0.1/health.php || exit 1

ENTRYPOINT ["alimpay-entrypoint"]
CMD ["apache2-foreground"]
