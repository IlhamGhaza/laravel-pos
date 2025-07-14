#!/bin/bash

# Debug script untuk mengidentifikasi masalah setup-database.sh
# Jalankan dengan: bash debug-deploy.sh

echo "=========================================="
echo "🔍 DEBUG: Mencari sumber error setup-database.sh"
echo "=========================================="
echo ""

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}[INFO]${NC} Current directory: $(pwd)"
echo -e "${BLUE}[INFO]${NC} Current user: $(whoami)"
echo ""

# Check if setup-database.sh exists
if [ -f "setup-database.sh" ]; then
    echo -e "${YELLOW}[WARNING]${NC} File setup-database.sh ditemukan!"
    echo -e "${YELLOW}[WARNING]${NC} Removing setup-database.sh..."
    rm -f setup-database.sh
    echo -e "${GREEN}[SUCCESS]${NC} File setup-database.sh berhasil dihapus"
else
    echo -e "${GREEN}[SUCCESS]${NC} File setup-database.sh tidak ditemukan"
fi

echo ""

# List all .sh files
echo -e "${BLUE}[INFO]${NC} Listing semua file .sh di direktori ini:"
ls -la *.sh 2>/dev/null || echo "Tidak ada file .sh ditemukan"

echo ""

# Search for any setup-related files
echo -e "${BLUE}[INFO]${NC} Mencari file yang berhubungan dengan setup:"
find . -maxdepth 1 -name "*setup*" -o -name "*install*" -o -name "*database*" 2>/dev/null || echo "Tidak ada file setup ditemukan"

echo ""

# Check if we're in the right directory
echo -e "${BLUE}[INFO]${NC} Verifying directory structure:"
if [ -f "deploy.sh" ]; then
    echo -e "${GREEN}[SUCCESS]${NC} deploy.sh ditemukan"
else
    echo -e "${RED}[ERROR]${NC} deploy.sh tidak ditemukan"
fi

if [ -f ".env.example" ]; then
    echo -e "${GREEN}[SUCCESS]${NC} .env.example ditemukan"
else
    echo -e "${RED}[ERROR]${NC} .env.example tidak ditemukan"
fi

if [ -f "composer.json" ]; then
    echo -e "${GREEN}[SUCCESS]${NC} composer.json ditemukan"
else
    echo -e "${RED}[ERROR]${NC} composer.json tidak ditemukan"
fi

echo ""

# Check for any recent commands that might have created setup-database.sh
echo -e "${BLUE}[INFO]${NC} Checking bash history for setup-related commands:"
if [ -f ~/.bash_history ]; then
    grep -i "setup-database\|chmod.*setup" ~/.bash_history | tail -5 || echo "Tidak ada command setup ditemukan di history"
else
    echo "Bash history tidak tersedia"
fi

echo ""

# Check if there are any processes trying to access setup-database.sh
echo -e "${BLUE}[INFO]${NC} Checking for processes that might be accessing setup files:"
ps aux | grep -i "setup\|chmod" | grep -v grep || echo "Tidak ada proses setup yang berjalan"

echo ""

echo "=========================================="
echo "🎯 RECOMMENDATIONS:"
echo "=========================================="
echo "1. Pastikan Anda menjalankan script dari direktori yang benar"
echo "2. Gunakan: sudo ./deploy.sh domain.com email@example.com"
echo "3. Jangan jalankan perintah manual dari dokumentasi lain"
echo "4. Jika masih error, coba clone repository baru:"
echo "   git clone https://github.com/IlhamGhaza/laravel-pos.git"
echo "   cd laravel-pos"
echo "   sudo ./deploy.sh domain.com email@example.com"
echo ""

echo -e "${GREEN}[SUCCESS]${NC} Debug selesai. Script deploy.sh sudah diperbaiki untuk mencegah error ini."
