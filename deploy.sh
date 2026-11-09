#!/usr/bin/env bash
# Деплой Laufen: обновить код из origin/main, зависимости, миграции и кеши.
# Запуск на сервере из папки проекта: ./deploy.sh
{
    set -euo pipefail
    cd "$(dirname "$0")"

    trap 'echo "!! Деплой упал на строке $LINENO. API в режиме обслуживания (503). Исправь и выполни: php artisan up"' ERR

    echo "==> Режим обслуживания"
    php artisan down --retry=15

    echo "==> Код из main"
    git fetch origin main
    git reset --hard origin/main

    echo "==> Зависимости"
    composer install --no-dev --optimize-autoloader --no-interaction --no-progress

    echo "==> Миграции"
    php artisan migrate --force

    echo "==> Кеши"
    php artisan optimize:clear
    php artisan optimize
    php artisan lighthouse:cache

    echo "==> Перезагрузка PHP-FPM"
    sudo systemctl reload php8.4-fpm

    echo "==> Включение"
    php artisan up

    echo "Готово: $(git log -1 --pretty='%h %s')"
    exit
}