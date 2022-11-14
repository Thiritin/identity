FROM php:8.1.11-bullseye as base
WORKDIR /app

ENV COMPOSER_MEMORY_LIMIT=-1
######################################################
# Step 1 | Install Dependencies
######################################################
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

RUN apt-get update \
    && chmod +x /usr/local/bin/install-php-extensions \
    && apt-get install -y curl git unzip openssl tar ca-certificates \
    && install-php-extensions gd bcmath pdo_mysql zip intl opcache pcntl redis swoole @composer \
    && apt-get clean -y \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
######################################################
# Copy Configuration
######################################################
COPY .github/docker/php/opcache.ini $PHP_INI_DIR/conf.d/opcache.ini
COPY .github/docker/php/php.ini $PHP_INI_DIR/conf.d/php.ini

######################################################
# Step 6 | Configure Credentials & Hosts for external Git (optional)
######################################################
COPY composer.json composer.lock /app/
######################################################
# Local Stage
######################################################
FROM base as local
RUN addgroup -gid 1024 app \
  && adduser -uid 1024 --disabled-password --ingroup app app \
  && adduser www-data app \
  && curl -fsSL https://deb.nodesource.com/setup_16.x | bash - \
  && apt-get update \
  && apt-get install -y nodejs npm \
  && apt-get clean -y
USER app
# yarn install as command
CMD sh -c "composer install && php artisan octane:start --watch --host=0.0.0.0 --port=80"
######################################################
# NodeJS Stage
######################################################
FROM node:16-buster as vite
WORKDIR /app
COPY package.json package-lock.json tailwind.config.js vite.config.js ./
RUN npm install
COPY . /app/
RUN npm run build
######################################################
# Production Stage
######################################################
FROM base as production
COPY --from=vite /app/public/build ./public/build
COPY . /app/
RUN composer install --no-dev --optimize-autoloader \
    && chmod 777 -R bootstrap storage \
    && rm -rf .env bootstrap/cache/*.php auth.json \
    && chown -R www-data:www-data /app \
    && rm -rf ~/.composer
CMD sh -c "php artisan octane:start --host=0.0.0.0 --port=80"
