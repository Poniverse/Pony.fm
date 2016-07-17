#!/usr/bin/env bash


if type java &>/dev/null; then
    echo "Java is installed!"
else
    sudo add-apt-repository -y ppa:webupd8team/java
    echo /usr/bin/debconf shared/accepted-oracle-license-v1-1 select true | sudo debconf-set-selections
    echo /usr/bin/debconf shared/accepted-oracle-license-v1-1 seen true  | sudo debconf-set-selections
fi


if type /usr/share/elasticsearch/bin/elasticsearch &>/dev/null; then
    echo "ElasticSearch is installed!"
else
    wget -qO - https://packages.elastic.co/GPG-KEY-elasticsearch | sudo apt-key add -
    echo "deb http://packages.elastic.co/elasticsearch/2.x/debian stable main" | sudo tee -a /etc/apt/sources.list.d/elasticsearch-2.x.list
fi


echo "Running apt-get update..."
sudo apt-get -qq update

echo "Installing tagging tools & other dependencies..."
sudo apt-get -qq install -y AtomicParsley flac vorbis-tools imagemagick oracle-java8-installer elasticsearch pkg-config yasm libfaac-dev libmp3lame-dev libvorbis-dev libtheora-dev

echo "Installing PHP extensions"
sudo apt-get -qq install -y libgmp-dev php-gmp

echo "Installing Postgres migration tool"
sudo apt-get -qq install -y pgloader

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
