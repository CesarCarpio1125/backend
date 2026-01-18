# Stage 1: Composer dependencies
FROM composer:2 as vendor

WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-interaction --optimize-autoloader --no-scripts

# Stage 2: Application
FROM php:8.4-cli

# System dependencies
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libssl-dev \
    && docker-php-ext-install \
    pdo \
    pdo_mysql \
    zip

# Set working directory
WORKDIR /var/www

# Create necessary directories and set permissions
RUN mkdir -p storage/framework/{sessions,views,cache} \
    && mkdir -p storage/logs \
    && mkdir -p bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Copy application files
COPY . .

# Copy vendor directory from the vendor stage
COPY --from=vendor /app/vendor/ /var/www/vendor/

# Copy .env.example to .env if it doesn't exist
RUN if [ ! -f .env ]; then \
        cp .env.example .env; \
    fi

# Generate application key
RUN php artisan key:generate

# Expose port
EXPOSE 10000

# Start the application
CMD php artisan config:clear && \
    php artisan cache:clear && \
    php artisan view:clear && \
    php artisan serve --host=0.0.0.0 --port=10000
