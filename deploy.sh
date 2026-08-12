#!/bin/bash
set -euo pipefail

cd "$(dirname "$0")"

echo "🚀 Deploy: commit + push + servidor"
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

SERVER="apollocyntra@vl24696.dinaserver.com"
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"

echo "🚀 Actualizando servidor..."
ssh "$SERVER" "bash -s" < "${SCRIPT_DIR}/servidor-git.sh"

echo "✅ ¡Todo actualizado!"
