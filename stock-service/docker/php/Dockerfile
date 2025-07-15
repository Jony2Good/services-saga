FROM php:8.3.4-fpm AS php

WORKDIR /var/www/app

ARG uid=1000

RUN apt-get update && apt-get install -y --no-install-recommends \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libpq-dev \
    zip \
    unzip \
    git \   
    && docker-php-ext-install \
        pdo_mysql \
        pdo_pgsql \
        pgsql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        sockets \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2.5.8 /usr/bin/composer /usr/bin/composer

# Настройка пользователя и прав
RUN usermod -u ${uid} www-data && \
    groupmod -g ${uid} www-data && \
    mkdir -p /home/www-data/.composer /var/www/.npm /var/www/app && \
    chown -R www-data:www-data /home/www-data /var/www

# Настройка логов
RUN mkdir -p /var/log && \
    touch /var/log/php_errors.log /var/log/php-fpm.log && \
    chown -R www-data:www-data /var/log && \
    chmod 664 /var/log/php_errors.log /var/log/php-fpm.log

# Копируем конфиги PHP
COPY ./docker/php/config/php-dev.ini /usr/local/etc/php/conf.d/php.ini
COPY ./docker/php/config/php-fpm.conf /usr/local/etc/php-fpm.d/www.conf

USER www-data

COPY --chown=www-data:www-data composer.json composer.lock package*.json ./

# Устанавливаем Composer зависимости
RUN composer install --no-scripts --no-autoloader --prefer-dist --no-progress --no-interaction

# Копируем проект
COPY --chown=www-data:www-data . .

# Генерируем автозагрузку Composer
RUN composer dump-autoload --optimize

# Настроим права доступа для storage и bootstrap/cache
RUN if [ -d "./storage" ]; then chmod -R 775 ./storage ./bootstrap/cache; fi

USER www-data
