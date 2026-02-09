# Usamos PHP 8.3 con Apache incluido (mucho más fácil para hosting)
FROM php:8.3-apache

# Instalar dependencias necesarias para Postgres y Symfony
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libicu-dev \
    libzip-dev \
    zip \
    unzip \
    && docker-php-ext-install intl pdo pdo_pgsql zip

# Habilitar el módulo de reescritura de Apache (vital para Symfony)
RUN a2enmod rewrite

# Copiar el código del proyecto
COPY . /var/www/html

# Configurar Apache para que apunte a /public en lugar de a la raíz
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Instalar dependencias de producción
RUN composer install --no-dev --optimize-autoloader --no-scripts

# Asegurarnos de que las carpetas existen
RUN mkdir -p /var/www/html/var/cache /var/www/html/var/log

# Cambiamos el dueño de TODO el proyecto a www-data
# Esto es lo más seguro para que Symfony no tenga problemas de escritura
RUN chown -R www-data:www-data /var/www/html

# Damos permisos de lectura/escritura/ejecución al dueño
RUN chmod -R 775 /var/www/html/var

EXPOSE 80

# Usamos el script como punto de entrada
ENTRYPOINT ["docker-entrypoint.sh"]
