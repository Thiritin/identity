#!/usr/bin/env bash

set -ex

cd "$(dirname "$0")/.."

# from sail's devcontainer setup
chown -R 1000:1000 /workspaces/identity 2>/dev/null || true

# install locked php dependencies
composer install
