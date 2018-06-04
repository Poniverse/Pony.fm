#!/usr/bin/env bash

echo "debconf debconf/frontend select noninteractive" | sudo debconf-set-selections
update-locale LANG=en_US.UTF-8
locale-gen --purge en_US.UTF-8
dpkg-reconfigure --frontend noninteractive locales

package_installed(){
	if dpkg-query -f '${binary:Package}\n' -W | grep "$1" &>/dev/null; then
		return 0;
	else
		return 1;
	fi
}

add_key(){
	wget -qO - "$1" | sudo apt-key add - &>/dev/null
}

install_packages(){
	sudo apt-get -qq install $@ >/dev/null
}

if ! package_installed elasticsearch; then
    add_key https://packages.elastic.co/GPG-KEY-elasticsearch
    echo "deb https://packages.elastic.co/elasticsearch/2.x/debian stable main" > /etc/apt/sources.list.d/elasticsearch-2.x.list
    echo "ElasticSearch repository added"
fi

if ! package_installed postgresql-10; then
    add_key https://www.postgresql.org/media/keys/ACCC4CF8.asc
    echo "deb http://apt.postgresql.org/pub/repos/apt/ xenial-pgdg main" > /etc/apt/sources.list.d/pgdg.list
    echo "PostgreSQL repository added"
fi

if ! package_installed php7.2-fpm; then
	sudo add-apt-repository ppa:ondrej/php &>/dev/null
    echo "PHP repository added"
fi

if ! package_installed nginx; then
    add_key http://nginx.org/keys/nginx_signing.key
	echo "deb http://nginx.org/packages/ubuntu/ xenial nginx" > /etc/apt/sources.list.d/nginx.list
    echo "nginx repository added"
fi

echo "Running apt-get update..."
sudo apt-get -qq update >/dev/null

echo "Running apt-get upgrade..."
sudo apt-get -qq upgrade >/dev/null

echo "Installing nginx, PHP and PostgreSQL..."
install_packages php7.2-fpm nginx postgresql-10
mkdir -p /etc/nginx/sites-enabled
chown vagrant:vagrant /run/php/php7.2-fpm.sock
chown -R vagrant:vagrant /var/cache/nginx
sed -i -e 's/www-data/vagrant/' /etc/php/7.2/fpm/pool.d/www.conf

echo "Installing PHP extensions..."
install_packages libgmp-dev php-gmp php7.2-gmp php-xdebug php-redis php7.2-mbstring php7.2-curl php7.2-dom php7.2-pg \
                 php7.2-gd

echo "Creating homestead Postgres user and database..."
sudo -u postgres psql -c "CREATE USER homestead PASSWORD 'secret';" >/dev/null
sudo -u postgres psql -c "CREATE DATABASE homestead OWNER homestead;" >/dev/null

echo "Installing Elasticsearch..."
install_packages apt-transport-https && install_packages elasticsearch

echo "Installing tagging tools & other dependencies..."
install_packages build-essential supervisor atomicparsley flac vorbis-tools imagemagick openjdk-8-jre pkg-config yasm \
				 libfaac-dev libmp3lame-dev libvorbis-dev libtheora-dev redis-server beanstalkd curl unzip

echo "debconf debconf/frontend select dialog" | sudo debconf-set-selections

echo "Installing Composer..."
curl -sS https://getcomposer.org/installer | sudo php -- --install-dir=/usr/local/bin --filename=composer

# Remove beanstalkd.socket symlink because we only use the service
rm /lib/systemd/system/beanstalkd.socket

if type ffmpeg &>/dev/null; then
    echo "ffmpeg is installed!"
else
    echo "ffmpeg is not installed; compiling..."
    cd /usr/local/src
    wget -q "https://ffmpeg.org/releases/ffmpeg-2.6.3.tar.bz2"
    tar -xjf "ffmpeg-2.6.3.tar.bz2"
    cd "ffmpeg-2.6.3"
    ./configure --enable-gpl --enable-encoder=flac --enable-encoder=alac --enable-libmp3lame --enable-libvorbis --enable-libtheora --enable-libfaac --enable-nonfree
    make -j4
    sudo make install
fi

mkdir -p /vagrant/storage/logs/system
/vagrant/vagrant/copy-and-restart-configs.sh

cd /vagrant

echo "Running composer install..."
composer install

cp -n "/vagrant/resources/environments/.env.local" "/vagrant/.env"

php artisan migrate
php artisan db:seed

echo ""
echo "+-----------------------------------------------+"
echo "| Now - if you haven't already, SSH into the VM |"
echo "| and run \`php artisan poni:setup\`!             |"
echo "| See the README for more details.              |"
echo "+-----------------------------------------------+"
