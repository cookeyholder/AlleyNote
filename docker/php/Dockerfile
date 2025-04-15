FROM php:8.1-fpm

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

# 複製專案檔案
COPY . .

# 執行 composer install 安裝相依套件
RUN composer install --no-interaction --no-scripts --no-progress --prefer-dist

# 設定權限
RUN chown -R www-data:www-data /var/www/html
