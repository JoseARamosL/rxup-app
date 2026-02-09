#!/bin/sh
set -e

# 1. Limpieza radical
echo "Limpiando caché física..."
rm -rf /var/www/html/var/cache/*

# 2. Asegurar existencia y permisos agresivos
mkdir -p /var/www/html/var/cache /var/www/html/var/log /var/www/html/var/sessions
chown -R www-data:www-data /var/www/html/var
chmod -R 777 /var/www/html/var

# 3. Migraciones
# Ejecutamos como root, el chmod posterior se encarga de los permisos si se crea algo nuevo
echo "Ejecutando migraciones..."
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

# 4. RE-APLICAR permisos tras las migraciones (por si Doctrine creó logs como root)
chmod -R 777 /var/www/html/var
chown -R www-data:www-data /var/www/html/var

echo "Limpiando y regenerando metadatos de Doctrine..."
# Borramos carpetas de caché a mano primero
rm -rf /var/www/html/var/cache/prod/*

# Comandos oficiales de limpieza
php bin/console doctrine:cache:clear-metadata --env=prod
php bin/console doctrine:cache:clear-query --env=prod
php bin/console doctrine:cache:clear-result --env=prod

# Forzamos el calentamiento de la caché para que Doctrine lea la DB ahora mismo
php bin/console cache:warmup --env=prod

echo "Arrancando Apache..."
exec apache2-foreground
