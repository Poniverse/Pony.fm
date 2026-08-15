#!/usr/bin/env sh

set -e

# If we have an .env file then we're likely running on a dev machine
#  in which case auto optimization on start up is not necessary.
if [ ! -f .env ]; then
  sudo -Esu www-data php artisan optimize
fi

MODE=$1

case $MODE in
  web)
    exec sudo -Esu www-data php artisan octane:start \
      --server=frankenphp \
      --caddyfile=docker/frankenphp/Caddyfile \
      --host=0.0.0.0 \
      --port=8080
    ;;

  worker)
    sudo -Esu www-data php artisan queue:listen --queue=default,notifications,indexing --sleep=5 --tries=3
    ;;

  artisan)
    shift
    sudo -Esu www-data php artisan "$@"
    ;;

  *)
    echo "Unknown mode given" >&2
    exit 1
    ;;
esac
