#!/bin/bash

# Build and start containers
docker-compose up -d --build

# Run tests
EXIT_CODE=0

composer run tests

# Stop containers
docker-compose down

exit $EXIT_CODE