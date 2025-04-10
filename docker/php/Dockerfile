FROM php:8.4.5-fpm

# 安裝相依套件
RUN apt-get update && apt-get install -y \
    git \
    zip \
    unzip \
    libzip-dev \
    libsqlite3-dev

# 安裝 PHP 擴充功能
RUN docker-php-ext-install pdo pdo_sqlite zip

# 安裝 Xdebug
RUN pecl install xdebug && docker-php-ext-enable xdebug

# 配置 Xdebug
RUN echo "xdebug.mode=coverage" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

# 安裝 Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 設定工作目錄
WORKDIR /var/www/html

# 設定權限
RUN chown -R www-data:www-data /var/www/html