#!/usr/bin/env bash

# User that will be used to run the application. (UID)
# It worth to set as same as your local linux user to have the same file permissions
# just run `id -u` and put this number while you build the image (tip: in .env file)
# or leave it empty, most of linux distros have 1000 as default
WWWUSER="${WWWUSER:-1000}"

# Group that will be used to run the application. (GID)
# same as above but for the group
# run `id -g` and put this number while you build the image (tip: in .env file)
# or leave it empty, most of linux distros have 1000 as default
WWWGROUP="${WWWGROUP:-1000}"

# User name that will be used to run the application anc commands
# can be sail or root
# sail user will have the same UID and GID provided above (typically from .env file)
WWWUSERNAME="${WWWUSERNAME:-sail}"

# default artisan serve command
DEV_SERVER_COMMAND_FALLBACK="php -d variables_order=EGPCS /var/www/html/artisan serve --host=0.0.0.0 --port=80"

# Command that will be executed when running the container without any command
# falling back to SUPERVISOR_PHP_COMMAND for backward compatibility
DEV_SERVER_COMMAND="${SUPERVISOR_PHP_COMMAND:-$DEV_SERVER_COMMAND_FALLBACK}"


# respect the SUPERVISOR_PHP_USER for backward compatibility
if [ -n "$SUPERVISOR_PHP_USER" ]; then
    WWWUSERNAME="$SUPERVISOR_PHP_USER"
fi

if [ "$WWWUSERNAME" != "root" ] && [ "$WWWUSERNAME" != "sail" ]; then
    echo "You should set WWWUSERNAME (or SUPERVISOR_PHP_USER) to either 'sail' or 'root'."
    exit 1
fi

# updating UID and GID for sail
usermod -u "$WWWUSER" sail > /dev/null
groupmod -g "$WWWGROUP" sail > /dev/null

# install project dependencies if not already installed
if [ ! -d "/var/www/html/vendor" ]; then
    echo "Installing PHP dependencies"
    if [ "$WWWUSERNAME" = "root" ]; then
        composer install --no-interaction --prefer-dist --optimize-autoloader
    else
        gosu "$WWWUSER:$WWWGROUP" composer install --no-interaction --prefer-dist --optimize-autoloader
    fi
fi


if [ $# -gt 0 ]; then
    # if the command is set, we will run it
    if [ "$WWWUSERNAME" = "root" ]; then
        exec "$@"
    else
        exec gosu "$WWWUSER:$WWWGROUP" "$@"
    fi
else
    # if the command is not set, we will run the default command
    if [ "$WWWUSERNAME" = "root" ]; then
        exec $DEV_SERVER_COMMAND
    else
        exec gosu "$WWWUSER:$WWWGROUP" $DEV_SERVER_COMMAND
    fi
fi
