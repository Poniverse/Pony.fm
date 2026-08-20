# Pony.fm dev commands — see DOCKER-README.md for the full story.

# List available recipes
default:
    @just --list

# One-time setup: env file, deps in docker, composer/pnpm install, migrate + seed
setup:
    test -f .env || cp .env.example .env
    docker compose up -d
    composer install
    pnpm install
    php artisan migrate --seed

# Start the docker dependencies (postgres + elasticsearch)
up:
    docker compose up -d

# Stop the docker dependencies
down:
    docker compose down

# Stop everything and delete all data (postgres, elasticsearch)
nuke:
    docker compose down -v

# Start everything: docker deps, PHP server (localhost:8000), Vite dev server
dev: up
    #!/usr/bin/env sh
    trap 'kill 0' INT TERM
    pnpm dev &
    composer serve &
    wait

# Run a queue worker (set QUEUE_CONNECTION=redis and `just queue-up` first)
queue:
    composer queue

# Start redis for prod-like queues
queue-up:
    docker compose --profile queue up -d

# Build and run the whole app as the production image (smoke test)
app:
    docker compose --profile app up -d --build

# Pass-through to artisan, e.g. `just artisan migrate`
artisan *args:
    PATH="$PWD/docker/bin:$PATH" php artisan {{args}}
