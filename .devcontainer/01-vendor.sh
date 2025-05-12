#!/usr/bin/env bash

set -ex

cd "$(dirname "$0")/.."

# --ignore-platform-reqs
# is needed due to missing extensions that we do not
# care about at this point

# install dependencies & create vendor folder
docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v "$(pwd):/var/www/html" \
    -w /var/www/html \
    laravelsail/php82-composer:latest \
    composer install --ignore-platform-reqs
