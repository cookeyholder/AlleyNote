# 使用官方 PHP 8.4 CLI 版本的 Trixie 映像
FROM php:8.4-cli-trixie

# 安裝基礎開發工具
RUN apt-get update && export DEBIAN_FRONTEND=noninteractive \
    && apt-get -y install --no-install-recommends \
    git \
    libfreetype6-dev \
    libicu-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    libsqlite3-dev \
    libwebp-dev \
    libzip-dev \
    openssh-client \
    sudo \
    unzip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# 安裝 PHP 常用擴展並配置 GD
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install gd intl pdo pdo_sqlite zip

# 從官方映像複製 Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 建立一個 vscode 使用者以符合 Dev Container 慣例
RUN useradd -m vscode -s /bin/bash
RUN usermod -aG sudo vscode \
    && echo "vscode ALL=(ALL) NOPASSWD:ALL" > /etc/sudoers.d/vscode \
    && chmod 0440 /etc/sudoers.d/vscode
USER vscode
