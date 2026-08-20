[![Pony.fm Logo](https://pony.fm/images/ponyfm-logo.svg)](https://pony.fm/)

The community for pony fan music.

For artists, Pony.fm features unlimited uploads and downloads, automatic
transcoding to a number of audio formats, and synchronized tags in all
downloads.

For listeners, Pony.fm offers unlimited streaming and downloading, user-generated
playlists, favourite lists, and a way of discovering new music with pony-based
taxonomies.


Contributing
------------
If you've run across a bug or have a feature request,
[open an issue](https://github.com/Poniverse/Pony.fm/issues/new)
for it.

For general questions and discussions about the site, stop by at
the [Pony.fm forum](https://mlpforums.com/forum/62-ponyfm/).

For quick fixes, go ahead and submit a pull request!

For larger features, it's best to open an issue before sinking a ton of work
into building them, to coordinate with Pony.fm's maintainers.

Developer documentation is available in the [`documentation` directory](documentation).

**Protip:** Looking for a place to jump in and start coding? Try a
[quickwin issue](https://github.com/Poniverse/Pony.fm/labels/quickwin%21) -
these are smaller in scope and easier to tackle if you're unfamiliar with the codebase!


Starting a dev environment
==========================

**Recommended:** the Docker-based setup described in
[`DOCKER-README.md`](DOCKER-README.md) — dependencies run in containers and
the app runs on your host.

The Vagrant environment below is the legacy setup and may bitrot:

To begin development, do the following:

1. Install [Vagrant](https://www.vagrantup.com/downloads.html) and
   [VirtualBox](https://www.virtualbox.org/wiki/Downloads) if you don't have them already.

2. Install the `vagrant-hostmanager` plugin: `vagrant plugin install vagrant-hostmanager`

3. Install the `vagrant-bindfs` plugin: `vagrant plugin install vagrant-bindfs`

4. Run `vagrant up` from the folder in which you cloned the repository

5. Run `vagrant ssh`, `cd /vagrant`, and `php artisan poni:setup`.

Once everything is up and running, you'll be able to access the site at [http://ponyfm-dev.poni/](http://ponyfm-dev.poni/). You can access the PostgreSQL database by logging into **ponyfm-dev.poni:5432** with the username **homestead** and the password **secret**. Pony.fm's database is named **homestead**.

Asset pipeline
--------------

Frontend assets are built with [Vite](https://vitejs.dev/). Install
dependencies with `pnpm install`, then run `pnpm dev` while developing
(or `pnpm build` for a production build).

### Email templates

Email templates are plain Blade views:

- [`resources/views/emails/html`](resources/views/emails/html), for HTML emails
- [`resources/views/emails/plaintext`](resources/views/emails/plaintext), for plaintext emails

In dev, sent mail is caught by Mailpit (started by `docker compose up -d`) and
viewable at [http://localhost:8025](http://localhost:8025).


Configuring the servers
-----------------------

Pony.fm uses nginx, php-fpm, redis, and PostgreSQL. You can modify the configuration of these services by locating the appropriate config file in the `vagrant` folder. Once modified, you must reload the configuration by running the appropriate shell script (`reload-config.sh`) or bat files (`reload-config.bat` and `reload-config.vmware.bat`). These scripts simply tell Vagrant to run `copy-and-restart-config.sh` on the VM.

If you need to change any other configuration file on the VM - copy the entire file over into the vagrant folder, make your changes, and update the `copy-and-restart-config.sh` script to copy the modified config back into the proper folder. All potential configuration requirements should be represented in the `vagrant` folder **and never only on the VM itself** as changes will not be preserved.
