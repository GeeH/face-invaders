set positional-arguments

# List available commands
default:
    @just --list --unsorted

# --- Environment ---------------------------------------------------------

# Start the dev environment and wait until it's ready
up:
    docker compose up -d --wait
    @echo ""
    @echo "  App:    http://localhost:8000"
    @echo "  Vite:   http://localhost:5173"
    @echo "  Reverb: ws://localhost:8080"

# Stop the dev environment
down:
    docker compose down

# Restart all services, or just the ones named (e.g. `just restart queue`)
restart *services:
    docker compose restart "$@"

# Rebuild the PHP image (after changing docker/) and restart
build:
    docker compose build
    docker compose up -d --wait

# Show running services
ps:
    docker compose ps

# Follow logs for all services, or the ones named (e.g. `just logs app`)
logs *services:
    docker compose logs -f "$@"

# Stop everything and wipe the database and node_modules volumes
reset:
    docker compose down -v

# --- Tools ---------------------------------------------------------------

# Open a shell in the app container
shell:
    docker compose exec app bash

# Run an artisan command (e.g. `just artisan route:list`)
artisan *args:
    docker compose exec app php artisan "$@"

# Run a composer command (e.g. `just composer require foo/bar`)
composer *args:
    docker compose exec app composer "$@"

# Run an npm command in the vite container (e.g. `just npm install phaser`)
npm *args:
    docker compose exec vite npm "$@"

# Open a Tinker session
tinker:
    docker compose exec app php artisan tinker

# Open a psql session on the dev database
db:
    docker compose exec pgsql sh -c 'psql -U "$POSTGRES_USER" "$POSTGRES_DB"'

# --- Database ------------------------------------------------------------

# Run outstanding migrations
migrate:
    docker compose exec app php artisan migrate

# Drop all tables, re-run migrations and seed
fresh:
    docker compose exec app php artisan migrate:fresh --seed

# --- Quality -------------------------------------------------------------

# Run the Pest test suite (extra args are passed through, e.g. `just test --filter=Health`)
test *args:
    docker compose exec app php artisan test "$@"

# Run the JavaScript (Vitest) tests
test-js:
    docker compose exec vite npm test

# Check code style without changing files
lint:
    docker compose exec app vendor/bin/pint --test

# Fix code style
fix:
    docker compose exec app vendor/bin/pint

# Run the same checks as CI: lint and tests
ci: lint test test-js
