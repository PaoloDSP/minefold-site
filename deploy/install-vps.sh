#!/usr/bin/env bash
# Installation du nouveau site Minefold sur la VM (Ubuntu + nginx + PHP 8.5 + MariaDB).
# Usage : sudo bash install-vps.sh
# Relançable sans risque : chaque étape vérifie ce qui existe déjà.
set -euo pipefail

APP=/var/www/minefold-site
REPO=https://github.com/PaoloDSP/minefold-site.git
DOMAIN=minefold.fr
NGINX_CONF=/etc/nginx/sites-available/www.minefold.fr
PHP_SOCK=/var/run/php/php8.5-fpm.sock
DB_NAME=minefold_site
DB_USER=minefold_site
BACKUP_DIR=/var/backups/minefold-wordpress-$(date +%F)
export COMPOSER_HOME=/var/cache/composer-www COMPOSER_ALLOW_SUPERUSER=1

step() { printf '\n\033[1;34m== %s\033[0m\n' "$*"; }
ok() { printf '\033[0;32m   ✓ %s\033[0m\n' "$*"; }
as_www() { sudo -u www-data -H env COMPOSER_HOME="$COMPOSER_HOME" "$@"; }

if [[ $EUID -ne 0 ]]; then
    echo "Lancez ce script avec sudo : sudo bash $0" >&2
    exit 1
fi

step "1/7 Vérifications"
[[ -S $PHP_SOCK ]] || { echo "PHP 8.5 FPM introuvable ($PHP_SOCK)" >&2; exit 1; }
for cmd in git composer mysql nginx php; do command -v $cmd >/dev/null || { echo "Commande manquante : $cmd" >&2; exit 1; }; done
php -m | grep -qi pdo_mysql || { echo "Extension PHP pdo_mysql manquante" >&2; exit 1; }
ok "PHP $(php -r 'echo PHP_VERSION;'), git, composer, MariaDB et nginx présents"

step "2/7 Base de données"
mkdir -p /root/.minefold && chmod 700 /root/.minefold
if [[ -f /root/.minefold/db_password ]]; then
    DB_PASS=$(cat /root/.minefold/db_password)
    ok "Mot de passe de base déjà généré (réutilisé)"
else
    DB_PASS=$(openssl rand -hex 24)
    echo "$DB_PASS" > /root/.minefold/db_password && chmod 600 /root/.minefold/db_password
