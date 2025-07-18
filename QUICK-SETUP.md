# Quick VPS Setup - Laravel POS

## Prerequisites
- Ubuntu/Debian VPS
- Domain pointing to VPS IP
- Root/sudo access

## Quick Setup (Recommended)

For the fastest setup, use the automated deployment script:

```bash
wget https://raw.githubusercontent.com/IlhamGhaza/laravel-pos/pupuk/deploy-pos.sh
chmod +x deploy-pos.sh
sudo ./deploy-pos.sh myposs.ilhamghazali.my.id m.ilhamghazali@gmail.com
```

This will automatically install everything and configure your application!

## Manual Quick Setup

If you prefer to set up manually:

### 1. Install Software
```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install PHP and extensions
apt install -y php8.2 php8.2-fpm php8.2-cli php8.2-common php8.2-pgsql php8.2-zip php8.2-gd php8.2-mbstring php8.2-curl php8.2-xml php8.2-bcmath

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
sudo chmod +x /usr/local/bin/composer

# Install PostgreSQL
sudo apt install -y postgresql postgresql-contrib

# Install Nginx & Certbot
sudo apt install nginx certbot python3-certbot-nginx -y

# Install Node.js
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt install -y nodejs

# Start services
sudo systemctl start php8.2-fpm postgresql nginx
sudo systemctl enable php8.2-fpm postgresql nginx
```

### 2. Setup Database
```bash
# Create database and user
sudo -u postgres psql -c "CREATE DATABASE laravel_pos;"
sudo -u postgres psql -c "CREATE USER laravel_pos_app WITH PASSWORD 'your_secure_password';"
sudo -u postgres psql -c "GRANT ALL PRIVILEGES ON DATABASE laravel_pos TO laravel_pos_app;"
sudo -u postgres psql -c "ALTER USER laravel_pos_app CREATEDB;"
```

### 3. Deploy Application
```bash
# Clone to correct location
sudo git clone https://github.com/IlhamGhaza/laravel-pos.git /var/www/laravel-pos
cd /var/www/laravel-pos

# Set permissions
sudo chown -R www-data:www-data /var/www/laravel-pos
sudo chmod -R 755 /var/www/laravel-pos
sudo chmod -R 777 /var/www/laravel-pos/storage
sudo chmod -R 777 /var/www/laravel-pos/bootstrap/cache

# Install dependencies
sudo -u www-data composer install --no-dev --optimize-autoloader

# Setup environment
sudo -u www-data cp .env.example .env
sudo nano .env  # Edit with your domain and database settings
```

### 4. Setup Laravel
```bash
# Generate key and setup
sudo -u www-data php artisan key:generate
sudo -u www-data php artisan migrate --seed

# Setup Shield
echo "y" | sudo -u www-data php artisan shield:setup --fresh
sudo -u www-data php artisan shield:install admin
sudo -u www-data php artisan shield:super-admin --user=2

# Optimize
sudo -u www-data php artisan optimize
sudo -u www-data php artisan storage:link
```

### 5. Configure Nginx
```bash
# Create nginx config
sudo nano /etc/nginx/sites-available/laravel-pos
```

Add this configuration (replace `your-domain.com`):
```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /var/www/laravel-pos/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;
    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\. {
        deny all;
    }

    location ~ ^/(storage|bootstrap/cache)/ {
        deny all;
    }

    client_max_body_size 50M;
}
```

```bash
# Enable site
sudo ln -s /etc/nginx/sites-available/laravel-pos /etc/nginx/sites-enabled/
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t
sudo systemctl reload nginx
```

### 6. Setup SSL
```bash
# Get SSL certificate
sudo certbot --nginx -d your-domain.com
```

## Done! 

Your application should now be accessible at:
- **Main site**: https://your-domain.com
- **Admin panel**: https://your-domain.com/admin

## Important Files

- **Nginx config**: `/etc/nginx/sites-available/laravel-pos`
- **Application**: `/var/www/laravel-pos`
- **Environment**: `/var/www/laravel-pos/.env`

## Quick Commands

```bash
# Check status
sudo systemctl status nginx php8.2-fpm postgresql

# Restart services
sudo systemctl restart nginx php8.2-fpm postgresql

# View logs
sudo tail -f /var/log/nginx/error.log
sudo tail -f /var/log/php8.2-fpm.log
sudo tail -f /var/www/laravel-pos/storage/logs/laravel.log
```

## Troubleshooting

### 502 Bad Gateway
```bash
sudo systemctl restart php8.2-fpm
sudo systemctl status php8.2-fpm
```

### Permission Issues
```bash
sudo chown -R www-data:www-data /var/www/laravel-pos
sudo chmod -R 777 /var/www/laravel-pos/storage
```

### Database Connection
```bash
sudo systemctl status postgresql
sudo -u postgres psql -c "SELECT 1;"
```

### SSL Issues
```bash
sudo certbot renew
sudo systemctl reload nginx
```

## Environment Variables

Make sure your `.env` file has these settings:
```env
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
