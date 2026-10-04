# Stage 1: Build Frontend Assets (Vite & Tailwind CSS)
FROM node:18-alpine AS frontend
WORKDIR /app
COPY package*.json ./
RUN npm install
COPY . .
RUN npm run build

# Stage 2: Production PHP Application & Nginx Server
FROM php:8.2-fpm-alpine

# Install system dependencies, Nginx, and essential PHP extensions
RUN apk add --no-cache \
    nginx \
    curl \
    git \
    libpng-dev \
    libxml2-dev \
    zip \
    unzip \
    ca-certificates \
    && docker-php-ext-install pdo pdo_mysql bcmath

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy application source code
COPY . .

# Copy compiled assets from frontend stage
COPY --from=frontend /app/public/build ./public/build

# Install production PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Set up proper directory permissions for Laravel runtime
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Copy Nginx server configuration
COPY nginx.conf /etc/nginx/http.d/default.conf

# Expose internal web server port
EXPOSE 80

# Start script to run migrations, link storage, and launch Nginx + PHP-FPM
CMD php artisan storage:link --force || true && \
    php artisan config:cache && \
    php artisan route:cache && \
    php artisan view:cache && \
    php artisan migrate --force && \
    php-fpm -D && \
    nginx -g "daemon off;"