FROM php:8.3-fpm-alpine

# extensions utiles : pdo mysql, intl pour les dates, zip pour composer
RUN apk add --no-cache icu-dev libzip-dev \
    && docker-php-ext-install pdo_mysql intl zip

# composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY php.ini /usr/local/etc/php/conf.d/nrv.ini

# php-fpm garde les variables du .env
RUN echo "clear_env = no" >> /usr/local/etc/php-fpm.d/www.conf

WORKDIR /var/www/api
