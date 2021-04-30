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
    php-fpm -D
    nginx -g 'pid /tmp/nginx.pid; daemon off;'
    ;;

  worker)
    sudo -Esu www-data php artisan queue:listen --queue=default,notifications,indexing --sleep=5 --tries=3
    ;;

  *)
    echo "Unknown mode given"
    ;;
esac
