FROM php:8.1-alpine as base
WORKDIR /app

ENV COMPOSER_MEMORY_LIMIT=-1
######################################################
# Step 1 | Install Dependencies
######################################################
RUN apk add --no-cache --virtual .build-deps libzip-dev icu-dev openssl-dev libtool autoconf gcc make g++ zlib-dev libjpeg-turbo-dev libpng-dev freetype-dev libwebp-dev  \
    && apk add --no-cache curl libzip git icu unzip openssl zip tar ca-certificates mysql-client freetype libzip libjpeg-turbo libpng libwebp procps \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && pecl install swoole \
    && docker-php-ext-enable swoole \
    && docker-php-ext-configure gd --with-freetype --with-webp --with-jpeg \
    && docker-php-ext-install gd bcmath pdo_mysql zip intl opcache pcntl \
    && apk del -f .build-deps \
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
RUN addgroup -g 1024 app \
  && adduser -u 1024 --disabled-password --ingroup app app \
  && adduser www-data app \
  && apk add --no-cache nodejs npm
USER app
# yarn install as command
CMD sh -c "composer install && php artisan octane:start --watch --host=0.0.0.0 --port=80"
######################################################
# NodeJS Stage
######################################################
FROM node:16-buster as mix
WORKDIR /app
COPY package.json package-lock.json webpack.mix.js tailwind.config.js webpack.config.js ./
RUN npm install
COPY . /app/
RUN npm run prod
######################################################
# Production Stage
######################################################
FROM base as production
COPY --from=mix /app/public/css/app.css ./public/css/app.css
COPY --from=mix /app/public/js/app.js ./public/js/app.js
COPY . /app/
RUN composer install --no-dev --optimize-autoloader \
    && chmod 777 -R bootstrap storage \
    && rm -rf .env bootstrap/cache/*.php auth.json \
    && chown -R www-data:www-data /app \
    && rm -rf ~/.composer
CMD sh -c "php artisan octane:start --host=0.0.0.0 --port=80"
