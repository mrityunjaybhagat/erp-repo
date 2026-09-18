# Stage 1: Composer dependencies
FROM composer:2 AS vendor

WORKDIR /app

COPY backend/composer.json backend/composer.lock ./

RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader \
    --no-scripts

COPY backend/ /app/

RUN composer dump-autoload --optimize


# Stage 2: Laravel runtime
FROM php:8.2-apache

RUN apt-get update && apt-get install -y \
    libzip-dev \
    libonig-dev \
    && docker-php-ext-install zip pdo pdo_mysql mbstring \
    && rm -rf /var/lib/apt/lists/*

RUN sed -i 's#DocumentRoot /var/www/html#DocumentRoot /var/www/html/public#g' \
    /etc/apache2/sites-available/000-default.conf \
    && a2enmod rewrite

RUN printf '<Directory /var/www/html/public>\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>\n' > /etc/apache2/conf-available/laravel.conf \
    && a2enconf laravel


WORKDIR /var/www/html

COPY --from=vendor /app /var/www/html

RUN chown -R www-data:www-data \
    /var/www/html/storage \
    /var/www/html/bootstrap/cache \
    /var/www/html/database

RUN chown -R www-data:www-data /var/www/html/storage \
    /var/www/html/bootstrap/cache