#!/bin/bash
set -euo pipefail

cd "$(dirname "$0")"

REPO="jorgedeboli-boop/erp-apollocyntra"
SERVER="quintagracia@vl24696.dinaserver.com"
SERVER_PATH="/home/quintagracia/erp-apollocyntra"

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

echo "🖥️  Configurando servidor..."
ssh "$SERVER" "test -d ${SERVER_PATH}/.git" 2>/dev/null || \
  ssh "$SERVER" "git clone git@github.com:${REPO}.git ${SERVER_PATH}"

echo "✅ Repositorio y servidor listos."
echo "   Usa ./deploy.sh para commitear y actualizar el servidor."
