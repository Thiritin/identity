.PHONY: setup up down restart dev build install migrate seed fresh test lint shell db-shell redis-shell hydra-shell logs

# First-time setup: copy .env, build image, start containers, install deps, migrate, seed
setup:
	@test -f .env || cp .env.example .env
	docker compose build
	docker compose up -d
	docker compose exec laravel.test composer install
	docker compose exec laravel.test npm install
	docker compose exec laravel.test php artisan key:generate
	docker compose exec laravel.test php artisan migrate --no-interaction
	docker compose exec laravel.test php artisan db:seed --no-interaction
	docker compose exec laravel.test npm run build
	@echo ""
	@echo "Setup complete! Open http://identity.eurofurence.localhost"
	@echo "Login with the admin user created by the seeder (password: admin)"
	@echo "Mailpit UI: http://localhost:8025"

# Start all services
up:
	docker compose up -d

# Stop all services
down:
	docker compose down

# Restart all services
restart:
	docker compose restart

# Start Vite dev server (HMR) inside the container
dev:
	docker compose exec laravel.test npm run dev

# Build frontend assets
build:
	docker compose exec laravel.test npm run build

# Install PHP and JS dependencies
install:
	docker compose exec laravel.test composer install
	docker compose exec laravel.test npm install

# Run database migrations
migrate:
	docker compose exec laravel.test php artisan migrate --no-interaction

# Run database seeders
seed:
	docker compose exec laravel.test php artisan db:seed --no-interaction

# Fresh migrate + seed (destructive!)
fresh:
	docker compose exec laravel.test php artisan migrate:fresh --seed --no-interaction

# Run tests
test:
	docker compose exec laravel.test php artisan test --compact

# Run tests with a filter (usage: make test-filter F=LoginTest)
test-filter:
	docker compose exec laravel.test php artisan test --compact --filter=$(F)

# Run Laravel Pint code formatter
lint:
	docker compose exec laravel.test vendor/bin/pint --dirty

# Open a shell in the app container
shell:
	docker compose exec laravel.test bash

# Open a MySQL shell
db-shell:
	docker compose exec mysql mysql -u sail -ppassword laravel

# Open a Redis CLI
redis-shell:
	docker compose exec redis redis-cli

# Open a Hydra shell
hydra-shell:
	docker compose exec hydra sh

# Tail application logs
logs:
	docker compose logs -f laravel.test

# Run an artisan command (usage: make artisan CMD="route:list")
artisan:
	docker compose exec laravel.test php artisan $(CMD)
