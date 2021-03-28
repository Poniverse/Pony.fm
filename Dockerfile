FROM jrottenberg/ffmpeg:4.3-alpine312 as ffmpeg
FROM node:12-alpine as assets_builder

# To handle 'not get uid/gid'
RUN npm config set unsafe-perm true

RUN npm install -g gulp

WORKDIR /app

RUN mkdir -p /app/resources

COPY package.json /app

RUN npm install

COPY gulpfile.js /app
COPY webpack.base.config.js /app
COPY webpack.dev.config.js /app
COPY webpack.production.config.js /app
COPY resources /app/resources

RUN gulp build

FROM php:8.0-fpm-alpine

ENV LD_LIBRARY_PATH=/usr/local/lib:/usr/local/lib64

COPY --from=ffmpeg /usr/local /usr/local
COPY --from=composer /usr/bin/composer /usr/bin/composer
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/bin/install-php-extensions

## Common libraries required for ffmpeg to work
RUN apk add --no-cache libgcc libstdc++ ca-certificates libcrypto1.1 libssl1.1 libgomp expat git
RUN apk add --no-cache nginx sudo

# Install php extensions
RUN install-php-extensions mysqli pgsql pdo_mysql pdo_pgsql gmp gmagick

RUN mkdir /app && chown -R www-data: /app

USER www-data
WORKDIR /app

COPY --chown=www-data composer.json /app
COPY --chown=www-data composer.lock /app

RUN composer install --no-scripts --no-autoloader --ignore-platform-reqs

COPY --chown=www-data --from=assets_builder /app /app
COPY --chown=www-data . /app

RUN composer dump-autoload -o
RUN php artisan optimize

USER root

# Remove files no longer needed on the host
RUN rm /usr/bin/composer /usr/bin/install-php-extensions

COPY docker/nginx/site.conf /etc/nginx/conf.d/default.conf

## Install AtomicParsley
RUN curl -s https://api.github.com/repos/wez/atomicparsley/releases/latest \
  | grep "browser_download_url.*Linux" \
  | cut -d '"' -f 4 \
  | xargs curl -sLo AtomicParsleyLinux.zip \
  && unzip AtomicParsleyLinux.zip \
  && rm AtomicParsleyLinux.zip \
  && mv AtomicParsley /usr/local/bin/AtomicParsley

EXPOSE 80

ENTRYPOINT ["docker/entrypoint.sh"]
