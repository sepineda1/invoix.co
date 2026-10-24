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

chmod -R a+rwX storage bootstrap/cache || true

if [ "${DB_CONNECTION:-}" = "mysql" ] || [ "${DB_CONNECTION:-}" = "mariadb" ]; then
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

git config --global --get-all safe.directory 2>/dev/null | grep -qx '/var/www/html' || git config --global --add safe.directory /var/www/html || true

if [ ! -f .env ]; then
    cp .env.example .env
    echo "Se creó .env a partir de .env.example"
fi

exec 9>storage/framework/.docker-setup.lock
flock 9 || true

if [ -z "${APP_KEY:-}" ]; then
    key_line=$(grep -E '^APP_KEY=' .env || true)
    key_value=$(printf '%s' "${key_line#APP_KEY=}" | tr -d '\015')
    case "$key_value" in
        '' | '""' | "''")
            generated="base64:$(php -r 'echo base64_encode(random_bytes(32));')"
            tmp=$(mktemp)
            awk -v key="$generated" 'BEGIN { done = 0 } /^APP_KEY=/ { print "APP_KEY=" key; done = 1; next } { print } END { if (!done) print "APP_KEY=" key }' .env > "$tmp"
            mv "$tmp" .env
            echo "Se generó APP_KEY en .env"
            ;;
    esac
fi

if [ ! -f vendor/autoload.php ]; then
    echo "Instalando dependencias de Composer..."
    COMPOSER_PROCESS_TIMEOUT=0 composer install --no-interaction --prefer-dist --optimize-autoloader
fi

if [ ! -L public/storage ]; then
    php artisan storage:link --ansi || true
fi

chmod 644 .env || true

flock -u 9 || true

exec docker-php-entrypoint "$@"
