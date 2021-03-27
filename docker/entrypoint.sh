#!/usr/bin/env sh

set -e

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
