# 🛍️ Laravel POS (Point of Sale) System

A modern, feature-rich Point of Sale system built with Laravel 11, Filament 3, and designed for both web and mobile applications. Perfect for retail stores, restaurants, and any business requiring inventory and sales management.

[![Laravel](https://img.shields.io/badge/Laravel-11.x-red.svg)](https://laravel.com)
[![Filament](https://img.shields.io/badge/Filament-3.x-blue.svg)](https://filamentphp.com)
[![PHP](https://img.shields.io/badge/PHP-8.1+-purple.svg)](https://php.net)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

## 🚀 Quick Start

### 🖥️ Production Deployment (Recommended)
```bash
# Clone repository
git clone https://github.com/IlhamGhaza/laravel-pos.git
cd laravel-pos

# Run deployment script
chmod +x deploy.sh
sudo ./deploy.sh your-domain.com your-email@example.com
```

### 🖥️ Local Development
```bash
# Install dependencies
composer install
npm install

# Setup environment
cp .env.example .env
php artisan key:generate

# Run migrations and seed
php artisan migrate --seed

# Start development server
php artisan serve
```

## 📋 Table of Contents

- [Features](#-features)
- [System Requirements](#-system-requirements)
- [Installation Guides](#-installation-guides)
- [API Documentation](#-api-documentation)
- [Admin Panel](#-admin-panel)
- [Mobile App Integration](#-mobile-app-integration)
- [Deployment](#-deployment)
- [Contributing](#-contributing)
- [Support](#-support)

## ✨ Features

### 🛍️ Core POS Features
- **Product Management** - Complete product catalog with categories, variants, and pricing
- **Inventory Management** - Real-time stock tracking with low stock alerts
- **Order Processing** - Fast checkout with multiple payment methods
- **Customer Management** - Customer database with purchase history
- **Discount System** - Flexible discount rules and promotions
- **Payment Processing** - Multiple payment gateways (Midtrans integration)
- **Sales Reports** - Comprehensive analytics and reporting
- **Delivery Management** - Order delivery tracking and status updates

### 🔧 Technical Features
- **Modern Stack** - Laravel 11, Filament 3, PHP 8.1+
- **Admin Panel** - Beautiful Filament-based admin interface
- **API-First** - RESTful API for mobile app integration
- **Real-time Sync** - Offline-capable mobile synchronization
- **Role-based Access** - Filament Shield for permissions
- **Database** - PostgreSQL with file-based caching
- **Simple Deployment** - No Docker complexity, direct server setup
- **Security** - Sanctum authentication, rate limiting, SSL

### 🎯 Specialized Features
- **Fertilizer Management** - Specialized features for agricultural businesses
- **Mobile Sync** - Offline-capable mobile application support
- **Multi-location** - Support for multiple store locations
- **Tax Management** - Flexible tax calculation and reporting
- **Service Charges** - Configurable service charge handling

## 🖥️ System Requirements

### Development
- PHP 8.1 or higher
- Composer 2.0+
- Node.js 16+ and NPM
- PostgreSQL 13+

### Production
- Ubuntu/Debian VPS
- PHP 8.1+ with FPM
- PostgreSQL 13+
- Nginx web server
- SSL certificate (Let's Encrypt)

## 📚 Installation Guides

### 🖥️ Production Deployment
For production deployment on a VPS:

1. **Quick Deployment (Recommended)**
   ```bash
   # Clone and deploy in one command
   git clone https://github.com/IlhamGhaza/laravel-pos.git
   cd laravel-pos
   chmod +x deploy.sh
   sudo ./deploy.sh your-domain.com your-email@example.com
   ```

2. **Manual Setup**
   - **[VPS Setup](VPS-SETUP.md)** - Detailed VPS configuration guide
   - **[Quick Setup](QUICK-SETUP.md)** - Fast VPS deployment guide

### 🖥️ Local Development
For developers setting up the project locally:

1. **Clone and Setup**
   ```bash
   git clone https://github.com/IlhamGhaza/laravel-pos.git
   cd laravel-pos
   composer install
   npm install
   ```

2. **Environment Configuration**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

3. **Database Setup**
   ```bash
   # Create PostgreSQL database
   createdb laravel_pos
   
   # Run migrations and seeders
   php artisan migrate --seed
   php artisan storage:link
   ```

4. **Filament Shield Setup**
   ```bash
   php artisan shield:install
   php artisan shield:generate
   ```

5. **Start Development**
   ```bash
   npm run dev
   php artisan serve
   ```

## 🔌 API Documentation

The system provides a comprehensive RESTful API for mobile app integration:

### Authentication
```bash
POST /api/login
POST /api/logout
```

### Core Endpoints
- **Products**: `GET/POST/PUT/DELETE /api/products`
- **Orders**: `GET/POST/PUT /api/orders`
- **Customers**: `GET/POST/PUT/DELETE /api/customers`
- **Inventory**: `GET /api/inventory/status`
- **Payments**: `POST /api/payments`

### Mobile Sync
- **Sync Data**: `POST /api/sync/push`
- **Pull Changes**: `POST /api/sync/pull`
- **Conflict Resolution**: `POST /api/sync/resolve-conflict`

### Specialized Features
- **Fertilizer**: `GET /api/fertilizer/recommendations`
- **Discounts**: `GET /api/discounts/filter/today`
- **Deliveries**: `POST /api/deliveries/{id}/dispatch`

📖 **Complete API Reference**: [Postman Collection](postman-collections/API%20POS%20pupuk.postman_collection.json)

## 🎛️ Admin Panel

Access the admin panel at `/admin` after installation:

### Dashboard Features
- **Sales Overview** - Real-time sales statistics
- **Recent Orders** - Latest transactions
- **Inventory Alerts** - Low stock notifications
- **Sales Charts** - 7-day sales trends

### Management Modules
- **Products** - Catalog management with bulk operations
- **Orders** - Order processing and status management
- **Customers** - Customer database and history
- **Inventory** - Stock management and adjustments
- **Reports** - Sales, inventory, and financial reports
- **Users** - Staff management and permissions
- **Settings** - System configuration

## 📱 Mobile App Integration

The system is designed for mobile app integration with:

- **Offline Support** - Data synchronization when online
- **Real-time Updates** - Live inventory and order status
- **Push Notifications** - Order updates and alerts
- **Barcode Scanning** - Product lookup and inventory
- **Payment Integration** - Multiple payment methods

## 🚀 Deployment

### Production Deployment
For production deployment, use the automated script:

```bash
# Quick deployment
sudo ./deploy.sh your-domain.com your-email@example.com
```

This script will:
- Install PHP 8.1, PostgreSQL, Nginx, and Certbot
- Clone the repository
- Setup database and user
- Configure Laravel application
- Setup Nginx web server
- Configure SSL certificate
- Optimize for production

### Environment Configuration
Key environment variables for production:
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com
DB_CONNECTION=pgsql
CACHE_DRIVER=file
SESSION_DRIVER=file
```

## 🛡️ Security Features

- **Authentication** - Laravel Sanctum for API authentication
- **Authorization** - Role-based access control with Filament Shield
- **Rate Limiting** - API rate limiting (100 requests/second)
- **SQL Injection Protection** - Laravel's built-in protection
- **XSS Protection** - Input sanitization and output escaping
- **CSRF Protection** - Cross-site request forgery protection
- **File Upload Security** - Secure file handling and validation

## 🔧 Development

### Project Structure
```
laravel-pos/
├── app/
│   ├── Filament/          # Admin panel resources
│   ├── Http/Controllers/  # API controllers
│   ├── Models/           # Eloquent models
│   └── Services/         # Business logic services
├── database/
│   ├── migrations/       # Database migrations
│   └── seeders/         # Database seeders
├── routes/
│   └── api.php          # API routes
└── deploy.sh            # Production deployment script
```

### Key Technologies
- **Backend**: Laravel 11, PHP 8.1+
- **Admin Panel**: Filament 3
- **Database**: PostgreSQL with file-based caching
- **API**: RESTful with Laravel Sanctum
- **Web Server**: Nginx with PHP-FPM

## 🤝 Contributing

We welcome contributions! Please follow these steps:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

### Development Guidelines
- Follow PSR-12 coding standards
- Write tests for new features
- Update documentation for API changes
- Use conventional commit messages

## 📞 Support

### Documentation
- **[Quick Setup](QUICK-SETUP.md)** - Fast deployment guide
- **[VPS Setup](VPS-SETUP.md)** - Server configuration
- **[Manual Tasks](MANUAL-TASKS.md)** - Post-deployment tasks

### Getting Help
- **Issues**: [GitHub Issues](https://github.com/IlhamGhaza/laravel-pos/issues)
- **Discussions**: [GitHub Discussions](https://github.com/IlhamGhaza/laravel-pos/discussions)
- **Wiki**: [Project Wiki](https://github.com/IlhamGhaza/laravel-pos/wiki)

### Troubleshooting
Common issues and solutions:
- **502 Bad Gateway**: Check PHP-FPM and Nginx are running
- **Permission Issues**: Fix file permissions for storage directory
- **Database Connection**: Verify database credentials in `.env`
- **SSL Issues**: Renew Let's Encrypt certificates

#### Common Commands
```bash
# Check service status
systemctl status nginx php8.2-fpm postgresql

# View logs
tail -f /var/log/nginx/error.log
tail -f /var/log/php8.2-fpm.log

# Fix permissions
chown -R www-data:www-data /var/www/laravel-pos
chmod -R 755 /var/www/laravel-pos
chmod -R 777 /var/www/laravel-pos/storage
```

## 📄 License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## 🙏 Acknowledgments

- [Laravel](https://laravel.com) - The PHP framework
- [Filament](https://filamentphp.com) - The admin panel
- [Midtrans](https://midtrans.com) - Payment gateway integration
- [PostgreSQL](https://postgresql.org) - Database system

---

**Made with ❤️ for modern businesses**

# Laravel POS Deployment

## Deployment Script: `deploy-pos.sh`

Script ini digunakan untuk melakukan deployment Laravel POS secara otomatis di server Ubuntu (tanpa Docker, tanpa Redis). Script akan menginstall dependensi, mengatur database, environment, Nginx, SSL, dan menjalankan Laravel.

### **PERINGATAN Penting!**

Secara default, script ini akan **mereset seluruh data database** karena menjalankan perintah:

```
php artisan migrate:fresh --seed --force
```

Perintah ini akan menghapus semua tabel dan data di database, lalu membuat ulang tabel dan mengisi data awal dari seeder.

**JANGAN jalankan script ini di server yang sudah berisi data penting/produksi tanpa modifikasi!**

#### Jika ingin update aplikasi TANPA reset data:
1. Edit file `deploy-pos.sh`
2. Cari baris:
   ```bash
   sudo -u www-data php artisan migrate:fresh --seed --force
   ```
3. Ganti menjadi:
   ```bash
   sudo -u www-data php artisan migrate --force
   ```
   atau hapus baris tersebut jika ingin migrasi manual.

---

## Langkah Manual Update Aplikasi (Tanpa Reset Data)
1. Pull update dari repository:
   ```bash
   cd /var/www/laravel-pos
   git pull origin <branch>
   ```
2. Install/update dependency:
   ```bash
   sudo -u www-data composer install --optimize-autoloader
   sudo -u www-data composer update --no-interaction
   ```
3. Jalankan migrasi (jika ada perubahan database):
   ```bash
   sudo -u www-data php artisan migrate --force
   ```
4. Optimasi Laravel:
   ```bash
   sudo -u www-data php artisan optimize
   sudo -u www-data php artisan config:cache
   sudo -u www-data php artisan route:cache
   sudo -u www-data php artisan view:cache
   ```
5. Reload Nginx jika ada perubahan konfigurasi:
   ```bash
   sudo systemctl reload nginx
   ```

---

## Catatan Konfigurasi Nginx
Pastikan pada file `/etc/nginx/sites-available/laravel-pos` bagian `server_name` sudah mencakup domain dan www:

```
server_name yourdomain.com www.yourdomain.com;
```

Jika belum, edit file tersebut dan reload Nginx.

---

## Dokumentasi Lain
- Lihat juga: `VPS-SETUP.md`, `QUICK-SETUP.md` untuk panduan lebih detail.

---

Terima kasih telah menggunakan Laravel POS!
