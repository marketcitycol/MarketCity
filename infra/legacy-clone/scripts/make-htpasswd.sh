#!/usr/bin/env bash
# Usage: scripts/make-htpasswd.sh <user>   (asks for the password; nothing is echoed or stored in git)
set -euo pipefail
cd "$(dirname "$0")/.."
user="${1:?user}"
read -rsp "Password for $user: " pass; echo
printf '%s:%s\n' "$user" "$(openssl passwd -apr1 "$pass")" > nginx/htpasswd
chmod 600 nginx/htpasswd
echo "nginx/htpasswd written (git-ignored)."
