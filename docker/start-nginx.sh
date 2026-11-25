#!/bin/sh
set -eu

# Render can probe /up as soon as the container starts. Wait until PHP-FPM
# accepts FastCGI connections so Nginx does not answer that first probe with 502.
attempt=0
until php -r '$connection = @fsockopen("127.0.0.1", 9000); exit($connection ? 0 : 1);'; do
    attempt=$((attempt + 1))

    if [ "$attempt" -ge 100 ]; then
        echo "PHP-FPM did not become ready; Nginx will not start." >&2
        exit 1
    fi

    sleep 0.1
done

exec nginx -g "daemon off;"
