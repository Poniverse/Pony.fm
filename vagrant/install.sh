mkdir /vagrant/logs
/vagrant/vagrant/copy-and-restart-configs.sh

cd /vagrant

/usr/local/bin/composer self-update
composer install

sudo npm install -g bower
sudo npm install -g coffee-script
sudo npm install -g less

bower install --allow-root

cp -r /vagrant/vagrant/config/* "/vagrant/app/config/local"

php artisan migrate
php artisan db:seed