#!/bin/sh
set -eu

APP_ROOT=/var/www/html
cd "$APP_ROOT"

log() { echo "[entrypoint] $*"; }

# ---------------------------------------------------------------------------
# Arborescence de storage (le volume monté peut être vide au premier démarrage)
# ---------------------------------------------------------------------------
mkdir -p \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    storage/app/public \
    bootstrap/cache

[ -L public/storage ] || ln -sfn "$APP_ROOT/storage/app/public" public/storage

chown -R www-data:www-data storage bootstrap/cache

# ---------------------------------------------------------------------------
# Clé d'application
# ---------------------------------------------------------------------------
# Sans APP_KEY, Laravel ne peut rien chiffrer. Plutôt que d'échouer, on en
# génère une et on la conserve dans le volume : elle survit aux redémarrages.
KEY_FILE="$APP_ROOT/storage/app/.app_key"

if [ -z "${APP_KEY:-}" ]; then
    if [ -s "$KEY_FILE" ]; then
        APP_KEY=$(cat "$KEY_FILE")
        log "APP_KEY absente : réutilisation de la clé conservée dans storage."
    else
        APP_KEY=$(php artisan key:generate --show)
        printf '%s' "$APP_KEY" > "$KEY_FILE"
        chown www-data:www-data "$KEY_FILE"
        chmod 600 "$KEY_FILE"
        log "ATTENTION : aucune APP_KEY fournie, une clé a été générée."
        log "            Définissez APP_KEY dans .env.docker pour la production."
    fi
    export APP_KEY
fi

# ---------------------------------------------------------------------------
# Attente de la base de données
# ---------------------------------------------------------------------------
if [ "${DB_CONNECTION:-mysql}" = "mysql" ]; then
    # .env.docker sert aux deux conteneurs : le conteneur db crée MYSQL_DATABASE et
    # MYSQL_USER, l'application se connecte avec DB_DATABASE et DB_USERNAME.
    if [ -n "${MYSQL_DATABASE:-}" ] && [ "${DB_DATABASE:-}" != "$MYSQL_DATABASE" ]; then
        log "ATTENTION : DB_DATABASE (${DB_DATABASE:-vide}) diffère de MYSQL_DATABASE ($MYSQL_DATABASE)."
        log "            La connexion sera refusée (erreur 1044) : alignez-les dans .env.docker."
    fi
    if [ -n "${MYSQL_USER:-}" ] && [ "${DB_USERNAME:-}" != "$MYSQL_USER" ]; then
        log "ATTENTION : DB_USERNAME (${DB_USERNAME:-vide}) diffère de MYSQL_USER ($MYSQL_USER)."
    fi

    log "Attente de ${DB_HOST:-db}:${DB_PORT:-3306}..."
    i=0
    until php -r '
        $dsn = sprintf("mysql:host=%s;port=%s", getenv("DB_HOST") ?: "db", getenv("DB_PORT") ?: "3306");
        try { new PDO($dsn, getenv("DB_USERNAME"), getenv("DB_PASSWORD")); exit(0); }
        catch (Throwable $e) { exit(1); }
    ' 2>/dev/null; do
        i=$((i + 1))
        if [ "$i" -ge 60 ]; then
            log "Base de données injoignable après 60 tentatives, abandon."
            exit 1
        fi
        sleep 2
    done
    log "Base de données prête."
fi

# ---------------------------------------------------------------------------
# Migrations et données de démonstration
# ---------------------------------------------------------------------------
if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    log "Migrations..."
    php artisan migrate --force --no-interaction
fi

if [ "${RUN_SEEDERS:-false}" = "true" ]; then
    log "Seeders..."
    php artisan db:seed --force --no-interaction
fi

# ---------------------------------------------------------------------------
# Caches : reconstruits à chaque démarrage, car ils dépendent des variables
# d'environnement fournies par Compose.
# ---------------------------------------------------------------------------
php artisan optimize:clear >/dev/null 2>&1 || true
php artisan config:cache --no-interaction
php artisan route:cache --no-interaction
php artisan view:cache --no-interaction
php artisan event:cache --no-interaction
# Les commandes ci-dessus tournent en root : seeders (logos copiés dans storage/app/public),
# cache en fichiers, journaux. Sans ce chown, php-fpm (www-data) ne pourrait plus y écrire
# et les téléversements échoueraient.
chown -R www-data:www-data bootstrap/cache storage

log "Démarrage des services."
exec "$@"
