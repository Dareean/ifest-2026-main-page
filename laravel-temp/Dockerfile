FROM php:8.4-cli-alpine

RUN apk add --no-cache postgresql-dev bash \
    && docker-php-ext-install pdo_pgsql pcntl

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-scripts

COPY . .

RUN composer dump-autoload && php artisan package:discover --ansi
RUN chmod +x startup.sh

EXPOSE 8000

CMD ["bash", "startup.sh"]
