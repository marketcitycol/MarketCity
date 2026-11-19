#!/usr/bin/env bash
# Delete the clone and every copy of production data it holds (end of Phase 0). Irreversible.
source "$(dirname "$0")/lib.sh"
read -rp "Type DESTRUIR to delete the clone and its data: " ok
[[ "$ok" == "DESTRUIR" ]] || { echo "Cancelled."; exit 1; }
docker compose --profile tools down -v --remove-orphans
log "Clone destroyed (containers and DB volume removed)"
cp data/reports/clone.log "/tmp/clone-destroyed-$(date +%s).log" 2>/dev/null || true
rm -rf data
echo "data/ removed. Remember to delete the original backup files as agreed."
