# Use PHP 8.2 with Apache
FROM php:8.2-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    zip \
    unzip \
    git \
    libzip-dev \
    libicu-dev \
    libonig-dev \
    libxml2-dev \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql gd zip bcmath intl opcache mbstring exif

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html

# Copy project files
COPY . .

# ======== CRITICAL: Delete stale local cache files ========
# These contain references to dev packages (NunoMaduro\Collision, etc.)
# that won't exist in the --no-dev production build
RUN rm -f bootstrap/cache/config.php \
          bootstrap/cache/packages.php \
          bootstrap/cache/services.php \
          bootstrap/cache/routes-v7.php

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Install PHP dependencies (no-scripts = skip DB-dependent artisan commands)
RUN composer install --no-dev --optimize-autoloader --no-scripts

# ======== CRITICAL: Generate fresh package discovery cache ========
# This creates bootstrap/cache/packages.php and services.php
# with ONLY the production packages (no dev packages)
RUN APP_KEY=base64:dGVzdGtleXRlc3RrZXl0ZXN0a2V5dGVzdGtleXQ= \
    APP_ENV=production \
    DB_CONNECTION=sqlite \
    CACHE_DRIVER=file \
    SESSION_DRIVER=file \
    php artisan package:discover --ansi

# Mark app as installed (bypass installation wizard)
RUN touch storage/installed

# Create storage link
RUN ln -sf /var/www/html/storage/app/public /var/www/html/public/storage

# Clear any remaining stale cache
RUN APP_KEY=base64:dGVzdGtleXRlc3RrZXl0ZXN0a2V5dGVzdGtleXQ= \
    APP_ENV=production \
    DB_CONNECTION=sqlite \
    CACHE_DRIVER=file \
    SESSION_DRIVER=file \
    php artisan config:clear 2>/dev/null || true

# Set permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Configure Apache to listen on port 10000 (Render requirement)
RUN sed -i 's/80/10000/g' /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf

# Suppress Apache ServerName warning
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

EXPOSE 10000

CMD ["apache2-foreground"]
