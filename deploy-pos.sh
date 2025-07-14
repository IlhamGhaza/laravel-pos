#!/bin/bash

# Laravel POS Simple Deployment Script (No Docker, No Redis)
# This script automates the deployment process without containers

set -e  # Exit on any error

# Global variables
DB_PASSWORD=""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Function to clean up old setup files
cleanup_old_files() {
    print_status "Cleaning up any old setup files..."

    # List of files that might cause errors
    local old_files=(
        "setup-database.sh"
        "setup.sh"
        "install.sh"
        "database-setup.sh"
        "setup-laravel.sh"
        "deploy-old.sh"
        "install-laravel.sh"
    )

    # Remove any old setup files that might cause errors
    for file in "${old_files[@]}"; do
        if [ -f "$file" ]; then
            print_warning "Found old $file file, removing..."
            rm -f "$file"
        fi

        # Also check in current directory with different extensions
        if [ -f "${file%.*}" ]; then
            print_warning "Found old ${file%.*} file, removing..."
            rm -f "${file%.*}"
        fi
    done

    # Check for any executable files that might be old setup scripts
    if [ -d "." ]; then
        for file in *.sh; do
            if [[ -f "$file" && "$file" != "deploy-pos.sh" ]]; then
                print_warning "Found additional script file: $file"
                if [[ "$file" == *"setup"* ]] || [[ "$file" == *"install"* ]] || [[ "$file" == *"database"* ]]; then
                    print_warning "Removing potentially problematic script: $file"
                    rm -f "$file"
                fi
            fi
        done
    fi

    print_success "Cleanup completed"
}

# Function to check if command exists
command_exists() {
    command -v "$1" >/dev/null 2>&1
}

# Function to validate email format
validate_email() {
    local email=$1
    if [[ $email =~ ^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$ ]]; then
        return 0
    else
        return 1
    fi
}

