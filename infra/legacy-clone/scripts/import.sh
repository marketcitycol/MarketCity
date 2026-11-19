#!/usr/bin/env bash
# Import a production backup into the isolated clone.
# Usage: scripts/import.sh <files-archive.(zip|tar.gz|tgz)> <database.sql[.gz]>
# The originals are never modified: we record their SHA-256 and work on copies.
source "$(dirname "$0")/lib.sh"
files="${1:?files archive}"; dump="${2:?sql dump}"

log "sha256 of originals:"
sha256sum "$files" "$dump" | tee -a data/reports/originals.sha256

log "Extracting files archive"
tmp="$(mktemp -d)"; trap 'rm -rf "$tmp"' EXIT
case "$files" in
  *.zip) unzip -q "$files" -d "$tmp" ;;
  *.tar.gz|*.tgz) tar -xzf "$files" -C "$tmp" ;;
  *) echo "Unsupported archive: $files"; exit 1 ;;
esac
src="$(dirname "$(find "$tmp" -maxdepth 4 -type d -name plugins -path '*wp-content/plugins' | head -1)")"
[[ -d "$src" ]] || { echo "No wp-content/ found in archive"; exit 1; }
rm -rf data/site/wp-content && mkdir -p data/site && cp -a "$src" data/site/wp-content
rm -f data/site/wp-content/mu-plugins/000-clone-guard.php
chown -R 33:33 data/site/wp-content

log "Copying SQL dump"
case "$dump" in
  *.gz) gunzip -c "$dump" > data/import/dump.sql ;;
  *) cp "$dump" data/import/dump.sql ;;
esac
prefix="$(grep -oE 'CREATE TABLE `[A-Za-z0-9_]+options`' data/import/dump.sql | head -1 | sed -E 's/CREATE TABLE `(.*)options`/\1/')"
if [[ -n "$prefix" && "$prefix" != "${TABLE_PREFIX}" ]]; then
  echo "Dump table prefix is '$prefix' but TABLE_PREFIX='${TABLE_PREFIX}'. Fix .env and rerun."; exit 1
fi

log "Starting database (internal network only)"
docker compose up -d db wordpress mailpit
for _ in $(seq 1 60); do docker compose exec -T db healthcheck.sh --connect >/dev/null 2>&1 && break; sleep 2; done

log "Importing database"
wp db reset --yes --defaults
wp db import /import/dump.sql --defaults
rm -f data/import/dump.sql

log "Rewriting URLs ${SOURCE_URL} -> ${CLONE_URL} (guid column untouched)"
wp search-replace "${SOURCE_URL}" "${CLONE_URL}" --all-tables-with-prefix --skip-columns=guid --precise --report-changed-only
wp search-replace "${SOURCE_URL/https:/http:}" "${CLONE_URL}" --all-tables-with-prefix --skip-columns=guid --precise --report-changed-only

"$CLONE_DIR/scripts/neutralize.sh"
docker compose up -d
log "Clone ready at ${CLONE_URL} (basic auth). Mail catcher on 127.0.0.1:${MAIL_PORT:-8025}."
