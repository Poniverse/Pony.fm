sudo add-apt-repository -y ppa:kirillshkrogalev/ffmpeg-next
sudo apt-get update
sudo apt-get install -y ffmpeg
sudo apt-get install -y AtomicParsley
sudo add-apt-repository -y --remove ppa:kirillshkrogalev/ffmpeg-next

mkdir /vagrant/logs
/vagrant/vagrant/copy-and-restart-configs.sh

cd /vagrant

/usr/local/bin/composer self-update
composer install

cp -r /vagrant/vagrant/config/* "/vagrant/app/config/local"

php artisan migrate
php artisan db:seed