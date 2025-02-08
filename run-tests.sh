#!/bin/bash

# Build and start containers
docker compose up -d --build

# Wait for database to be ready
echo "Waiting for database to be ready..."
sleep 5

# Install dependencies
docker compose exec php composer install

# Run tests with coverage
docker compose exec php vendor/bin/phpunit --coverage-html coverage

start coverage/index.html

# Keep containers running unless specified otherwise
if [ "$1" == "--down" ]; then
    docker compose down
fi
