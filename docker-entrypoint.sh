#!/bin/sh
set -e

# Ejecutar migraciones antes de arrancar el servidor
echo "Ejecutando migraciones..."
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

# Arrancar Apache en primer plano (el comando original del Dockerfile)
apache2-foreground
