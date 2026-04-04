# EF Identity

Identity management and OIDC provider for Eurofurence. Built with Laravel, Inertia.js (Vue 3), and Ory Hydra.

## Prerequisites

- [Docker](https://docs.docker.com/get-docker/) and Docker Compose
- Port 80 available (used by Traefik reverse proxy)

## Quick Start

```bash
make setup
```

This single command copies `.env`, builds containers, installs all dependencies, runs migrations, seeds the database, and builds frontend assets.

Once finished, open **http://identity.eurofurence.localhost** in your browser.

> **Note:** `*.localhost` resolves to `127.0.0.1` on most systems. If it doesn't work, add `127.0.0.1 identity.eurofurence.localhost` to your `/etc/hosts` file.

### Default Credentials

- **Email:** `identity@eurofurence.localhost`
- **Password:** `admin` (configurable via `ADMIN_PASSWORD` in `.env`)

## Manual Setup

If you prefer to run each step yourself:

```bash
# 1. Copy environment file
cp .env.example .env

# 2. Build and start containers
docker compose build
docker compose up -d

# 3. Install PHP dependencies (also auto-runs on first container start)
docker compose exec laravel.test composer install

# 4. Install JS dependencies
docker compose exec laravel.test npm install

# 5. Generate application key
docker compose exec laravel.test php artisan key:generate

# 6. Run database migrations
docker compose exec laravel.test php artisan migrate

# 7. Seed the database (creates admin user and OAuth apps)
docker compose exec laravel.test php artisan db:seed

# 8. Build frontend assets (or use `make dev` for HMR)
docker compose exec laravel.test npm run build
```

## Day-to-Day Development

```bash
make up          # Start containers
make dev         # Start Vite dev server (HMR for frontend changes)
make down        # Stop containers
```

Frontend changes are picked up automatically by Vite when `make dev` is running.

## Make Commands

| Command | Description |
|---------|-------------|
| `make setup` | First-time setup (does everything) |
| `make up` | Start all containers |
| `make down` | Stop all containers |
| `make restart` | Restart all containers |
| `make dev` | Start Vite dev server (HMR) |
| `make build` | Build frontend assets |
| `make install` | Install PHP + JS dependencies |
| `make migrate` | Run database migrations |
| `make seed` | Run database seeders |
| `make fresh` | Drop all tables, re-migrate, and re-seed |
| `make test` | Run the test suite |
| `make test-filter F=LoginTest` | Run tests matching a filter |
| `make lint` | Run Laravel Pint on changed files |
| `make shell` | Open a bash shell in the app container |
| `make db-shell` | Open a MySQL shell |
| `make logs` | Tail application logs |
| `make artisan CMD="route:list"` | Run any artisan command |

## Updating After a Pull

```bash
make install     # Update PHP + JS dependencies
make migrate     # Run any new migrations
make up          # Restart containers (picks up image changes)
```

## Services & Ports

| Service | URL | Notes |
|---------|-----|-------|
| Application | http://identity.eurofurence.localhost | Main app |
| Mailpit | http://localhost:8025 | Email testing UI |
| Traefik dashboard | http://localhost:8080 | Routing debug |
| MySQL | `localhost:3306` | Configurable via `FORWARD_DB_PORT` |
| Redis | `localhost:6379` | Configurable via `FORWARD_REDIS_PORT` |
| Hydra (public) | `localhost:4444` | OAuth2/OIDC |

### Connecting to MySQL from the Host

| Setting | Value |
|---------|-------|
| Host | `127.0.0.1` |
| Port | `3306` (or `FORWARD_DB_PORT`) |
| Username | `sail` (or `DB_USERNAME`) |
| Password | `password` (or `DB_PASSWORD`) |
| Database | `laravel` (or `DB_DATABASE`) |

## Receiving Mail

Use any `@identity.eurofurence.localhost` email address during development. All outgoing mail is captured by Mailpit at http://localhost:8025.

## Cleaning Up

```bash
# Stop containers (preserves data)
make down

# Remove containers AND all data (database, volumes)
docker compose down -v

# Full cleanup (removes images, build cache, everything)
docker system prune -af
```

## Troubleshooting

### Port 80 is already in use

Stop whatever is using port 80 (Apache, Nginx, another Docker project), then `make up`.

### Permission issues with files created by Docker

Uncomment and set `WWWUSER` and `WWWGROUP` in `.env` to match your local user:

```bash
id -u  # your UID
id -g  # your GID
```

### Frontend changes not showing up

Make sure `make dev` is running for HMR. If you previously ran `make build`, the dev server takes precedence.

### Database connection refused

Wait a few seconds after `make up` for MySQL to finish starting. Check with `docker compose ps` that `mysql` is healthy.

## Security

If you discover any security related issues, please email me@thiritin.com instead of using the issue tracker.
