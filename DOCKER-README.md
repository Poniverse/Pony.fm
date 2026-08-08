# Pony.fm Docker development

Pony.fm runs in a container in production (nginx + php-fpm + ffmpeg +
AtomicParsley all baked into one image — see `Dockerfile`). For local
development the recommended setup is:

- **Dependencies in Docker** — `docker compose up -d` gives you Postgres and
  Elasticsearch (and optionally beanstalkd).
- **The app on the host** — `composer serve` for PHP, `yarn dev` for the
  asset watcher.
- **ffmpeg & AtomicParsley via `docker run`** — shims in `docker/bin/` mean
  you don't need either installed locally.

## Prerequisites

- Docker (Desktop or equivalent)
- PHP 8.0 with composer
- Node 12 with yarn — the gulp 4 / webpack 1 asset toolchain predates modern
  Node; use nvm/volta, or skip local Node entirely and use the `assets`
  compose profile below.

You do **not** need ffmpeg, AtomicParsley, Postgres, or Elasticsearch
installed on your machine.

## Quick start

If you have [`just`](https://github.com/casey/just), `just setup` runs all of
the below, then it's `just dev` (deps + PHP server) and `just assets` (the
watcher) in a second terminal — see `just --list` for the rest. By hand:

```sh
cp .env.example .env       # works as-is, no editing needed
docker compose up -d       # postgres + elasticsearch

composer install
php artisan migrate --seed

composer serve             # app on http://localhost:8000
```

And in a second terminal, the asset watcher (leave it running while you
develop):

```sh
yarn install
yarn dev
```

If your host Node is too new for the old gulp toolchain (Node 17+ breaks
webpack 1), run the watcher in a Node 12 container instead — `just dev` does
this automatically when it detects a too-new host Node:

```sh
docker compose --profile assets up
```

The container is x86-64 Debian (emulated on Apple Silicon) because the email
pipeline's phantomjs/imagemin dependencies only ship x86-64 glibc binaries.
The first start runs a full `yarn install` and is slow; after that the
`node_modules` volume is reused.

## How ffmpeg & AtomicParsley work in dev

The app shells out to `ffmpeg` (upload validation, transcoding) and
`AtomicParsley` (MP4 tagging) by name. `composer serve` and `composer queue`
prepend `docker/bin/` to `PATH`, where shim scripts forward those calls to
`docker run`:

- `docker/bin/ffmpeg` → `jrottenberg/ffmpeg:4.3-alpine312` (same build the
  production image uses)
- `docker/bin/AtomicParsley` → an image built from `docker/AtomicParsley/`
  on first use

The shims mount the project directory and temp directories at their host
paths, so the file paths the app passes resolve identically inside the
container. Expect a little `docker run` startup latency on each call — fine
for dev.

If you *do* have ffmpeg/AtomicParsley installed locally and would rather use
them, just run `php -S 127.0.0.1:8000 -t public server.php` directly instead
of `composer serve` (note: `php artisan serve` can't raise the upload size
limits, which is why the composer script exists).

## Queues

`.env.example` sets `QUEUE_CONNECTION=sync`, so transcoding/indexing jobs run
inline in the request — no worker needed, uploads are just slower.

For prod-like behaviour:

```sh
docker compose --profile queue up -d    # starts beanstalkd
```

Set `QUEUE_CONNECTION=beanstalkd` in `.env`, then run a worker:

```sh
composer queue
```

## Running the whole app in Docker

This builds and runs the actual production image (code baked in, no live
reload) against the compose dependencies — useful as a smoke test of the
image, not as a dev loop:

```sh
docker compose --profile app up -d --build
```

The site is served at http://localhost:8000. Migrate/seed it with:

```sh
docker compose exec web php artisan migrate --seed
```

`Dockerfile.dev` layers xdebug on top of the built `ponyfm` image if you need
to step-debug inside the container.

In production the same image also runs the queue worker: the entrypoint takes
a mode argument, `web` or `worker` (see `docker/entrypoint.sh`).

## Useful bits

- Reset all data: `docker compose down -v`
- Postgres is on `localhost:5432` (`ponyfm` / `ponyfm`, database `ponyfm`);
  Elasticsearch on `localhost:9200`.
- Uploaded files land in `storage/app/datastore` by default
  (`PONYFM_DATASTORE` overrides this).
- Handy alias while developing: `alias p="php artisan"`
