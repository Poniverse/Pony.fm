Vagrant.configure("2") do |config|

  config.hostmanager.enabled = true
  config.hostmanager.manage_host = true

  config.vm.box = 'laravel/homestead-7'
  config.vm.box_version = '0.2.0'
  config.vm.provider "virtualbox" do |v|
    v.cpus = 4
    v.memory = 2048
  end

  config.vm.define 'default' do |node|
    node.vm.hostname = 'ponyfm-dev.poni'
    node.vm.network :private_network, ip: "192.168.33.11"
    node.hostmanager.aliases = %w(api.ponyfm-dev.poni)
  end

  config.vm.synced_folder ".", "/vagrant", type: "nfs"

  config.vm.provision "shell", path: "vagrant/install.sh"

  config.vm.network "forwarded_port", guest: 3306, host: 33060

  config.vm.synced_folder "../pony.fm.files", "/vagrant-files", type: "nfs"
  config.bindfs.bind_folder "/vagrant", "/vagrant"
end
