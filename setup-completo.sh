#!/bin/bash
set -euo pipefail

cd "$(dirname "$0")"

REPO="jorgedeboli-boop/erp-apollocyntra"
SERVER="apollocyntra@vl24696.dinaserver.com"
SERVER_PATH="/home/apollocyntra/erp"
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"

echo "📦 Creando repositorio en GitHub (si no existe)..."
if ! git ls-remote "git@github.com:${REPO}.git" HEAD >/dev/null 2>&1; then
  if ! gh auth status >/dev/null 2>&1; then
    echo "❌ GitHub CLI no autenticado. Ejecuta: gh auth login"
    exit 1
  fi
  gh repo create "$REPO" --private --source=. --remote=origin --description "ERP web Apollo Cyntra (erp.apollocyntra.app)"
fi

echo "⬆️  Subiendo código a GitHub..."
git push -u origin main

echo "🖥️  Configurando repositorio git en el servidor..."
ssh "$SERVER" "bash -s" < "${SCRIPT_DIR}/servidor-git.sh"

echo "✅ Repositorio y servidor listos."
echo "   Usa ./deploy.sh para commitear y actualizar el servidor."
