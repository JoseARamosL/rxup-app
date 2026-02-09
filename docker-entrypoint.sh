#!/bin/sh
set -e

# Borrar la caché físicamente para que no haya archivos con dueño "root"
echo "Limpiando caché física..."
rm -rf /var/www/html/var/cache/*

# Crear las carpetas de nuevo por si acaso
mkdir -p /var/www/html/var/cache /var/www/html/var/log /var/www/html/var/sessions

# Permisos totales al usuario www-data (el que usa Apache)
echo "Ajustando permisos de var/..."
chown -R www-data:www-data /var/www/html/var
chmod -R 777 /var/www/html/var

# Migraciones
echo "Ejecutando migraciones..."
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

echo "Arrancando servidor..."
exec apache2-foreground
