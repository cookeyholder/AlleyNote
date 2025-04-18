FROM php:8.1-fpm AS base

# 安裝基礎套件
RUN apt-get update && apt-get install -y \
    git \
    zip \
    unzip \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev

# 設定 GD 擴充功能
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd pdo pdo_mysql

# 安裝 Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 設定工作目錄
WORKDIR /var/www/html

# 複製所有專案檔案
COPY . .

# 安裝相依套件
RUN composer install --no-interaction --no-scripts --no-progress --prefer-dist

# 設定 git 安全目錄
RUN git config --global --add safe.directory /var/www/html

# 設定正確的權限
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage

# 建立測試環境的映像檔
FROM base AS test

# 安裝 Xdebug 和 PCNTL
RUN pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && docker-php-ext-install pcntl

# 複製 Xdebug 設定
COPY docker/php/conf.d/xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini

# 建立必要的目錄
RUN mkdir -p /var/www/html/storage/coverage \
    && mkdir -p /var/www/html/storage/logs \
    && mkdir -p /var/www/html/storage/cache \
    && chown -R www-data:www-data /var/www/html/storage
