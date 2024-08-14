# Use official PHP image
FROM php:8.2-fpm

# Set working directory
WORKDIR /var/www/html

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    zip \
    unzip \
    git \
    curl \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libicu-dev \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd intl zip

# Install Node.js and npm
RUN curl -sL https://deb.nodesource.com/setup_18.x | bash - \
    && apt-get install -y nodejs

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy existing application directory contents
COPY . /var/www/html

# Copy existing application directory permissions
COPY --chown=www-data:www-data . /var/www/html

# Install Laravel dependencies
RUN composer install

# Install Node.js dependencies
RUN npm install

# Build assets with Vite and log output
RUN npm run build > build.log 2>&1 && cat build.log && ls -la /var/www/html/public/build

# Check if manifest.json exists
RUN if [ -f /var/www/html/public/build/manifest.json ]; then echo "manifest.json exists"; else echo "manifest.json does not exist"; fi

# Change current user to www
USER www-data

# Expose port 8500 and start php-fpm server
EXPOSE 8500
CMD ["php-fpm"]
