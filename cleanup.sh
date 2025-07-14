#!/bin/bash

# Script untuk membersihkan file-file yang mungkin menyebabkan error
echo "🧹 Membersihkan file-file yang mungkin menyebabkan error..."

# Hapus file-file yang mungkin ada
rm -f setup-database.sh
rm -f setup.sh
rm -f install.sh
rm -f database-setup.sh
rm -f setup-laravel.sh
rm -f deploy-old.sh
rm -f install-laravel.sh

# Hapus file temporary
rm -f *.tmp
rm -f *.log

echo "✅ Cleanup selesai!"
echo ""
echo "Sekarang jalankan:"
echo "sudo ./deploy.sh domain.com email@example.com"
