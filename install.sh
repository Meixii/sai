#!/bin/bash

# Install Composer dependencies
composer install

# Create required directories
mkdir -p backend/uploads/sounds
chmod 777 backend/uploads/sounds

# Copy sound files (assuming they're in a sounds directory)
cp sounds/* backend/uploads/sounds/

# Import default sounds
php backend/scripts/import_default_sounds.php

echo "Installation completed!" 