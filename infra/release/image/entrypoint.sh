#!/usr/bin/env bash

if [ ! -d "/var/www/html/vendor" ]; then
    echo "Composer dependencies not installed. The image is not built properly."
    exit 1
fi

if [ ! -f "/var/www/html/public/build/manifest.json" ]; then
    echo "Assets not built. The image is not built properly."
    exit 1
fi

# instantly exit with same code if any command fails
set -e

if [ $# -gt 0 ]; then
    # if the command is set, we will run it
    exec "$@"
else
    # starting the default octane command
    exec php artisan octane:start --host=0.0.0.0 --port=80 --no-interaction
fi
