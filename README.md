# Identity

This is a Laravel, IntertiaJS with Vue based application that works as an OIDC Provider.

## Installation

You need to have at least docker and docker-compose installed.
Podman is also supported, but you need to set up the podman socket and command to be compatible with docker.

1. Clone the Repo
2. Copy `.env.example` to `.env` and set up the environment variables if needed.  
   `cp .env.example .env`  
   (you should generate new secret keys, but for simplicity some default ones are provided)
3. Run `docker compose up -d` to start the docker containers.  
   It may take a while to pull and build images for the first time.
4. Wait when php dependencies are installed and the dev server starts.  
   It may also take a while depending on your internet connection and computer performance.    
   You can check the logs with `docker compose logs -f laravel.test`
   (`Ctrl+C` here will close logs, but will keep the server running).
5. Setup alias for sail:
   ```bash
   alias sail='./vendor/bin/sail'
   ```
   or use docker-compose directly:
   ```bash
   docker compose exec -it -u sail laravel.test <command>
   ```
   (further commands will have a sail and pure docker-compose version)
6. Run `sail artisan migrate` or `docker compose exec -it -u sail laravel.test php artisan migrate`.
7. Run `sail artisan db:seed` or `docker compose exec -it -u sail laravel.test php artisan db:seed`.
8. Run `sail npm install` or `docker compose exec -it -u sail laravel.test npm install`.
9. Run `sail npm run dev` or `docker compose exec -it -u sail laravel.test npm run dev`.  
   (you can run this command locally. it may speed up HMR and file watching on some systems)
10. Add `identity.eurofurence.lan` to your hosts file and forward them to `127.0.0.1`.
11. You can now go to http://identity.eurofurence.lan.    
    it may take a while to load first time.  
    Default credentials are: `identity@eurofurence.lan`, `admin` (unless you changed them in `.env`).

### Entering laravel container

To enter the laravel container, run `sail shell` or `docker compose exec -it -u sail laravel.test bash`.

### Updating dev environment

To keep your dev environment up to date after merging changes from the main branch or creating a new one, run:

* `sail up -d` or `docker compose up -d` to update container image versions.
* `sail npm install` or `docker compose exec -it -u sail laravel.test npm install` to update npm packages.
* `sail composer install` or `docker compose exec -it -u sail laravel.test composer install` to update composer files.
* `sail restart` or `docker compose restart` to restart the containers.

### Stopping dev environment

To stop the dev environment, run `sail stop` or `docker compose stop`.

This action will stop all containers, free up the ports and keep data in containers and volumes.

To start it again, run `sail up -d` or `docker compose up -d`.

### Removing and cleaning up the dev environment

**WARNING: This action will remove all containers and volumes, including the database and all data in it.**

To remove the dev environment, run `sail down -v` or `docker compose down -v`.

Also, you may want to remove `vendor` and `node_modules` if you want to perform
a clean installation later `rm -rf vendor node_modules`.

**WARNING: next step will remove all pulled and built images, build caches, volumes, networks and stopped containers.**

For some rare cases, you may want to remove all pulled images and volumes `docker system prune -af`.

## Exposed Ports

* `:80` - HTTP terafic internal router. It routes http request to laravel container (mostly)
  and hydra (`/.well-known`,`/oauth2`,`/health`,`/userinfo`)
* `:8080` - traefic dashboard (for debugging)
* `:3306` - MySQL database (can be changed via `FORWARD_DB_PORT` in `.env`)
* `:4444`, `:4445`, `:5555` - Hydra endpoints (for debugging)
* `:6379` - Redis (can be changed via `FORWARD_REDIS_PORT` in `.env`)
* `:6001`, `:9601` - Soketi (local WS pusher implementation)
  (can be changed via `PUSHER_PORT` and `PUSHER_METRICS_PORT` in `.env`)
* `:7700` - MeiliSearch (can be changed via `FORWARD_MEILISEARCH_PORT` in `.env`)
* `:1025`, `:8025` - MailPit local SMTP server
  (can be changed via `FORWARD_MAILPIT_PORT`, `FORWARD_MAILPIT_DASHBOARD_PORT` in `.env`)

## Connecting to the MySQL database from the host

After you finish the installation, you can connect to the MySQL database from your host machine.

* Host: `127.0.0.1`
* Port: `3306` (or the port you set in `.env` for `FORWARD_DB_PORT`)
* Username: `sail` (or the user you set in `.env` for `DB_USERNAME`)
* Password: `password` (or the password you set in `.env` for `DB_PASSWORD`)
* Database: `laravel` (or the database you set in `.env` for `DB_DATABASE`)

## Receiving mail

During development, you should use `identity.eurofurence.lan` hostname
(eg `some-user@identity.eurofurence.lan`) to receive emails.

Use mailpit to check the emails at [http://localhost:8025](http://localhost:8025).

## Security

If you discover any security related issues, please email me@thiritin.com instead of using the issue tracker.
