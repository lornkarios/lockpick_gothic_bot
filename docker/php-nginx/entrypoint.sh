#!/bin/bash

echo "Run artisan migrations"
php artisan migrate --force

echo "Optimize"
php artisan optimize

echo "Start polling events"
php artisan bot:work 2>&1


