FROM php:8.1-cli

# 安裝 PHP 擴充
RUN apt-get update && apt-get install -y unzip libzip-dev && docker-php-ext-install zip

# 設定工作目錄
WORKDIR /app

# 複製所有專案檔案
COPY . .

# 啟動 PHP 內建伺服器
CMD ["php", "-S", "0.0.0.0:3000", "-t", "."]
