#!/bin/bash
set -euo pipefail

cd "$(dirname "$0")"

mensaje=$(osascript -e 'tell application "System Events" to display dialog "Describe los cambios:" default answer ""' -e 'text returned of result')

if [ -z "$mensaje" ]; then
  echo "❌ Mensaje vacío, cancelado."
  exit 1
fi

git add -A

if git diff --cached --quiet; then
  echo "❌ No hay cambios para commitear."
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

/opt/homebrew/bin/lftp -c "
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
