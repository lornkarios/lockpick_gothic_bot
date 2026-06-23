#!/bin/bash

echo "Check nginx is fine"
nginx -t

echo "Run artisan migrations"
php artisan migrate --force

echo "Generate swagger"
php artisan optimize

echo "Run NGINX as www process"
nginx

echo "Run FPM"
php-fpm


