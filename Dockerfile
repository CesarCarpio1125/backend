FROM php:8.2-cli

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    && docker-php-ext-install pdo pdo_mysql zip bcmath

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy only composer files first to leverage Docker cache
COPY composer.json composer.lock* ./

# Install dependencies (don't run scripts or autoloader yet)
RUN composer install --no-scripts --no-autoloader --no-interaction --no-dev

# Copy the rest of the application
COPY . .

# Set permissions
RUN chmod -R 777 storage bootstrap/cache

# Generate optimized autoloader and run post-install scripts
RUN composer dump-autoload --optimize && \
    composer run-script post-autoload-dump

# Generate application key if not exists
RUN [ -f .env ] || cp .env.example .env && \
    php artisan key:generate

# Expose port 10000 (used by Render)
EXPOSE 10000

# Start the application
CMD php artisan serve --host=0.0.0.0 --port=10000