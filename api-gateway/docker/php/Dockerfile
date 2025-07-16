FROM php:8.3.21-fpm

WORKDIR /var/www/app

ARG uid=1000

RUN apt-get update && apt-get install -y --no-install-recommends \
    libonig-dev unzip git \
    && docker-php-ext-install mbstring pcntl \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2.5.8 /usr/bin/composer /usr/bin/composer

RUN usermod -u ${uid} www-data && groupmod -g ${uid} www-data && \
    mkdir -p /home/www-data/.composer /var/www/app && \
    chown -R www-data:www-data /home/www-data /var/www

    # Настройка логов
RUN mkdir -p /var/log && \
    touch /var/log/php_errors.log /var/log/php-fpm.log && \
    chown -R www-data:www-data /var/log && \
    chmod 664 /var/log/php_errors.log /var/log/php-fpm.log

COPY ./docker/php/config/php-dev.ini /usr/local/etc/php/conf.d/php.ini
COPY ./docker/php/config/php-fpm.conf /usr/local/etc/php-fpm.d/www.conf

COPY --chown=www-data:www-data . .

RUN composer install --optimize-autoloader --no-dev --no-interaction

RUN if [ -d "./storage" ]; then chmod -R 775 ./storage ./bootstrap/cache; fi

USER www-data