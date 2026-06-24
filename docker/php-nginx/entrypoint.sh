#!/bin/bash

echo "Run artisan migrations"
php artisan migrate --force

echo "Optimize"
php artisan optimize

echo "Start polling events"
php artisan schedule:work 2>&1


