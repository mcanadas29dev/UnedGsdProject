#!/bin/bash
set -e

# ------------------------------
# Configuración
# ------------------------------
ENV=test
DB_FILE="var/test.db"

echo "=== Limpieza de la base de datos de test ==="
if [ -f "$DB_FILE" ]; then
    echo "Eliminando base de datos SQLite existente..."
    # rm "$DB_FILE"
fi

# ------------------------------
# Crear base de datos y esquema
# ------------------------------
echo "=== Creando base de datos ==="
# php bin/console doctrine:database:create --env=$ENV || true

echo "=== Creando esquema de Doctrine ==="
# php bin/console doctrine:schema:create --env=$ENV

# ------------------------------
# Cargar fixtures
# ------------------------------
echo "=== Cargando fixtures ==="
# php bin/console doctrine:fixtures:load --env=$ENV --no-interaction

# ------------------------------
# Ejecutar PHPUnit
# ------------------------------
echo "=== Ejecutando tests ==="
php bin/phpunit --colors=always

echo "=== Tests finalizados ==="
