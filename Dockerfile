FROM php:8.2-fpm

# Установка зависимостей PHP
RUN apt-get update && apt-get install -y \
    libicu-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    && docker-php-ext-install intl pdo pdo_mysql zip

# Установка Composer внутри контейнера
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
