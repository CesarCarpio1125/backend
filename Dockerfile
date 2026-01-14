# Stage 1: Composer dependencies
FROM composer:2 as vendor

WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --optimize-autoloader --no-scripts

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

# Create .env file if it doesn't exist and generate key if needed
RUN if [ ! -f .env ]; then \
        touch .env && \
        echo "APP_NAME=Laravel" >> .env && \
        echo "APP_ENV=local" >> .env && \
        echo "APP_KEY=" >> .env && \
        echo "APP_DEBUG=true" >> .env && \
        echo "APP_URL=http://localhost" >> .env; \
    fi && \
    php artisan key:generate

# Expose port
EXPOSE 10000

# Start the application
CMD php artisan serve --host=0.0.0.0 --port=10000