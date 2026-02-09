#!/bin/sh
set -e

echo "Esperando a que la base de datos esté lista..."
# Opcional: podrías añadir un sleep 2 aquí

echo "Ejecutando migraciones..."
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

echo "Arrancando servidor..."
exec apache2-foreground
