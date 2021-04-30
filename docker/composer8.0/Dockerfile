FROM php:8.0-fpm-alpine

RUN docker-php-ext-install mysqli pdo pdo_mysql

COPY --from=composer /usr/bin/composer /usr/bin/composer

ENTRYPOINT ["composer"]
