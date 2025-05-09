#!/usr/bin/env bash

mysql --user=root --password="$MYSQL_ROOT_PASSWORD" <<-EOSQL
    CREATE DATABASE IF NOT EXISTS hydra;
    GRANT ALL PRIVILEGES ON \`hydra%\`.* TO '$MYSQL_USER'@'%';
EOSQL
