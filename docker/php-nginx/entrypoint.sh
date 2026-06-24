#!/bin/bash

echo "Run artisan migrations"
php artisan migrate --force

echo "Generate swagger"
php artisan optimize

echo "Run FPM"
php-fpm