fi
mysql <<SQL
CREATE DATABASE IF NOT EXISTS \`$DB_NAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';
ALTER USER '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';
GRANT ALL PRIVILEGES ON \`$DB_NAME\`.* TO '$DB_USER'@'localhost';
FLUSH PRIVILEGES;
SQL
ok "Base « $DB_NAME » prête"

step "3/7 Code du site"
mkdir -p "$COMPOSER_HOME" && chown www-data:www-data "$COMPOSER_HOME"
if [[ -d $APP/.git ]]; then
    as_www git -C "$APP" pull --ff-only
    ok "Code mis à jour"
else
    mkdir -p "$APP" && chown www-data:www-data "$APP"
    as_www git clone "$REPO" "$APP"
    ok "Code téléchargé dans $APP"
fi
cd "$APP"
as_www composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
ok "Dépendances installées"

step "4/7 Configuration (.env)"
if [[ ! -f .env ]]; then
    cat > .env <<ENV
APP_NAME=Minefold
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://$DOMAIN

APP_LOCALE=fr
APP_FALLBACK_LOCALE=fr
APP_FAKER_LOCALE=fr_FR
APP_MAINTENANCE_DRIVER=file
BCRYPT_ROUNDS=12

LOG_CHANNEL=daily
LOG_LEVEL=warning

DB_CONNECTION=mariadb
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=$DB_NAME
DB_USERNAME=$DB_USER
DB_PASSWORD=$DB_PASS

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_SECURE_COOKIE=true
CACHE_STORE=database
QUEUE_CONNECTION=database
FILESYSTEM_DISK=local

# Twitch (https://dev.twitch.tv/console/apps) : détecte si la chaîne est en direct
TWITCH_CLIENT_ID=
TWITCH_CLIENT_SECRET=

# HelloAsso (espace association > Intégrations et API) : places vendues / restantes
HELLOASSO_CLIENT_ID=
HELLOASSO_CLIENT_SECRET=
HELLOASSO_ORGANIZATION=minefold
ENV
    chown www-data:www-data .env && chmod 640 .env
    as_www php artisan key:generate --force
    ok ".env créé"
else
    ok ".env existant conservé"
fi

step "5/7 Base, fichiers et cache"
as_www php artisan migrate --force
if [[ ! -f /root/.minefold/seeded ]]; then
    as_www php artisan db:seed --force
    touch /root/.minefold/seeded
    ok "Contenu de départ ajouté (Geek Area Party 2027, replays, réglages)"
fi
as_www php artisan storage:link || true
as_www php artisan optimize
as_www php artisan filament:optimize
ok "Site prêt"

cat > /etc/cron.d/minefold-site <<CRON
* * * * * www-data cd $APP && php artisan schedule:run >> /dev/null 2>&1
CRON
ok "Tâche planifiée installée (synchro HelloAsso toutes les 5 min)"

step "6/7 Bascule de $DOMAIN vers le nouveau site"
echo "   L'ancien WordPress (/var/www/minefold) ne sera plus servi. Il n'est PAS supprimé."
read -r -p "   Basculer maintenant ? [o/N] " answer < /dev/tty
if [[ ! $answer =~ ^[oOyY]$ ]]; then
    echo "   Bascule annulée. Relancez le script quand vous êtes prêt."
    exit 0
fi
mkdir -p "$BACKUP_DIR"
[[ -f $BACKUP_DIR/nginx-www.minefold.fr.conf ]] || cp "$NGINX_CONF" "$BACKUP_DIR/nginx-www.minefold.fr.conf"
cat > "$NGINX_CONF" <<NGINX
server {
    listen 80;
    listen [::]:80;
    server_name www.$DOMAIN $DOMAIN;
    return 301 https://\$host\$request_uri;
}

server {
    listen 443 ssl;
    listen [::]:443 ssl;
    server_name www.$DOMAIN $DOMAIN;

    access_log /var/log/nginx/http_minefold_fr_access.log;
    error_log  /var/log/nginx/http_minefold_fr_error.log;

    ssl_certificate /etc/letsencrypt/live/$DOMAIN/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/$DOMAIN/privkey.pem;
    include /etc/letsencrypt/options-ssl-nginx.conf;
    ssl_dhparam /etc/letsencrypt/ssl-dhparams.pem;

    root $APP/public;
    index index.php;
    charset utf-8;
    client_max_body_size 20M;

    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~* \.(?:css|js|woff2|png|jpg|jpeg|webp|svg|ico)\$ {
        expires 30d;
        access_log off;
        try_files \$uri /index.php?\$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ ^/index\.php(/|\$) {
        fastcgi_pass unix:$PHP_SOCK;
        fastcgi_split_path_info ^(.+\.php)(/.*)\$;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT \$realpath_root;
        fastcgi_param HTTPS on;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ \.php\$ {
        return 404;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
NGINX
if nginx -t; then
    systemctl reload nginx
    ok "https://$DOMAIN sert maintenant le nouveau site"
else
    cp "$BACKUP_DIR/nginx-www.minefold.fr.conf" "$NGINX_CONF"
    nginx -t && systemctl reload nginx
    echo "Erreur de configuration nginx : l'ancienne configuration a été remise en place." >&2
    exit 1
fi

step "7/7 Compte administrateur"
if as_www php artisan tinker --execute 'echo App\Models\User::count();' 2>/dev/null | grep -q '^0$'; then
    echo "   Créez votre compte pour /admin (le mot de passe ne s'affiche pas quand vous le tapez) :"
    as_www php artisan make:filament-user < /dev/tty
else
    ok "Un compte administrateur existe déjà"
fi

printf '\n\033[1;32mTerminé !\033[0m Site : https://%s   Back-office : https://%s/admin\n' "$DOMAIN" "$DOMAIN"
echo "Retour à l'ancien WordPress si besoin :"
echo "   sudo cp $BACKUP_DIR/nginx-www.minefold.fr.conf $NGINX_CONF && sudo systemctl reload nginx"
