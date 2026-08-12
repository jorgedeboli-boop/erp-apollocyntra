#!/bin/bash
set -euo pipefail

cd "$(dirname "$0")"

echo "🚀 Deploy: commit + push + FTP"
echo ""

prompt_commit_message() {
  if [[ -n "${1:-}" ]]; then
    printf '%s' "$1"
    return
  fi

  if [[ -r /dev/tty ]]; then
    printf 'Describe los cambios: ' >/dev/tty
    IFS= read -r reply </dev/tty || true
    printf '%s' "$reply"
    return
  fi

  osascript \
    -e 'tell application "System Events" to activate' \
    -e 'display dialog "Describe los cambios:" default answer ""' \
    -e 'text returned of result' 2>/dev/null || true
}

mensaje="$(prompt_commit_message "${1:-}")"

if [ -z "$mensaje" ]; then
  echo "❌ Mensaje vacío, cancelado."
  exit 1
fi

git add -A

if git diff --cached --quiet; then
  echo "❌ No hay cambios para commitear."
  if ! git diff --quiet; then
    echo "ℹ️  Hay cambios locales en archivos ignorados por .gitignore (p. ej. login.php, include/config.php)."
    git status --short
  fi
  exit 1
fi

commit_msg_file=$(mktemp)
trap 'rm -f "$commit_msg_file"' EXIT
printf "%s\n" "$mensaje" > "$commit_msg_file"

git commit -F "$commit_msg_file"
git push

echo "🚀 Subiendo archivos por FTP al hosting..."

SFTP_CONFIG=".vscode/sftp.json"
if [[ ! -f "$SFTP_CONFIG" ]]; then
  echo "❌ No se encontró $SFTP_CONFIG"
  exit 1
fi

read -r FTP_HOST FTP_USER FTP_PASS FTP_PATH <<< "$(python3 - <<'PY'
import json
with open(".vscode/sftp.json", encoding="utf-8") as f:
    cfg = json.load(f)
print(cfg["host"], cfg["username"], cfg["password"], cfg.get("remotePath", ""))
PY
)"

LOCAL_DIR="$(pwd)"

LFTP="$(command -v lftp || true)"
if [[ -z "$LFTP" && -x /opt/homebrew/bin/lftp ]]; then
  LFTP="/opt/homebrew/bin/lftp"
fi
if [[ -z "$LFTP" ]]; then
  echo "❌ lftp no está instalado. Instálalo con: brew install lftp"
  exit 1
fi

"$LFTP" -c "
set ftp:ssl-allow no
set cmd:fail-exit yes
open -u ${FTP_USER},${FTP_PASS} ${FTP_HOST}
lcd '${LOCAL_DIR}'
cd ${FTP_PATH:-/}
mirror -R \
  --parallel=4 \
  --exclude .git/ \
  --exclude .vscode/ \
  --exclude .DS_Store \
  --exclude-glob **_notes/** \
  --exclude include/config.php \
  --exclude include/API_KEY_CLAUDE.txt \
  --exclude node_modules/ \
  --exclude vendor/ \
  --exclude photos/ \
  --exclude invoices/ \
  --exclude-glob *.sql \
  --exclude-glob *.bak \
  --exclude-glob *.zip \
  --exclude-glob *.log
quit
"

echo "✅ ¡Todo actualizado en GitHub y en el servidor FTP!"
