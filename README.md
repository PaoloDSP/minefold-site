# minefold.fr

Le site de l'association Minefold : la chaîne **Minefold TV** (Twitch + flux de secours ffplayout) et la **billetterie des LAN** (HelloAsso).

Laravel 13 · Filament 5 (back-office sur `/admin`) · PHP 8.3+ (8.5 en production).

## Ce que fait le site

- **Accueil = la chaîne.** Lecteur en direct + chat. Le serveur interroge Twitch toutes les 45 s :
  - chaîne en direct → lecteur Twitch ;
  - chaîne hors ligne, bannie ou Twitch en panne → flux HLS du serveur Minefold (ffplayout), avec un bandeau d'explication ;
  - le mode peut être forcé dans *Admin › Diffusion & liens*, et le visiteur peut changer de source avec les boutons.
- **Grille des programmes** de la semaine, gérée dans *Admin › Grille des programmes*.
- **Événements** (`/evenements/{slug}`) : billets en cartes, tournois, challenge, programme, matériel, récompenses, infos pratiques. Le paiement se fait sur HelloAsso.
- **Places restantes en direct** : `php artisan helloasso:sync` (lancé toutes les 5 min par le planificateur) lit les statistiques du formulaire HelloAsso et met à jour chaque billet lié à un tarif (champ « ID du tarif HelloAsso »).
- Ancienne adresse `/lanparty` → redirigée vers le prochain événement.

## Installation sur la VM (nginx + PHP-FPM)

**Méthode rapide** (VM Minefold) : `curl -fsSL https://raw.githubusercontent.com/PaoloDSP/minefold-site/main/deploy/install-vps.sh -o ~/install-minefold.sh && sudo bash ~/install-minefold.sh` — crée la base MariaDB, installe le site dans `/var/www/minefold-site`, bascule minefold.fr (après confirmation) en gardant une copie de l'ancienne config nginx, puis crée le compte admin.

Méthode manuelle :

Prérequis : PHP 8.5 avec `php8.5-fpm php8.5-sqlite3 php8.5-mbstring php8.5-xml php8.5-curl php8.5-intl php8.5-gd php8.5-zip`, Composer, git.

```bash
sudo mkdir -p /var/www/minefold-site && sudo chown $USER:www-data /var/www/minefold-site
git clone https://github.com/PaoloDSP/minefold-site.git /var/www/minefold-site
cd /var/www/minefold-site
composer install --no-dev --optimize-autoloader
cp .env.example .env && php artisan key:generate
```

Dans `.env` : `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://beta.minefold.fr`, puis les clés Twitch et HelloAsso (voir plus bas). La base SQLite par défaut suffit largement ; pour MariaDB, renseigner les variables `DB_*`.

```bash
touch database/database.sqlite
php artisan migrate --force --seed      # crée les tables + le contenu de la Geek Area Party 2027
php artisan storage:link
php artisan make:filament-user          # compte admin
sudo chown -R www-data:www-data storage bootstrap/cache database
php artisan optimize && php artisan filament:optimize
```

nginx : copier `deploy/nginx-minefold.conf` (sous-domaine `beta.minefold.fr` pour tester à côté du WordPress actuel), puis `certbot --nginx -d beta.minefold.fr`.

Planificateur (synchro HelloAsso) — `sudo crontab -u www-data -e` :

```
* * * * * cd /var/www/minefold-site && php artisan schedule:run >> /dev/null 2>&1
```

Mises à jour suivantes : `sudo -u www-data ./deploy/deploy.sh`.

## Clés API

| Variable | Où l'obtenir |
|---|---|
| `TWITCH_CLIENT_ID`, `TWITCH_CLIENT_SECRET` | https://dev.twitch.tv/console/apps → nouvelle application (catégorie « Website Integration ») |
| `HELLOASSO_CLIENT_ID`, `HELLOASSO_CLIENT_SECRET` | Espace association HelloAsso → *Mon compte › Intégrations et API* |
| `HELLOASSO_ORGANIZATION` | Le slug de l'association dans l'adresse HelloAsso (`minefold`) |

Sans clés Twitch, le lecteur reste sur Twitch (le mode automatique ne peut pas détecter une panne). Sans clés HelloAsso, les places affichées sont celles saisies à la main.

## Flux de secours (ffplayout)

Dans ffplayout, choisir la sortie **HLS** (par défaut les segments vont dans `/usr/share/ffplayout/public/live/`), exposer ce dossier en HTTPS (ex. `https://tv.minefold.fr/live/stream.m3u8`) avec l'en-tête `Access-Control-Allow-Origin: https://minefold.fr`, puis coller l'adresse du `.m3u8` dans *Admin › Diffusion & liens*. Pour un direct de secours, OBS envoie sur l'ingest live de ffplayout en plus de Twitch.

## Développement

```bash
composer install && cp .env.example .env && php artisan key:generate
php artisan migrate --seed && php artisan serve
php artisan test
```

Front public sans étape de build : `public/css/site.css`, `public/js/player.js`, polices et hls.js auto-hébergés dans `public/`.
