#!/bin/bash
set -euo pipefail

cd "$(dirname "$0")"

SERVER="apollocyntra@vl24696.dinaserver.com"
SERVER_PATH="/home/apollocyntra/erp"

echo "🚀 Deploy: commit + push + servidor"
echo ""

git add -A

if git diff --cached --quiet; then
  if ! git diff --quiet; then
    echo "ℹ️  Hay cambios en archivos ignorados por .gitignore (p. ej. login.php, include/config.php)."
    git status --short
  fi
  echo "ℹ️  No hay cambios para commitear. Actualizando solo el servidor..."
else
  if [[ -r /dev/tty ]]; then
    printf 'Describe los cambios: ' >/dev/tty
    IFS= read -r mensaje </dev/tty || true
  else
    mensaje=$(osascript -e 'tell application "System Events" to display dialog "Describe los cambios:" default answer ""' -e 'text returned of result' 2>/dev/null || true)
  fi

  if [ -z "${mensaje:-}" ]; then
    echo "❌ Mensaje vacío, cancelado."
    exit 1
  fi

  commit_msg_file=$(mktemp)
  trap 'rm -f "$commit_msg_file"' EXIT
  printf "%s\n" "$mensaje" > "$commit_msg_file"

  git commit -F "$commit_msg_file"
  git push
fi

echo "🚀 Actualizando servidor..."
ssh "$SERVER" "cd ${SERVER_PATH} && git fetch origin && git reset --hard origin/main"

echo "✅ ¡Todo actualizado!"
