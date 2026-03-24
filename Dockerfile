FROM php:7.4.33-cli-alpine3.16

RUN apk add --no-cache git unzip zip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY composer.json composer.lock* ./

RUN composer install --no-interaction --prefer-dist --no-progress

COPY . .

CMD ["vendor/bin/phpunit"]