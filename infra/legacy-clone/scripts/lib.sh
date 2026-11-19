#!/usr/bin/env bash
# Shared helpers. Source from the other scripts.
set -euo pipefail
CLONE_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$CLONE_DIR"
[[ -f .env ]] || { echo "Missing .env (copy .env.example and fill it)"; exit 1; }
set -a; source .env; set +a
mkdir -p data/site/wp-content data/import data/reports
chmod a+rwx data/reports  # wp-cli runs as www-data (uid 33)
wp() { docker compose --profile tools run --rm -T wpcli wp --path=/var/www/html "$@"; }
log() { printf '[%s] %s\n' "$(date -u +%FT%TZ)" "$*" | tee -a data/reports/clone.log; }
