# Pony.fm Docker development

Pony.fm runs in a container in production (FrankenPHP running Laravel
Octane, plus ffmpeg + AtomicParsley all baked into one image — see
`Dockerfile`). For local development the recommended setup is:

- **Dependencies in Docker** — `docker compose up -d` gives you Postgres,
  Elasticsearch, and Mailpit (and optionally redis).
- **The app on the host** — `composer serve` for PHP, `pnpm dev` for the
  Vite dev server.
- **ffmpeg & AtomicParsley via `docker run`** — shims in `docker/bin/` mean
  you don't need either installed locally.

## Prerequisites

- Docker (Desktop or equivalent)
- PHP 8.4+ with composer
- Node 26 with pnpm (see `.nvmrc`; `corepack enable` gets you pnpm)

You do **not** need ffmpeg, AtomicParsley, Postgres, or Elasticsearch
installed on your machine.

## Quick start

If you have [`just`](https://github.com/casey/just), `just setup` runs all of
the below, then `just dev` starts everything (deps + PHP server + Vite) —
see `just --list` for the rest. By hand:

```sh
cp .env.example .env       # works as-is, no editing needed
docker compose up -d       # postgres + elasticsearch + mailpit

composer install
php artisan migrate --seed

composer serve             # app on http://localhost:8000
```

And in a second terminal, the Vite dev server (leave it running while you
develop):

```sh
pnpm install
pnpm dev
```

Mail sent in dev is caught by Mailpit — browse it at http://localhost:8025.

## How ffmpeg & AtomicParsley work in dev

The app shells out to `ffmpeg` (upload validation, transcoding) and
`AtomicParsley` (MP4 tagging) by name. `composer serve` and `composer queue`
prepend `docker/bin/` to `PATH`, where shim scripts forward those calls to
`docker run`:

- `docker/bin/ffmpeg` → `jrottenberg/ffmpeg:7.1-alpine320` (same build the
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
docker compose --profile queue up -d    # starts redis
```

Set `QUEUE_CONNECTION=redis` in `.env`, then run a worker:

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
