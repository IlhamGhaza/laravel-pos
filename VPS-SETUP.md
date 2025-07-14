# Laravel POS VPS Setup Guide

## Prerequisites
- Ubuntu/Debian VPS
- Domain name pointing to your VPS IP
- Root or sudo access

## Quick Setup (Recommended)

For the fastest setup, use the automated deployment script:

```bash
# Clone repository
git clone https://github.com/IlhamGhaza/laravel-pos.git
cd laravel-pos

# Run deployment script
chmod +x deploy.sh
sudo ./deploy.sh your-domain.com your-email@example.com
```

This script will automatically:
- Install PHP 8.1, PostgreSQL, Nginx, and Certbot
- Setup database and user
- Configure Laravel application
- Setup Nginx web server
- Configure SSL certificate
- Optimize for production

## Manual Setup

If you prefer to set up manually, follow these steps:

## Step 1: Install Required Software

### Update System
```bash
sudo apt update && sudo apt upgrade -y
```

### Install PHP and Extensions
```bash
# Install PHP 8.1 and required extensions
sudo apt install -y php8.2 php8.2-fpm php8.2-cli php8.2-common php8.2-pgsql php8.2-zip php8.2-gd php8.2-mbstring php8.2-curl php8.2-xml php8.2-bcmath php8.2-json php8.2-tokenizer php8.2-fileinfo

# Start and enable PHP-FPM
sudo systemctl start php8.2-fpm
sudo systemctl enable php8.2-fpm
```

### Install Composer
```bash
# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
sudo chmod +x /usr/local/bin/composer
```

### Install PostgreSQL
```bash
# Install PostgreSQL
sudo apt install -y postgresql postgresql-contrib

# Start and enable PostgreSQL
sudo systemctl start postgresql
sudo systemctl enable postgresql
```

### Install Nginx
```bash
# Install Nginx
sudo apt install nginx -y
sudo systemctl enable nginx
sudo systemctl start nginx
```

### Install Certbot (for SSL)
```bash
sudo apt install certbot python3-certbot-nginx -y
```

### Install Node.js (for frontend assets)
```bash
# Install Node.js 18
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt install -y nodejs
```

## Step 2: Setup Database

### Create Database and User
```bash
# Switch to postgres user
sudo -u postgres psql

# Create database and user
CREATE DATABASE laravel_pos;
CREATE USER laravel_pos_app WITH PASSWORD 'your_secure_password';
GRANT ALL PRIVILEGES ON DATABASE laravel_pos TO laravel_pos_app;
ALTER USER laravel_pos_app CREATEDB;
\q
```

## Step 3: Deploy Laravel Application

### Clone Repository
```bash
# Clone repository
sudo git clone https://github.com/IlhamGhaza/laravel-pos.git /var/www/laravel-pos
cd /var/www/laravel-pos

# Set proper permissions
sudo chown -R www-data:www-data /var/www/laravel-pos
sudo chmod -R 755 /var/www/laravel-pos
sudo chmod -R 777 /var/www/laravel-pos/storage
sudo chmod -R 777 /var/www/laravel-pos/bootstrap/cache
```

### Install Dependencies
```bash
# Install PHP dependencies
sudo -u www-data composer install --no-dev --optimize-autoloader

# Install Node.js dependencies (if needed)
sudo -u www-data npm install
```

### Configure Environment
```bash
# Copy environment file
sudo -u www-data cp .env.example .env

# Edit environment file
sudo nano .env
```

Update these values in `.env`:
```env
APP_NAME="Laravel POS"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=laravel_pos
DB_USERNAME=laravel_pos_app
DB_PASSWORD=your_secure_password

CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync
```

### Setup Laravel
```bash
# Generate application key
sudo -u www-data php artisan key:generate

# Run migrations and seeders
sudo -u www-data php artisan migrate --seed

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

# Create storage link
sudo -u www-data php artisan storage:link
```

## Step 4: Configure Nginx

### Create Nginx Configuration
```bash
sudo nano /etc/nginx/sites-available/laravel-pos
```

