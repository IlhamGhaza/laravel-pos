#!/bin/bash

# Run migrations and seeders
sudo -u www-data php artisan migrate:fresh --seed --force

# Setup Shield
echo "y" | sudo -u www-data php artisan shield:setup --fresh

# Install Shield admin
sudo -u www-data php artisan shield:install admin

# Create super admin user
sudo -u www-data php artisan shield:super-admin --user=2

# Optimize Laravel
sudo -u www-data php artisan optimize
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache
