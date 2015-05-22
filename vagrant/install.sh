sudo apt-get update

echo "Installing tagging tools"
sudo apt-get install -y AtomicParsley flac vorbis-tools

echo "Installing ffmpeg dependencies"
sudo apt-get install -y pkg-config yasm libfaac-dev libmp3lame-dev libvorbis-dev


if type ffmpeg &>/dev/null; then
	echo "ffmpeg is installed!"
else
	echo "ffmpeg is not installed; compiling..."
	cd /tmp
	wget "https://ffmpeg.org/releases/ffmpeg-2.6.3.tar.bz2"
	tar -xjf "ffmpeg-2.6.3.tar.bz2"
	cd "ffmpeg-2.6.3"
	./configure --enable-gpl --enable-encoder=flac --enable-encoder=alac --enable-libmp3lame --enable-libvorbis --enable-libfaac --enable-nonfree
	make -j4
	sudo make install
fi

mkdir /vagrant/logs
/vagrant/vagrant/copy-and-restart-configs.sh

cd /vagrant

/usr/local/bin/composer self-update
composer install

cp -r /vagrant/vagrant/config/* "/vagrant/app/config/local"

php artisan migrate
php artisan db:seed
