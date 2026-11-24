FROM php:8.4-fpm-alpine

RUN apk add --no-cache nginx nodejs npm supervisor bash icu-dev libzip-dev postgresql-dev sqlite-dev \
    && docker-php-ext-install bcmath intl opcache pcntl pdo_pgsql pdo_sqlite zip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .

RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader \
    && npm ci \
    && npm run build \
    && mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/supervisord.conf /etc/supervisord.conf
COPY docker/entrypoint.sh /usr/local/bin/taskmate-entrypoint
RUN chmod +x /usr/local/bin/taskmate-entrypoint

EXPOSE 10000

ENTRYPOINT ["/usr/local/bin/taskmate-entrypoint"]
