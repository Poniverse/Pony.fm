mkdir /vagrant/logs
/vagrant/vagrant/copy-and-restart-configs.sh

cd /vagrant

/usr/local/bin/composer self-update
composer install

cp -r /vagrant/vagrant/config/* "/vagrant/app/config/local"

php artisan migrate
php artisan db:seed