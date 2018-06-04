#!/usr/bin/env bash

echo "Updating config files and restarting services..."
echo

mkdir -p /vagrant/storage/app/datastore

sudo cp /vagrant/vagrant/pony.fm.nginx.config /etc/nginx/nginx.conf &
sudo cp /vagrant/vagrant/pony.fm.nginx.site.config /etc/nginx/sites-enabled/pony.fm &
sudo cp /vagrant/vagrant/php-overrides.ini /etc/php/7.2/cli/conf.d/99-overrides.ini &
sudo cp /vagrant/vagrant/php-overrides.ini /etc/php/7.2/fpm/conf.d/99-overrides.ini &
sudo cp /vagrant/vagrant/pony.fm.redis.config /etc/redis/redis.conf &
sudo cp /vagrant/vagrant/pony.fm.supervisor.config /etc/supervisor/conf.d/pony.fm.conf &
wait

sudo service supervisor start
sudo service beanstalkd start

sudo supervisorctl update &
sudo service elasticsearch restart &
sudo service nginx restart &
sudo service php7.2-fpm restart &
wait
