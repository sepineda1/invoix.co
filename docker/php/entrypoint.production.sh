#!/bin/sh
set -eu

cd /var/www/html

mkdir -p \
    storage/app/public \
    storage/app/private \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

if [ -z "${APP_KEY:-}" ]; then
    echo "APP_KEY no está definido. El panel debe entregar el .env de esta empresa." >&2
    exit 1
fi

if [ "${DB_CONNECTION:-}" = "mysql" ]; then
    echo "Esperando a MySQL en ${DB_HOST:-mysql}:${DB_PORT:-3306}..."
    i=0
    while [ "$i" -lt 60 ]; do
        if php -r 'try { new PDO(sprintf("mysql:host=%s;port=%s;dbname=%s", getenv("DB_HOST"), getenv("DB_PORT") ?: "3306", getenv("DB_DATABASE")), getenv("DB_USERNAME"), getenv("DB_PASSWORD")); exit(0); } catch (Throwable $e) { exit(1); }'; then
            break
        fi
        i=$((i + 1))
        sleep 2
    done
    if [ "$i" -ge 60 ]; then
        echo "MySQL no respondió a tiempo." >&2
        exit 1
    fi
fi

if [ -n "${REDIS_HOST:-}" ]; then
    echo "Esperando a Redis en ${REDIS_HOST}:${REDIS_PORT:-6379}..."
    i=0
    while [ "$i" -lt 60 ]; do
        if php -r '$s=@fsockopen(getenv("REDIS_HOST"), (int) (getenv("REDIS_PORT") ?: 6379), $e, $t, 2); if ($s) { fclose($s); exit(0); } exit(1);'; then
            break
        fi
        i=$((i + 1))
        sleep 1
    done
    if [ "$i" -ge 60 ]; then
        echo "Redis no respondió a tiempo." >&2
        exit 1
    fi
fi

if [ "${1:-}" = "nginx" ] && [ -n "${FPM_HOST:-}" ]; then
    case "$FPM_HOST" in
        *[!a-z0-9-]*)
            echo "FPM_HOST no es válido." >&2
            exit 1
            ;;
    esac
    sed -i "s/__FPM_HOST__/${FPM_HOST}/g" /etc/nginx/conf.d/default.conf
fi

if [ ! -e public/storage ]; then
    php artisan storage:link --no-interaction || ln -sfn /var/www/html/storage/app/public /var/www/html/public/storage
fi

mkdir -p storage/framework
exec 9>storage/framework/.migrate.lock
flock 9
php artisan migrate --force --no-interaction
if [ -n "${ERP_ADMIN_EMAIL:-}" ] && [ -n "${ERP_ADMIN_PASSWORD:-}" ]; then
    php artisan db:seed --force --no-interaction
fi
flock -u 9

exec docker-php-entrypoint "$@"
