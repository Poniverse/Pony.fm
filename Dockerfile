FROM jrottenberg/ffmpeg:7.1-alpine320 AS ffmpeg

FROM alpine:3.12 AS atomicparsley_builder

RUN apk add --no-cache make cmake linux-headers g++ git
RUN git clone https://github.com/wez/atomicparsley.git /tmp/atomicparsley

RUN cd /tmp/atomicparsley \
  && cmake . \
  && cmake --build . --config Release

FROM node:26-alpine AS assets_builder

# Node 25+ no longer bundles corepack; it activates the pnpm version pinned
# by package.json's packageManager field.
RUN npm install -g corepack && corepack enable

WORKDIR /app

COPY package.json pnpm-lock.yaml pnpm-workspace.yaml /app/

RUN pnpm install --frozen-lockfile

COPY vite.config.ts tsconfig.json /app/
COPY resources /app/resources/

# Builds the client bundle into public/assets and the Inertia SSR bundle
# into bootstrap/ssr.
RUN pnpm run build

# Standalone Inertia SSR server. Run with:
#   docker build --target ssr
FROM node:26-alpine AS ssr

WORKDIR /app

COPY --from=assets_builder /app/bootstrap/ssr /app/bootstrap/ssr

EXPOSE 13714

CMD ["node", "bootstrap/ssr/ssr.js"]

FROM dunglas/frankenphp:1-php8.4-alpine

ENV LD_LIBRARY_PATH=/usr/local/lib:/usr/local/lib64

COPY --from=ffmpeg /usr/local /usr/local
COPY --from=composer /usr/bin/composer /usr/bin/composer
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/bin/install-php-extensions
COPY --from=atomicparsley_builder /tmp/atomicparsley/AtomicParsley /usr/local/bin/AtomicParsley

RUN apk update

## Common libraries required for ffmpeg & atomicparsley` to work
RUN apk add libgcc libstdc++ ca-certificates libcrypto3 libssl3 libgomp expat git
RUN apk add sudo

# Tag writers getID3 shells out to: metaflac (FLAC) and vorbiscomment (OGG).
RUN apk add flac vorbis-tools

# Install php extensions. gd is for color-thief (avatar colour extraction);
# image resizing shells out to imagemagick's convert.
RUN install-php-extensions mysqli pgsql pdo_mysql pdo_pgsql gmp gd redis pcntl opcache

# not sure why but this needs to be after the php extensions otherwise some kind of dependency issue occurs
RUN apk add imagemagick

# Caddy (inside FrankenPHP) needs writable state dirs when running as www-data.
RUN mkdir -p /config /data && chown -R www-data: /config /data

# The frankenphp base image ships /app as its default workdir.
RUN mkdir -p /app && chown -R www-data: /app

USER www-data
WORKDIR /app

COPY --chown=www-data composer.json /app
COPY --chown=www-data composer.lock /app

RUN composer install --no-scripts --no-autoloader --ignore-platform-reqs

COPY --chown=www-data . /app
COPY --chown=www-data --from=assets_builder /app/public/assets /app/public/assets
COPY --chown=www-data --from=assets_builder /app/bootstrap/ssr /app/bootstrap/ssr

RUN composer dump-autoload -o

# Octane's stub Caddyfile serves the worker from {$APP_PUBLIC_PATH};
# this is the same copy `octane:install` performs, minus its side effects.
RUN cp vendor/laravel/octane/src/Commands/stubs/frankenphp-worker.php public/frankenphp-worker.php

RUN php artisan optimize

USER root

# Remove files no longer needed on the host
RUN rm /usr/bin/composer /usr/bin/install-php-extensions

COPY docker/php/php.ini /usr/local/etc/php/conf.d/php.ini

EXPOSE 8080

ENTRYPOINT ["docker/entrypoint.sh"]
