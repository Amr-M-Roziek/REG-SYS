#!/bin/bash
# Deployment script for regsys.cloud
# Run this on the server at /var/www/html/icpm2026

# 1. Initialize git if not done
if [ ! -d ".git" ]; then
    git init
    git remote add origin https://github.com/Amr-M-Roziek/REG-SYS.git
    git fetch origin
    git checkout -f master
else
    # 2. Pull latest changes
    git pull origin master
fi

# 3. Set permissions (optional but recommended)
chown -R www-data:www-data .
