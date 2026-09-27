#!/bin/sh
set -e

# First-run setup, safe to repeat. Only the app service runs it, so the
# queue worker (which waits for app to be healthy) never races it.
if [ "$RUN_SETUP" = "true" ]; then
    [ -f vendor/autoload.php ] || composer install --no-interaction

    if [ ! -f .env ]; then
        cp .env.example .env
        php artisan key:generate --ansi
    fi

    php artisan migrate --force --ansi
fi

exec "$@"
