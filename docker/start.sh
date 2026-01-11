#!/bin/bash

# Start PHP-FPM in background
php-fpm -D

# Run Laravel optimization commands
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Start Nginx in foreground
nginx -g 'daemon off;'
