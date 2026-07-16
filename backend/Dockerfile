FROM php:8.4-cli

RUN apt-get update && apt-get install -y \
    git unzip libzip-dev libsqlite3-dev libpq-dev \
    && docker-php-ext-install zip pdo pdo_sqlite pdo_pgsql bcmath ctype \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

COPY composer.json composer.lock ./
RUN composer install --no-interaction --no-cache --optimize-autoloader --no-scripts

COPY . .

RUN composer run post-autoload-dump \
    && mkdir -p database storage/framework storage/logs storage/app bootstrap/cache \
    && chmod -R 777 bootstrap/cache storage database

RUN chmod +x startup.sh

EXPOSE 8000

CMD ["bash", "startup.sh"]