Add this configuration (replace `your-domain.com` with your actual domain):
```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /var/www/laravel-pos/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    # Handle Laravel routes
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # Handle PHP files
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Deny access to hidden files
    location ~ /\. {
        deny all;
    }

    # Deny access to storage and bootstrap/cache
    location ~ ^/(storage|bootstrap/cache)/ {
        deny all;
    }

    # Handle large file uploads
    client_max_body_size 50M;

    # Rate limiting
    limit_req_zone $binary_remote_addr zone=api:10m rate=100r/s;
    location /api/ {
        limit_req zone=api burst=200 nodelay;
        try_files $uri $uri/ /index.php?$query_string;
    }
}
```

### Enable Site
```bash
# Create symlink
sudo ln -s /etc/nginx/sites-available/laravel-pos /etc/nginx/sites-enabled/

# Remove default site
sudo rm -f /etc/nginx/sites-enabled/default

# Test configuration
sudo nginx -t

# Reload nginx
sudo systemctl reload nginx
```

## Step 5: Configure SSL Certificate

### Get SSL Certificate
```bash
# Replace 'your-domain.com' with your actual domain
sudo certbot --nginx -d your-domain.com

# Auto-renewal (certbot creates this automatically)
sudo crontab -e
# Add this line if not present:
# 0 12 * * * /usr/bin/certbot renew --quiet
```

## Step 6: Verify Setup

### Check Application
- Visit: `https://your-domain.com`
- Admin panel: `https://your-domain.com/admin`

### Check Services Status
```bash
# Check Nginx
sudo systemctl status nginx

# Check PHP-FPM
sudo systemctl status php8.2-fpm

# Check PostgreSQL
sudo systemctl status postgresql
```

### Check SSL Certificate
```bash
sudo certbot certificates
```

## Important Notes

### File Upload Size
The nginx configuration includes `client_max_body_size 50M;` for file uploads up to 50MB.

### Security
- Environment files are protected from web access
- Admin panel has additional security headers
- API endpoints have rate limiting (100 requests/second)
- File-based caching instead of Redis for simplicity

### Troubleshooting

#### Check Nginx Logs
```bash
sudo tail -f /var/log/nginx/error.log
sudo tail -f /var/log/nginx/access.log
```

#### Check PHP-FPM Logs
```bash
sudo tail -f /var/log/php8.2-fpm.log
```

#### Restart Services
```bash
# Restart nginx
sudo systemctl restart nginx

# Restart PHP-FPM
sudo systemctl restart php8.2-fpm

# Restart PostgreSQL
sudo systemctl restart postgresql
```

#### Common Issues

1. **502 Bad Gateway**: PHP-FPM not running
   ```bash
   sudo systemctl restart php8.2-fpm
   ```

2. **Permission Denied**: Fix file permissions
   ```bash
   sudo chown -R www-data:www-data /var/www/laravel-pos
   sudo chmod -R 755 /var/www/laravel-pos
   sudo chmod -R 777 /var/www/laravel-pos/storage
   ```

3. **Database Connection**: Check PostgreSQL
   ```bash
   sudo systemctl status postgresql
   sudo -u postgres psql -c "SELECT 1;"
   ```

4. **SSL Certificate Issues**: Renew certificate
   ```bash
   sudo certbot renew
   sudo systemctl reload nginx
   ```

## Maintenance

### Update Application
```bash
cd /var/www/laravel-pos
sudo git pull
sudo -u www-data composer install --no-dev --optimize-autoloader
sudo -u www-data php artisan migrate
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache
sudo systemctl reload nginx
```

### Backup Database
```bash
sudo -u postgres pg_dump laravel_pos > backup_$(date +%Y%m%d_%H%M%S).sql
```

### Monitor Logs
```bash
# Monitor all logs
sudo tail -f /var/log/nginx/error.log /var/log/php8.2-fpm.log

# Monitor Laravel logs
sudo tail -f /var/www/laravel-pos/storage/logs/laravel.log
```

## Performance Optimization

### PHP-FPM Optimization
Edit `/etc/php/8.1/fpm/php.ini`:
```ini
memory_limit = 256M
max_execution_time = 300
upload_max_filesize = 50M
post_max_size = 50M
```

### Nginx Optimization
Add to nginx configuration:
```nginx
# Enable gzip compression
gzip on;
gzip_vary on;
gzip_min_length 1024;
gzip_types text/plain text/css text/xml text/javascript application/javascript application/xml+rss application/json;
```

Restart services after changes:
```bash
sudo systemctl restart php8.2-fpm nginx
``` 
