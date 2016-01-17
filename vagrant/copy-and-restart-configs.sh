#!/usr/bin/env bash

sudo cp /vagrant/vagrant/pony.fm.nginx.config /etc/nginx/nginx.conf
sudo cp /vagrant/vagrant/pony.fm.nginx.site.config /etc/nginx/sites-enabled/pony.fm

sudo cp /vagrant/vagrant/php-overrides.ini /etc/php/7.0/fpm/99-overrides.ini

sudo cp /vagrant/vagrant/pony.fm.redis.config /etc/redis/redis.conf

sudo service elasticsearch restart
sudo service nginx restart
sudo service php7.0-fpm restart

# todo: figure out how to restart redis
