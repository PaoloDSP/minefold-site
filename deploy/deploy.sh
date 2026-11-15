#!/usr/bin/env bash
# Mise à jour du site sur la VM : sudo -u www-data ./deploy/deploy.sh
set -euo pipefail
cd "$(dirname "$0")/.."

php artisan down --retry=30 || true
git pull --ff-only
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
php artisan migrate --force
php artisan storage:link 2>/dev/null || true
php artisan optimize
php artisan filament:optimize
php artisan up

echo "Déploiement terminé."
