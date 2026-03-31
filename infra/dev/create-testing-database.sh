#!/usr/bin/env bash

mysql --user=root --password="$MYSQL_ROOT_PASSWORD" <<-EOSQL
    CREATE DATABASE IF NOT EXISTS hydra;
    GRANT ALL PRIVILEGES ON \`hydra%\`.* TO '$MYSQL_USER'@'%';
    CREATE DATABASE IF NOT EXISTS \`idp-test\`;
    GRANT ALL PRIVILEGES ON \`idp-test\`.* TO '$MYSQL_USER'@'%';
EOSQL
