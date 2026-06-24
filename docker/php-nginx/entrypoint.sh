#!/bin/bash

echo "Run artisan migrations"
php artisan migrate --force

echo "Optimize"
php artisan optimize

echo "Run FPM"
php-fpm
