FROM php:8.0-fpm-alpine as base
WORKDIR /app

ENV COMPOSER_MEMORY_LIMIT=-1
######################################################
# Step 1 | Install Dependencies
######################################################
RUN set -eux; \
	mkdir -p \
		/config/caddy \
		/data/caddy \
		/etc/caddy \
		/usr/share/caddy \
		/var/log/supervisord/ \
	;

RUN set -eux; \
	apkArch="$(apk --print-arch)"; \
	case "$apkArch" in \
		x86_64)  binArch='amd64'; checksum='7112a03bf341a4ccc5332b5ea715de9a68316d2aa2f468bdc263b192448ce412e002acfda68bd0606088b35c5de1f2e93f2aa64ccc065a039f87ee34e0b85b98' ;; \
		armhf)   binArch='armv6'; checksum='a597dbfbd277648881cf51739382a509e5014b3342c78e444f6a680f93836d46c12fc1294e200358fd4a0a40688c5582c81bff14dffd0bba5303170a4d274014' ;; \
		armv7)   binArch='armv7'; checksum='99e7703ffa9dd8f636f4624c0972fd3d4af01523953ebf487b919ce93e1989b5513785dd9e902326423eb334bb22dddbcccab382f46763ec11c43c9e513f7c38' ;; \
		aarch64) binArch='arm64'; checksum='ef1e44293a935b05602524dbab96b51c862864b8a36c7de48b3329dab9b8a4b7d1930460868fded3afb3a74bdfb5a1c1c0ba46f1401edf648a370c0f7be8a05b' ;; \
		ppc64el|ppc64le) binArch='ppc64le'; checksum='62e4a191cae8a1a023ab2653b76439cd4182ca49af4f00bff56507f9f1f4af3e72716a59c59ff157efa87c655110fb2491125baae72590719870dc795d19538d' ;; \
		s390x)   binArch='s390x'; checksum='48cac248c29218e153d76408b172510f4f02e3fe7f7b2209371d2c69ed46d2bfa1f572f46390a00eda6f9296a8cac744a36e21cae6df791bd9d98f22b43ea42b' ;; \
		*) echo >&2 "error: unsupported architecture ($apkArch)"; exit 1 ;;\
	esac; \
	wget -O /tmp/caddy.tar.gz "https://github.com/caddyserver/caddy/releases/download/v2.3.0/caddy_2.3.0_linux_${binArch}.tar.gz"; \
	echo "$checksum  /tmp/caddy.tar.gz" | sha512sum -c; \
	tar x -z -f /tmp/caddy.tar.gz -C /usr/bin caddy; \
	rm -f /tmp/caddy.tar.gz; \
	chmod +x /usr/bin/caddy; \
	caddy version

RUN apk add --no-cache --virtual .build-deps curl-dev libzip-dev icu-dev openssl-dev libtool libxml2-dev oniguruma-dev autoconf gcc make g++ zlib-dev libjpeg-turbo-dev libpng-dev freetype-dev  \
    && apk add --no-cache curl supervisor libzip libintl icu git unzip openssl zip tar ca-certificates dcron mysql-client freetype libzip libjpeg-turbo libpng libwebp-dev \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && docker-php-ext-configure gd --with-freetype --with-webp --with-jpeg \
    && docker-php-ext-install gd bcmath iconv pdo pdo_mysql tokenizer xml zip intl opcache pcntl \
    && echo "* * * * * /usr/local/bin/php /app/artisan schedule:run >> /dev/null 2>&1" >> /var/spool/cron/crontabs/www-data \
    && apk del -f .build-deps \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
######################################################
# Copy Configuration
######################################################
COPY .github/docker/php/opcache.ini $PHP_INI_DIR/conf.d/opcache.ini
COPY .github/docker/php/php.ini $PHP_INI_DIR/conf.d/php.ini
COPY .github/docker/php/www.conf /usr/local/etc/php-fpm.d/www.conf
COPY .github/docker/supervisord.conf /etc/supervisord.conf
COPY .github/docker/Caddyfile /etc/caddy/Caddyfile

######################################################
# Step 6 | Configure Credentials & Hosts for external Git (optional)
######################################################
COPY composer.json composer.lock /app/
######################################################
# Develop Stage
######################################################
FROM base as development
ENTRYPOINT ["/bin/ash", ".github/docker/entrypoint.sh"]
CMD [ "supervisord", "-n", "-c", "/etc/supervisord.conf" ]

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
    && php artisan storage:link
CMD [ "supervisord", "-n", "-c", "/etc/supervisord.conf" ]
