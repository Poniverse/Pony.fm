#!/usr/bin/env bash

# Homestead v10 defaults the "php" command to PHP 7.4. Pony.fm needs 7.0.
sudo ln -sf /usr/bin/php7.0 /usr/bin/php

if type java &>/dev/null; then
    echo "Java is installed!"
else
    #sudo apt-get install -y wget apt-transport-https gnupg
    wget -qO - https://adoptopenjdk.jfrog.io/adoptopenjdk/api/gpg/key/public | sudo apt-key add -
    echo "deb https://adoptopenjdk.jfrog.io/adoptopenjdk/deb focal main" | sudo tee /etc/apt/sources.list.d/adoptopenjdk.list

fi


if type /usr/share/elasticsearch/bin/elasticsearch &>/dev/null; then
    echo "ElasticSearch is installed!"
else
    wget -qO - https://packages.elastic.co/GPG-KEY-elasticsearch | sudo apt-key add -
    echo "deb http://packages.elastic.co/elasticsearch/2.x/debian stable main" | sudo tee /etc/apt/sources.list.d/elasticsearch-2.x.list
fi


echo "Running apt-get update..."
sudo apt-get update

echo "Installing tagging tools & other dependencies..."
sudo apt-get install -y elasticsearch
sudo apt-get install -y atomicparsley flac vorbis-tools imagemagick adoptopenjdk-8-openj9 pkg-config yasm libfaac-dev libmp3lame-dev libvorbis-dev libtheora-dev

echo "Installing PHP extensions"
sudo apt-get install -y libgmp-dev php-gmp php7.0-gmp

echo "Installing Postgres migration tool"
sudo apt-get install -y pgloader

if type ffmpeg &>/dev/null; then
    echo "ffmpeg is installed!"
else
    echo "ffmpeg is not installed; downloading..."
    cd /tmp
    wget -q "https://ffmpeg.org/releases/ffmpeg-2.6.3.tar.bz2"
    echo "Finished downloading ffmpeg; now compiling it..."
    tar -xjf "ffmpeg-2.6.3.tar.bz2"
    cd "ffmpeg-2.6.3"
    sudo ./configure --enable-gpl --enable-encoder=flac --enable-encoder=alac --enable-libmp3lame --enable-libvorbis --enable-libtheora --enable-libfaac --enable-nonfree
    sudo make -j4
    sudo make install
fi

mkdir -p /vagrant/storage/logs/system
/vagrant/vagrant/copy-and-restart-configs.sh

cd /vagrant

/usr/local/bin/composer self-update
/usr/local/bin/composer install

cp -n "/vagrant/resources/environments/.env.local" "/vagrant/.env"

php artisan migrate
php artisan db:seed

echo ""
echo "+-----------------------------------------------+"
echo "| Now - if you haven't already, SSH into the VM |"
echo "| and run \`php artisan poni:setup\`!             |"
echo "| See the README for more details.              |"
echo "+-----------------------------------------------+"
