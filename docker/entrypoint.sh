#!/bin/sh
set -eu

php artisan migrate --force

exec /usr/bin/supervisord -c /etc/supervisord.conf
