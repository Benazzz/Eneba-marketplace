# Production image for Render: PHP 8.4 + Apache (mod_php) serving Symfony from public/.
FROM php:8.4-apache

# System libraries + PHP extensions.
#  - libpq-dev: PostgreSQL client library, needed to compile and run pdo_pgsql
#  - unzip:     lets Composer extract downloaded packages
#  - opcache:   caches compiled PHP bytecode in memory (big speedup in production)
RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev unzip \
    && docker-php-ext-install pdo_pgsql opcache \
    && rm -rf /var/lib/apt/lists/*

# PHP's recommended production settings (errors logged, not displayed, etc.),
# plus our overrides (conf.d files load after php.ini; "zz-" makes ours load last).
RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY docker/php.ini "$PHP_INI_DIR/conf.d/zz-app.ini"

# Apache: listen on $PORT (Render sets it; 80 is the local default) and use our vhost.
ENV PORT=80
RUN echo 'Listen ${PORT}' > /etc/apache2/ports.conf
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf

# Composer binary, copied from the official Composer image.
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

ENV APP_ENV=prod
WORKDIR /var/www/html

# 1) Dependencies first: this layer is cached until composer.json/lock change,
#    so code-only changes rebuild in seconds.
COPY composer.json composer.lock symfony.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-progress --no-interaction

# 2) Application code, then:
#  - an optimized class map (no filesystem lookups to find classes)
#  - the prod cache (compiled DI container, routes) built now, so the first request after a
#    cold start is fast. Env vars like DATABASE_URL are read at runtime, not baked in here.
#  - var/ owned by www-data, the user Apache workers run as (they write cache and logs)
#  - start.sh made executable (Windows checkouts don't keep the executable bit)
COPY . .
RUN composer dump-autoload --no-dev --classmap-authoritative \
    && php bin/console cache:warmup \
    && chown -R www-data:www-data var \
    && chmod +x docker/start.sh

CMD ["docker/start.sh"]
