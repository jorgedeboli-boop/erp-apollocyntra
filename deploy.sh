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

echo "🚀 Actualizando servidor..."
ssh apollocyntra@vl24696.dinaserver.com "cd /home/apollocyntra/erp && git fetch origin && git reset --hard origin/main"

echo "✅ ¡Todo actualizado!"
