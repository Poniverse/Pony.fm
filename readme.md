# Starting a dev environment
To begin development, you must do three things:
1. Ensure that you have the following hosts entries:
> 192.168.33.11		pony.fm.local  
> 192.168.33.11		api.pony.fm.local

2. Install the "vagrant-bindfs" plugin: `vagrant plugin install vagrant-bindfs`

3. Create the directory `pony.fm.files` in the repository's parent directory

4. Run `vagrant up` from the folder in which you cloned the repository

Once everything is up and running, you'll be able to access the site at http://pony.fm.local. You can access the MySQL database by logging into **192.168.33.11:3306** with the username of **homestead** and the password of **secret**. The pony.fm database is named **homestead**.

# Asset pipeline
Pony.fm uses gulp to mange its asset pipeline. **Important** due to everything being awful, you must run npm and gulp from your host machine and not the VM. You must first have it installed globally:
> npm install -g gulp

And then install all of the required local packages by invoking:
> npm install

Finally, build all of the scripts by executing:
> gulp build

During development, you should make a point to run "gulp watch". You can do this simply by executing:
> gulp watch

This will watch and compile the .less and .coffee files in real time.

# Configuring the servers
Pony.fm uses nginx, php-fpm, redis, and MySQL. You can modify the configuration of these services by locating the appropriate config file in the "vagrant" folder. Once modified, you must reload the configuration by running the appropriate shell script (**reload-config.sh**) or bat files (**reload-config.bat** and **reload-config.vmware.bat**). These scripts simply tell Vagrant to run "copy-and-restart-config.sh" on the VM.

If you need to change any other configuration file on the VM - copy the entire file over into the vagrant folder, make your changes, and update the "copy-and-restart-config.sh" script to copy the modified config back into the proper folder. All potential configuration requirements should be represented in the vagrant folder **and never only on the VM itself** as changes will not be preserved.

**NOTE:** currently, Redis' configuration is not reloaded by the "copy-and-restart-config.sh"