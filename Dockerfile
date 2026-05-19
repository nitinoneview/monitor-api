FROM php:8.1-cli
RUN docker-php-ext-install pdo pdo_pgsql
WORKDIR /app
COPY . .
CMD ["php", "-S", "0.0.0.0:$PORT", "api.php"]
