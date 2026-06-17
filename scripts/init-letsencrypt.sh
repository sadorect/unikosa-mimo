#!/usr/bin/env bash
#
# Obtain the initial Let's Encrypt certificate for UNIKOSA on a fresh VPS.
# Run once after DNS points at the server and `docker compose up -d nginx` is up
# (nginx must be serving :80 so the HTTP-01 challenge can be answered).
#
# Usage: DOMAIN=alumni.unikosa.org EMAIL=admin@unikosa.org ./scripts/init-letsencrypt.sh
#
set -euo pipefail

DOMAIN="${DOMAIN:?Set DOMAIN, e.g. DOMAIN=alumni.unikosa.org}"
EMAIL="${EMAIL:?Set EMAIL, e.g. EMAIL=admin@unikosa.org}"
STAGING="${STAGING:-0}" # set STAGING=1 to test against Let's Encrypt staging

staging_arg=""
if [ "$STAGING" != "0" ]; then
    staging_arg="--staging"
fi

echo "Requesting certificate for ${DOMAIN} ..."
docker compose run --rm --entrypoint "\
  certbot certonly --webroot -w /var/www/certbot \
    ${staging_arg} \
    --email ${EMAIL} \
    -d ${DOMAIN} \
    --rsa-key-size 4096 \
    --agree-tos \
    --non-interactive" certbot

echo "Certificate obtained. Now enable nginx/ssl.conf (see nginx/ssl.conf.example) and run:"
echo "  docker compose up -d --build nginx"
