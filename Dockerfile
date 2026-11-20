# Image de production du site L'ÂME PEÏ (utilisée par Render, voir render.yaml et docs/DEPLOIEMENT.md)
FROM php:8.4-apache

# Extensions PHP nécessaires : MySQL, traduction (intl), images (gd + exif), zip, cache d'opcodes
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git unzip ca-certificates \
        libicu-dev libzip-dev libpng-dev libjpeg62-turbo-dev libwebp-dev libfreetype6-dev \
    && docker-php-ext-configure gd --with-jpeg --with-webp --with-freetype \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql intl zip gd exif opcache \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

ENV APP_ENV=prod \
    APP_DEBUG=0 \
    COMPOSER_ALLOW_SUPERUSER=1

WORKDIR /var/www/html

# 1. Dépendances PHP (couche mise en cache tant que composer.lock ne change pas)
COPY composer.json composer.lock symfony.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-progress --no-interaction

# 2. Code du site, autoload optimisé, scripts Symfony (cache, assets, importmap), assets compilés
COPY . .
RUN composer dump-autoload --no-dev --classmap-authoritative \
    && composer run-script --no-dev post-install-cmd \
    && php bin/console asset-map:compile \
    && chown -R www-data:www-data var public/uploads

COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf
COPY docker/php.ini "$PHP_INI_DIR/conf.d/zz-app.ini"
COPY docker/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

# Render fournit le port dans la variable PORT (10000 par défaut)
EXPOSE 10000
CMD ["entrypoint"]