# Function to validate domain format
validate_domain() {
    local domain=$1
    if [[ $domain =~ ^[a-zA-Z0-9]([a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(\.[a-zA-Z0-9]([a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*$ ]]; then
        return 0
    else
        return 1
    fi
}

# Function to check if running as root
check_root() {
    if [ "$EUID" -ne 0 ]; then
        print_error "Please run this script as root or with sudo"
        exit 1
    fi
}

# Function to verify script environment
verify_environment() {
    print_status "Verifying script environment..."

    # Check if .env.example exists in current directory (should be /var/www/laravel-pos)
    if [ ! -f ".env.example" ]; then
        print_error "Missing .env.example file in $(pwd). Repository clone might have failed."
        exit 1
    fi

    print_success "Environment verification completed"
}

# Function to install required software
install_software() {
    print_status "Installing required software..."

    # Update system
    apt update && apt upgrade -y

    # Add PHP PPA if not already added
    if ! grep -q "ondrej/php" /etc/apt/sources.list /etc/apt/sources.list.d/* 2>/dev/null; then
        print_status "Adding PHP repository..."
        apt install -y software-properties-common
        add-apt-repository ppa:ondrej/php -y
        apt update
    fi

    # Install PHP and extensions
    print_status "Installing PHP and extensions..."
    apt install -y php8.3 php8.3-fpm php8.3-cli php8.3-common php8.3-pgsql php8.3-zip php8.3-gd php8.3-mbstring php8.3-curl php8.3-xml php8.3-bcmath php8.3-intl
    # Start and enable PHP-FPM
    systemctl start php8.3-fpm
    systemctl enable php8.3-fpm

    # Install Composer
    if ! command_exists composer; then
        print_status "Installing Composer..."
        curl -sS https://getcomposer.org/installer | php
        mv composer.phar /usr/local/bin/composer
        chmod +x /usr/local/bin/composer
    else
        print_success "Composer already installed"
    fi

    # Install PostgreSQL
    if ! command_exists psql; then
        print_status "Installing PostgreSQL..."
        apt install -y postgresql postgresql-contrib
        systemctl enable postgresql
        systemctl start postgresql
    else
        print_success "PostgreSQL already installed"
    fi

    # Install Nginx
    if ! command_exists nginx; then
        print_status "Installing Nginx..."
        apt install nginx -y
        systemctl enable nginx
        systemctl start nginx
    else
        print_success "Nginx already installed"
    fi

    # Install Certbot
    if ! command_exists certbot; then
        print_status "Installing Certbot..."
        apt install certbot python3-certbot-nginx -y
    else
        print_success "Certbot already installed"
    fi

    # Install Node.js and npm (for frontend assets)
    # if ! command_exists node; then
    #     print_status "Installing Node.js..."
    #     curl -fsSL https://deb.nodesource.com/setup_18.x | bash -
    #     apt install -y nodejs
    # else
    #     print_success "Node.js already installed"
    # fi

    print_success "All required software installed"
}

# Function to clone repository
clone_repository() {
    local repo_url=$1
    local branch=$2
    local domain=$3

    print_status "Cloning repository..."

    # Remove existing directory if exists
    # if [ -d "/var/www/laravel-pos" ]; then
    # # If current directory is inside /var/www/laravel-pos, move out first
    #     if [[ "$(pwd)" == /var/www/laravel-pos* ]]; then
    #         print_warning "Currently inside /var/www/laravel-pos, moving out before removing..."
    #         cd /tmp || cd /
    #     fi
    #     print_warning "Directory /var/www/laravel-pos already exists. Removing..."
    #     rm -rf /var/www/laravel-pos
    # fi

    # Clone repository with specific branch
    # git clone -b "$branch" "$repo_url" /var/www/laravel-pos

    # Set ownership
    chown -R www-data:www-data /var/www/laravel-pos

    # Set permissions
    chmod -R 755 /var/www/laravel-pos
    chmod -R 777 /var/www/laravel-pos/storage
    chmod -R 777 /var/www/laravel-pos/bootstrap/cache

    cd /var/www/laravel-pos

    print_success "Repository cloned successfully"
}

# Function to setup database
setup_database() {
    print_status "Setting up database..."

    # Generate secure password for database
    DB_PASSWORD=$(openssl rand -base64 32 | tr -d '\n')

    # Create database and user
    sudo -u postgres psql -c "CREATE DATABASE laravel_pos;" 2>/dev/null || true
    sudo -u postgres psql -tc "SELECT 1 FROM pg_roles WHERE rolname='laravel_pos_app'" | grep -q 1 \
      && sudo -u postgres psql -c "ALTER USER laravel_pos_app WITH PASSWORD '$DB_PASSWORD';" \
      || sudo -u postgres psql -c "CREATE USER laravel_pos_app WITH PASSWORD '$DB_PASSWORD';"
    sudo -u postgres psql -c "GRANT ALL PRIVILEGES ON DATABASE laravel_pos TO laravel_pos_app;"
    sudo -u postgres psql -c "ALTER USER laravel_pos_app CREATEDB;"
    sudo -u postgres psql -d laravel_pos -c "GRANT ALL ON SCHEMA public TO laravel_pos_app;"

    print_success "Database setup completed"
    print_status "Database Password: $DB_PASSWORD"
}

# Function to setup environment
setup_environment() {
    local domain=$1

    print_status "Setting up environment..."

    # Copy environment file
    cp .env.example .env

    # Update .env with database configuration
    sed -i "s/DB_CONNECTION=.*/DB_CONNECTION=pgsql/" .env
    sed -i "s/DB_HOST=.*/DB_HOST=127.0.0.1/" .env
    sed -i "s/DB_PORT=.*/DB_PORT=5432/" .env
    sed -i "s/DB_DATABASE=.*/DB_DATABASE=laravel_pos/" .env
    sed -i "s/DB_USERNAME=.*/DB_USERNAME=laravel_pos_app/" .env
    sed -i "s#DB_PASSWORD=.*#DB_PASSWORD=$DB_PASSWORD#" .env

    # Update app configuration
    sed -i "s/APP_URL=.*/APP_URL=https:\/\/$domain/" .env
    sed -i "s/APP_ENV=.*/APP_ENV=production/" .env
    sed -i "s/APP_DEBUG=.*/APP_DEBUG=false/" .env

    # Use file cache instead of Redis
    sed -i "s/CACHE_DRIVER=.*/CACHE_DRIVER=file/" .env
    sed -i "s/SESSION_DRIVER=.*/SESSION_DRIVER=file/" .env
    sed -i "s/QUEUE_CONNECTION=.*/QUEUE_CONNECTION=sync/" .env

    # Set proper permissions
    chown www-data:www-data .env
    chmod 600 .env

    print_success "Environment configured"
}

# Function to setup Laravel
setup_laravel() {
    print_status "Setting up Laravel application..."

    # Install dependencies
    # sudo -u www-data composer install --no-dev --optimize-autoloader
    sudo -u www-data composer install --optimize-autoloader
    sudo -u www-data composer update --no-interaction

    # Generate application key
    sudo -u www-data php artisan key:generate

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

    # Create storage link
    sudo -u www-data php artisan storage:link

    print_success "Laravel setup completed"
}

# Function to setup Nginx
setup_nginx() {
    local domain=$1

    print_status "Setting up Nginx..."

    # Create Nginx configuration
    cat > /etc/nginx/sites-available/laravel-pos << EOF
limit_req_zone \$binary_remote_addr zone=api:10m rate=100r/s;
server {
    listen 80;
    server_name $domain www.$domain;
    root /var/www/laravel-pos/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    # Handle Laravel routes
    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    # Handle PHP files
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
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

    # Rate limiting for /api/
    location /api/ {
        limit_req zone=api burst=200 nodelay;
        try_files \$uri \$uri/ /index.php?\$query_string;
    }
}
EOF

    # Enable the site
    ln -sf /etc/nginx/sites-available/laravel-pos /etc/nginx/sites-enabled/
    rm -f /etc/nginx/sites-enabled/default

    # Test Nginx configuration
    nginx -t

    # Reload Nginx
    systemctl reload nginx

    print_success "Nginx configured"
}

# Function to setup SSL
setup_ssl() {
    local domain=$1
    local email=$2

    print_status "Setting up SSL certificate..."

    # Install Certbot via Snap (best practice)
    if ! command_exists snap; then
        print_status "Installing snapd..."
        apt install -y snapd
    fi
    sudo snap install core; sudo snap refresh core
    sudo apt remove -y certbot || true
    sudo snap install --classic certbot
    sudo ln -sf /snap/bin/certbot /usr/bin/certbot

    # Setup UFW rules for HTTPS
    if command_exists ufw; then
        sudo ufw allow 'OpenSSH'
        sudo ufw allow 'Nginx Full'
        sudo ufw delete allow 'Nginx HTTP' || true
        sudo ufw --force enable
    fi

    # Get SSL certificate (with and without www)
    certbot --nginx -d "$domain" -d "www.$domain" --non-interactive --agree-tos --email "$email"

    print_success "SSL certificate configured"
}

# Function to verify deployment
verify_deployment() {
    local domain=$1

    print_status "Verifying deployment..."

    # Check if Nginx is running
    if systemctl is-active --quiet nginx; then
        print_success "Nginx is running"
    else
        print_error "Nginx is not running"
        return 1
    fi

    # Check if PHP-FPM is running
    if systemctl is-active --quiet php8.3-fpm; then
        print_success "PHP-FPM is running"
    else
        print_error "PHP-FPM is not running"
        return 1
    fi

    # Test website accessibility
    if curl -s -o /dev/null -w "%{http_code}" "https://$domain" | grep -q "200"; then
        print_success "Website is accessible"
    else
        print_warning "Website might not be accessible yet (DNS propagation)"
    fi

    print_success "Deployment verification completed"
}

# Function to show final information
show_final_info() {
    local domain=$1
    local db_password=$2

    echo ""
    echo "=========================================="
    echo "🎉 DEPLOYMENT COMPLETED SUCCESSFULLY! 🎉"
    echo "=========================================="
    echo ""
    echo "Your Laravel POS application is now live at:"
    echo "🌐 Main site: https://$domain"
    echo "🔧 Admin panel: https://$domain/admin"
    echo ""
    echo "Important files and locations:"
    echo "📁 Application: /var/www/laravel-pos"
    echo "⚙️  Nginx config: /etc/nginx/sites-available/laravel-pos"
    echo "🗄️  Database: PostgreSQL (localhost)"
    echo ""
    echo "Useful commands:"
    echo "📊 Check Nginx status: systemctl status nginx"
    echo "📊 Check PHP-FPM status: systemctl status php8.3-fpm"
    echo "📝 View Nginx logs: tail -f /var/log/nginx/error.log"
    echo "🔄 Restart services: systemctl restart nginx php8.3-fpm"
    echo "🔒 SSL renewal: certbot renew"
    echo ""
    echo "Security features enabled:"
    echo "✅ Secure database user"
    echo "✅ Nginx rate limiting (100 req/s)"
    echo "✅ SSL certificate"
    echo "✅ File upload limit (50MB)"
    echo "✅ Hidden file protection"
    echo ""
    echo "IMPORTANT: Database credentials (save these securely!):"
    echo "📋 Database Password: $db_password"
    echo "📋 Or check the .env file in /var/www/laravel-pos"
    echo ""
    echo "Next steps:"
    echo "1. Create admin user in Filament panel"
    echo "2. Configure your business settings"
    echo "3. Set up regular backups"
    echo "4. Monitor application logs"
    echo ""
    echo "For support, check the documentation:"
    echo "📚 VPS-SETUP.md"
    echo "📚 QUICK-SETUP.md"
    echo ""
}

# Main deployment function
main() {
    local domain=$1
    local email=$2

    if [ -z "$domain" ] || [ -z "$email" ]; then
        echo "Usage: sudo ./deploy-pos.sh <domain> <email>"
        echo "Example: sudo ./deploy-pos.sh example.com admin@example.com"
        echo ""
        echo "This will deploy from: https://github.com/IlhamGhaza/laravel-pos/tree/pupuk"
        echo ""
        echo "Parameters:"
        echo "  domain: Your domain name (e.g., example.com)"
        echo "  email: Email for SSL certificate notifications"
        exit 1
    fi

    # Validate domain format
    if ! validate_domain "$domain"; then
        print_error "Invalid domain format: $domain"
        echo "Please use a valid domain name (e.g., example.com)"
        exit 1
    fi

    # Validate email format
    if ! validate_email "$email"; then
        print_error "Invalid email format: $email"
        echo "Please use a valid email address (e.g., admin@example.com)"
        exit 1
    fi

    # Set repository URL
    repo_url="https://github.com/IlhamGhaza/laravel-pos.git"
    branch="pupuk"

    # Always work from /var/www/laravel-pos
    project_dir="/var/www/laravel-pos"

    # Clean up any old setup files in current dir
    cleanup_old_files

    # --- AUTO CLONE IF NEEDED ---
    if [ ! -d "$project_dir" ]; then
        print_status "Project directory $project_dir not found. Cloning repository..."
        git clone -b "$branch" "$repo_url" "$project_dir"
    fi

    # Move to project directory for all further steps
    cd "$project_dir"

    # Clean up any old setup files in project dir
    cleanup_old_files

    # Verify script environment (now in project dir)
    verify_environment

    echo "=========================================="
    echo "🚀 Laravel POS Simple Deployment Script"
    echo "=========================================="
    echo ""
    echo "Repository: $repo_url"
    echo "Branch: $branch"
    echo "Domain: $domain"
    echo "Email: $email"
    echo ""

    # Show deployment summary
    echo "=========================================="
    echo "📋 DEPLOYMENT SUMMARY"
    echo "=========================================="
    echo "Repository: $repo_url"
    echo "Branch: $branch"
    echo "Domain: $domain"
    echo "Email: $email"
    echo "Installation Path: $project_dir"
    echo ""
    echo "This will:"
    echo "✅ Install PHP, PostgreSQL, Nginx, and Certbot"
    echo "✅ Clone the Laravel POS repository"
    echo "✅ Setup database and user"
    echo "✅ Configure Laravel application"
    echo "✅ Setup Nginx web server"
    echo "✅ Setup SSL certificate"
    echo ""

    # Confirm deployment
    read -p "Do you want to proceed with the deployment? (y/N): " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        print_warning "Deployment cancelled"
        exit 0
    fi

    # Check if running as root
    check_root

    # Start deployment
    print_status "Starting deployment process..."

    # Step 1: Install software
    install_software

    # Step 2: Clone repository (force update if already exists)
    clone_repository "$repo_url" "$branch" "$domain"

    # Step 3: Setup database
    setup_database

    # Step 4: Setup environment
    setup_environment "$domain"

    # Step 5: Setup Laravel
    setup_laravel

    # Step 6: Setup Nginx
    setup_nginx "$domain"

    # Step 7: Setup SSL
    setup_ssl "$domain" "$email"

    # Step 8: Verify deployment
    verify_deployment "$domain"

    # Step 9: Show final information
    show_final_info "$domain" "$DB_PASSWORD"

    print_success "Deployment completed successfully!"
}

# Run main function with arguments
main "$@"
