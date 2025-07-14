# Manual Tasks untuk Project Kecil

Untuk menghemat resource VPS, berikut cara menjalankan task background secara manual tanpa Docker:

## 1. Queue Worker (Background Jobs)

Jika aplikasi menggunakan queue jobs (seperti email, export, dll), jalankan secara manual:

```bash
# Masuk ke direktori aplikasi
cd /var/www/laravel-pos

# Jalankan queue worker
sudo -u www-data php artisan queue:work --sleep=3 --tries=3

# Atau untuk menjalankan sekali saja
sudo -u www-data php artisan queue:work --once
```

## 2. Scheduler (Cron Jobs)

Untuk menjalankan scheduled tasks, gunakan cron job di VPS:

```bash
# Edit crontab
sudo crontab -e

# Tambahkan baris berikut untuk menjalankan scheduler setiap menit
* * * * * cd /var/www/laravel-pos && sudo -u www-data php artisan schedule:run --no-interaction
```

## 3. Systemd Service untuk Queue Worker

Buat service untuk menjalankan queue worker secara otomatis:

```bash
# Buat file service
sudo nano /etc/systemd/system/laravel-queue.service
```

Isi dengan:
```ini
[Unit]
Description=Laravel Queue Worker
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/var/www/laravel-pos
ExecStart=/usr/bin/php artisan queue:work --sleep=3 --tries=3
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
```

Aktifkan service:
```bash
sudo systemctl enable laravel-queue
sudo systemctl start laravel-queue
```

## 4. Monitoring

Cek status queue:
```bash
# Lihat jumlah job yang pending
cd /var/www/laravel-pos
sudo -u www-data php artisan queue:monitor

# Lihat failed jobs
sudo -u www-data php artisan queue:failed

# Retry failed jobs
sudo -u www-data php artisan queue:retry all
```

## 5. Backup Database

Buat backup database secara manual:

```bash
# Backup database
sudo -u postgres pg_dump laravel_pos > backup_$(date +%Y%m%d_%H%M%S).sql

# Restore database (jika diperlukan)
sudo -u postgres psql laravel_pos < backup_file.sql
```

## 6. Log Rotation

Setup log rotation untuk menghemat disk space:

```bash
# Edit logrotate config
sudo nano /etc/logrotate.d/laravel-pos
```

Isi dengan:
```
/var/www/laravel-pos/storage/logs/*.log {
    daily
    missingok
    rotate 7
    compress
    delaycompress
    notifempty
    create 644 www-data www-data
    postrotate
        systemctl reload php8.2-fpm
    endscript
}
```

## 7. Performance Monitoring

Monitor resource usage:

```bash
# Check disk usage
df -h

# Check memory usage
free -h

# Check PHP-FPM status
sudo systemctl status php8.2-fpm

# Check PostgreSQL status
sudo systemctl status postgresql

# Monitor logs
sudo tail -f /var/log/nginx/error.log
sudo tail -f /var/www/laravel-pos/storage/logs/laravel.log
```

## 8. Optimasi Performance

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

Restart services:
```bash
sudo systemctl restart php8.2-fpm nginx
```

## 9. Security Maintenance

### Update SSL Certificate
```bash
# Test renewal
sudo certbot renew --dry-run

# Manual renewal
sudo certbot renew
sudo systemctl reload nginx
```

### Update System Packages
```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Restart services after update
sudo systemctl restart nginx php8.2-fpm postgresql
```

## 10. Estimasi Resource

Dengan setup tanpa Docker:

- **RAM yang dibutuhkan**: ~300-500 MB
- **Services yang berjalan**: 3 (nginx, php8.2-fpm, postgresql)
- **CPU usage**: Sangat ringan
- **Disk space**: Minimal

**Kesimpulan**: VPS 1 vCPU, 1 GB RAM **SANGAT CUKUP** untuk project kecil dengan traffic rendah.

## 11. Troubleshooting Commands

```bash
# Check service status
sudo systemctl status nginx php8.2-fpm postgresql

# Restart all services
sudo systemctl restart nginx php8.2-fpm postgresql

# Check logs
sudo journalctl -u nginx -f
sudo journalctl -u php8.2-fpm -f
sudo journalctl -u postgresql -f

# Fix permissions
sudo chown -R www-data:www-data /var/www/laravel-pos
sudo chmod -R 755 /var/www/laravel-pos
sudo chmod -R 777 /var/www/laravel-pos/storage
``` 
