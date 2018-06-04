Vagrant.configure("2") do |config|

  config.hostmanager.enabled = true
  config.hostmanager.manage_host = true

  config.vm.box = 'aspyatkin/ubuntu-16.04-server-amd64'
  config.vm.box_version = '2.1.0'

  config.vm.provider "virtualbox" do |v|
    v.cpus = 4
    v.memory = 1024
  end

  config.vm.define 'default' do |node|
    node.vm.hostname = 'ponyfm-dev.poni'
    node.vm.network :private_network, ip: "192.168.33.11"
    node.vm.network "forwarded_port", guest: 80, host: 8080
    node.vm.network "forwarded_port", guest: 5432, host: 5433
    node.hostmanager.aliases = %w(api.ponyfm-dev.poni)
  end

  config.vm.synced_folder ".", "/vagrant", type: "nfs"
  config.bindfs.bind_folder "/vagrant", "/vagrant"

  config.vm.provision "shell", path: "vagrant/install.sh"
  config.vm.provision "shell", path: "vagrant/copy-and-restart-configs.sh", run: "always"
end
