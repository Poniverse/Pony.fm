Vagrant.configure("2") do |config|
	config.vm.box = 'laravel/homestead'

	config.vm.network :private_network, ip: "192.168.33.11"
	config.vm.synced_folder ".", "/vagrant", type: "nfs"
	
	config.vm.provision "shell", path: "vagrant/install.sh"

	config.vm.synced_folder "../pony.fm.files", "/vagrant-files"
	config.bindfs.bind_folder "/vagrant", "/vagrant"
end
