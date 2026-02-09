#!/bin/sh
set -e

# Aseguramos permisos de escritura en tiempo de ejecución
echo "Ajustando permisos de caché..."
chown -R www-data:www-data /var/www/html/var

echo "Ejecutando migraciones..."
# Usamos su-exec o simplemente php (siendo root al inicio del script)
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

echo "Limpiando caché de producción..."
php bin/console cache:clear --env=prod --no-warmup

echo "Arrancando servidor..."
exec apache2-foreground
