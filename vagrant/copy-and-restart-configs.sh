#!/usr/bin/env bash
sudo cp /vagrant/vagrant/pony.fm.nginx.config /etc/nginx/nginx.conf
sudo cp /vagrant/vagrant/pony.fm.nginx.site.config /etc/nginx/sites-enabled/pony.fm

sudo cp /vagrant/vagrant/php.ini /etc/php5/fpm/php.ini

sudo cp /vagrant/vagrant/pony.fm.mysql.config /etc/mysql/my.cnf

sudo cp /vagrant/vagrant/pony.fm.redis.config /etc/redis/redis.conf

sudo service nginx restart
sudo service php5-fpm restart

sudo service mysql restart

# todo: figure out how to restart redis
