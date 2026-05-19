FROM php:8.1-cli

RUN apt-get update && apt-get install -y \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql \
    && apt-get clean

WORKDIR /app
COPY . .

CMD php -S 0.0.0.0:$PORT api.php
