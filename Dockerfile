FROM php:8.3-apache

# 1. Instalar dependencias del sistema
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libicu-dev \
    libzip-dev \
    libgd-dev \
    zip \
    unzip \
    && docker-php-ext-install intl pdo pdo_pgsql zip gd

RUN a2enmod rewrite

# 2. Configurar el DocumentRoot de Apache
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# 3. Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# 4. Copiar archivos de dependencias e instalar (para cachear capas)
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction

# 5. Copiar el resto del código
COPY . .

# 6. Crear carpetas necesarias y asegurar que existen antes del chmod
RUN mkdir -p var/cache var/log var/sessions

# 7. Generar Autoloader SIN optimización autoritativa (que a veces genera caché de solo lectura)
RUN composer dump-autoload --optimize --no-dev

# Aseguramos que la carpeta var esté REALMENTE vacía y con permisos de escritura total
RUN rm -rf var/cache/* && mkdir -p var/cache var/log var/sessions
RUN chown -R www-data:www-data /var/www/html/var
RUN chmod -R 777 /var/www/html/var

# 8. Asegurar que el script sea ejecutable
RUN chmod +x /var/www/html/docker-entrypoint.sh
RUN ln -s /var/www/html/docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh

# MUY IMPORTANTE: Nos aseguramos de ser ROOT para el entrypoint
USER root

EXPOSE 80

ENTRYPOINT ["docker-entrypoint.sh"]
