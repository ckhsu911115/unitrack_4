FROM php:8.1-cli

# 安裝必要套件
RUN apt-get update && apt-get install -y \
    unzip \
    libzip-dev \
    libonig-dev \
    libxml2-dev \
    default-mysql-client \
    && docker-php-ext-install zip pdo pdo_mysql

# 設定工作目錄
WORKDIR /app

# 複製你整個專案
COPY . .

# 啟動 PHP 內建伺服器
CMD ["php", "-S", "0.0.0.0:3000", "-t", "."]
