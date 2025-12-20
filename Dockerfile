FROM php:8.2-cli

# Dependencias del sistema
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

# Instalar Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Directorio de trabajo
WORKDIR /var/www

# Copiar proyecto
COPY . .

# Instalar dependencias Laravel
RUN composer install --no-dev --no-interaction --optimize-autoloader

# Permisos
RUN chmod -R 775 storage bootstrap/cache

# Puerto Render
EXPOSE 10000

# Arranque
CMD php artisan serve --host=0.0.0.0 --port=10000
