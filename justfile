# Pony.fm dev commands — see DOCKER-README.md for the full story.

# List available recipes
default:
    @just --list

# One-time setup: env file, deps in docker, composer/yarn install, migrate + seed
setup:
    test -f .env || cp .env.example .env
    docker compose up -d
    composer install
    yarn install
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

# Start everything: docker deps, PHP server (localhost:8000), asset watcher
dev: up
    #!/usr/bin/env sh
    trap 'kill 0' INT TERM
    node_major="$(node -p 'process.versions.node.split(".")[0]' 2>/dev/null || echo 0)"
    if [ "$node_major" -ge 10 ] && [ "$node_major" -le 16 ]; then
        yarn dev &
    else
        echo "==> Host Node ($(node -v 2>/dev/null || echo 'not installed')) can't run the old gulp toolchain — using the Node 12 container for assets"
        docker compose --profile assets up assets &
    fi
    composer serve &
    wait

# Compile assets and watch for changes
assets:
    yarn dev

# Asset watcher in a Node 12 container (if your host Node is too new)
assets-docker:
    docker compose --profile assets up

# Run a queue worker (set QUEUE_CONNECTION=beanstalkd and `just queue-up` first)
queue:
    composer queue

# Start beanstalkd for prod-like queues
queue-up:
    docker compose --profile queue up -d

# Build and run the whole app as the production image (smoke test)
app:
    docker compose --profile app up -d --build

# Pass-through to artisan, e.g. `just artisan migrate`
artisan *args:
    PATH="$PWD/docker/bin:$PATH" php artisan {{args}}
