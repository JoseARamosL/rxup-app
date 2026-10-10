FROM php:8.3-fpm

# Instalar dependencias del sistema y extensiones de PHP
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libicu-dev \
    libzip-dev \
    libgd-dev \
    zip \
    unzip \
    && docker-php-ext-install intl pdo_pgsql zip gd

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configurar el directorio de trabajo
WORKDIR /var/www/html

EXPOSE 9000
