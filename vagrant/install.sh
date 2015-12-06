#!/usr/bin/env bash

echo "Running apt-get update..."
sudo apt-get -qq update

echo "Installing tagging tools..."
sudo apt-get -qq install -y AtomicParsley flac vorbis-tools imagemagick

echo "Installing ffmpeg dependencies.."
sudo apt-get -qq install -y pkg-config yasm libfaac-dev libmp3lame-dev libvorbis-dev libtheora-dev


if type ffmpeg &>/dev/null; then
    echo "ffmpeg is installed!"
else
    echo "ffmpeg is not installed; compiling..."
    cd /tmp
    wget "https://ffmpeg.org/releases/ffmpeg-2.6.3.tar.bz2"
    tar -xjf "ffmpeg-2.6.3.tar.bz2"
    cd "ffmpeg-2.6.3"
    ./configure --enable-gpl --enable-encoder=flac --enable-encoder=alac --enable-libmp3lame --enable-libvorbis --enable-libtheora --enable-libfaac --enable-nonfree
    make -j4
    sudo make install
fi

mkdir -p /vagrant/storage/logs/system
/vagrant/vagrant/copy-and-restart-configs.sh

cd /vagrant

hhvm /usr/local/bin/composer self-update
hhvm /usr/local/bin/composer install

cp -n "/vagrant/resources/environments/.env.local" "/vagrant/.env"

php artisan migrate
php artisan db:seed

echo ""
echo "+-----------------------------------------------+"
echo "| Now - if you haven't already, SSH into the VM |"
echo "| and run \`php artisan poni:setup\`!             |"
echo "| See the README for more details.              |"
echo "+-----------------------------------------------+"
