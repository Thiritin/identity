FROM dunglas/frankenphp:1.9-php8.3-bookworm as base
WORKDIR /app

ENV COMPOSER_MEMORY_LIMIT=-1
######################################################
# Install Dependencies
######################################################
RUN apt-get update \
    && chmod +x /usr/local/bin/install-php-extensions \
    && apt-get install -y --no-install-recommends curl git unzip openssl tar ca-certificates \
    && install-php-extensions gd bcmath pdo_mysql zip intl opcache pcntl redis @composer \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*
######################################################
# Configure Credentials & Hosts for external Git (optional)
######################################################
COPY composer.json composer.lock /app/
RUN composer install --no-dev --no-scripts --no-autoloader
######################################################
# Local Stage
######################################################
FROM base as local
######################################################
# Build Ziggy Package - Vite needs ziggy package available
######################################################
FROM base as vite-vendor-build
WORKDIR /app
RUN rm -f composer.lock composer.json && COMPOSER_ALLOW_SUPERUSER=1 composer require tightenco/ziggy:^1.0 --ignore-platform-reqs
######################################################
# NodeJS Stage
######################################################
FROM node:20-bookworm as vite
WORKDIR /app
COPY package.json package-lock.json tailwind.config.js vite.config.js postcss.config.js ./
RUN npm install
COPY ./resources /app/resources
COPY --from=vite-vendor-build /app/vendor/tightenco/ziggy /app/vendor/tightenco/ziggy
RUN npm run build
######################################################
# Production Stage
######################################################
FROM base as production
COPY --from=vite /app/public/build ./public/build
COPY composer.json composer.lock /app/
RUN composer install --no-dev --optimize-autoloader --no-scripts --no-progress --no-interaction
COPY . /app/
RUN chmod 777 -R bootstrap storage \
    && composer install \
    && rm -rf .env bootstrap/cache/*.php auth.json \
    && chown -R www-data:www-data /app \
    && php artisan matice:generate
CMD sh -c "php artisan octane:start --host=0.0.0.0 --port=80 --admin-port=2019 --server=frankenphp"
