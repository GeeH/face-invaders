# Contributing to Face Invaders

Thanks for wanting to help! Face Invaders is built live on stream and is open source from day one.

## Before you start

- Read the [Game Design Document](docs/Face%20Invaders%20%E2%80%94%20Game%20Design%20Document%20(v1).md) so you know what v1 is and isn't.
- Work is tracked as [issues](https://github.com/GeeH/face-invaders/issues) grouped into [milestones](https://github.com/GeeH/face-invaders/milestones). Pick an existing issue, or open one to discuss your idea before writing code.
- Issues labelled `future` are deliberately out of v1 scope. Please check in on the issue before starting one.

## Local setup

The dev environment runs in Docker Compose, so all you need locally is [Docker](https://docs.docker.com/get-docker/). You don't need PHP, Composer, Node or a database installed. Common commands are wrapped in a [`justfile`](justfile). `just` is optional (`brew install just`), and every recipe is a plain `docker compose` command you can run yourself.

```sh
git clone git@github.com:GeeH/face-invaders.git
cd face-invaders
just up        # or: docker compose up -d --wait
```

On the first run this builds the PHP image, installs Composer and npm dependencies, creates `.env` with an app key, and runs the migrations. It takes a minute or two, and `just up` returns once the app is ready.

| Service | What it runs | URL / port |
| --- | --- | --- |
| `app` | Laravel (`php artisan serve`) | http://localhost:8000 |
| `vite` | Vite dev server with hot reload | http://localhost:5173 |
| `queue` | Queue worker (`queue:listen`, reloads on code changes) | – |
| `scheduler` | Scheduled tasks (`schedule:work`), e.g. the six-hourly follower sync | – |
| `pgsql` | PostgreSQL 18 | `localhost:5432` |

Ports can be changed with `APP_PORT`, `VITE_PORT` and `FORWARD_DB_PORT` in `.env`.

**Linux users:** add `UID` and `GID` to `.env` (from `id -u` and `id -g`) and rebuild with `docker compose build`, so files the containers create are owned by you.

### Everyday commands

Run `just` on its own to list every recipe. The ones you'll use most:

```sh
just up / just down      # start / stop the dev environment
just test                # run the Pest test suite (args pass through: just test --filter=Health)
just test-js             # run the JavaScript tests (Vitest)
just ci                  # run the same checks as CI (Pint, Pest, Vitest) before pushing
just fix                 # fix code style with Pint
just artisan …           # run any artisan command, e.g. just artisan make:model Follower
just composer …          # e.g. just composer require foo/bar
just npm …               # e.g. just npm install phaser
just migrate / fresh     # run migrations / rebuild the database and seed
just logs app            # follow a service's logs
just shell / tinker / db # shell in the app container / Tinker / psql
just reset               # stop everything and wipe the database
```

Tests run against an in-memory SQLite database, so they don't touch your dev data.

### Debugging the game

Add `?debug` to your game URL to see physics hitboxes. It also exposes the game as `window.faceInvaders` in the browser console.

## Branches and pull requests

- Branch names follow `<milestone>/#<issue>-<short-description>`, for example `M0/#2-licence-readme-contributing`.
- Keep each pull request focused on one issue, and link it in the description with `Closes #<issue>`.
- Include tests for new behaviour, and make sure the test suite and Pint pass. CI runs both on every pull request.
- Describe what changed and how you tested it. For anything visual in the game, a screenshot or short clip helps.

## Review

**Every pull request is reviewed and tested before it's merged**, and that includes community contributions. This matters most for upgrades: they run on other people's streams, so they need to be safe, perform well, and fit the game's balance.

## Community upgrades

The upgrade system is being built so that new upgrades plug in without touching the core game loop (#17). A documented way to add your own upgrade is planned (#30). Until then, please hold off on upgrade PRs.

## Licence

By contributing, you agree that your contributions are licensed under the [MIT licence](LICENSE).
