#!/bin/sh
set -eu

cd /var/www/html

lock_hash=$(sha256sum package-lock.json | awk '{print $1}')
current=""
if [ -f node_modules/.package-lock.sha ]; then
    current=$(cat node_modules/.package-lock.sha)
fi

if [ "$lock_hash" != "$current" ] || [ ! -x node_modules/.bin/vite ]; then
    echo "Instalando dependencias de Node..."
    npm ci
    echo "$lock_hash" > node_modules/.package-lock.sha
fi

exec npm run dev -- --host 0.0.0.0 --port 5173
