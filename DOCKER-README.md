# Pony.fm Docker README

So, Pony.fm is being converted to run within a container on some new servers where it is hosted, thus it is now gaining
support for docker in the project. 

This guide is going to be an attempt to document how to run things in the new docker environment, until it's merged into
the main README expect things to be a WIP, and probably be partially documented or undocumented.

## Environment Setups

It is my aim to allow Pony.fm to be run in the following types of environments:

- App is run on the host with dependencies running in docker.
- App is run in docker, alongside all dependencies.

### App on host

In this the general idea is that you'd need to do the following

In your .env file you will refer to localhost for pretty much all services.

```
docker-compose up -d
```
```
cd public; php -c ../docker/php/php.ini -S localhost:8000 ../serve.php
```

TODO: Figure out if I can change the path that the local php server uses

Notes:
- `php artisan serve` cannot be configured to change ini settings, so max file upload is a problem.
- You can change the `FFMPEG_PREFIX` env var to `docker run -v \"$(pwd):$(pwd)\" -w \"$(pwd)\" jrottenberg/ffmpeg:4.3-alpine312` and that'll work as expected. (TODO: Same thing, but for AtomicParsley)

### App on docker

You need to install https://docker-sync.readthedocs.io/en/latest/getting-started/installation.html

** This method is primarily optimised for macOS **

In your .env file you will refer to the container names for all services. (I.E `DB_HOST=postgresd`, `ELASTICSEARCH_HOST=elasticsearch`)

Bring everything up
```
docker build . -t ponyfm
docker-sync start
docker-compose up -d
```

Notes:
- Initial sync is super slow, but watching is pretty fast
- You could skip docker-sync and change `appcode-sync` to `./` but expect request time to go from the `ms` to the many `s`'s.

Once everything is up and running

Create an alias to interact with the `artisan` cli tool:

```
alias p="docker compose exec web php artisan"
```

Then migrate and seed the app, and you should be good to go! 

```
p migrate --seed
```
