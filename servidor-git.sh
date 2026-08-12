#!/bin/bash
# Ejecutar EN el servidor apollocyntra (via ssh). Repara .git roto y actualiza desde GitHub.
set -euo pipefail

SERVER_PATH="/home/apollocyntra/erp"
REPO="git@github.com:jorgedeboli-boop/erp-apollocyntra.git"
BRANCH="main"

cd "$SERVER_PATH"
REAL_PATH="$(pwd -P)"

# La carpeta erp suele ser enlace a .ftp-users/erp (propietario distinto).
git config --global --add safe.directory "$SERVER_PATH"
git config --global --add safe.directory "$REAL_PATH"

is_valid_git() {
  [[ -d .git && -d .git/objects && -d .git/refs ]]
}

if ! is_valid_git; then
  echo "⚠️  Repositorio git inválido o incompleto. Reparando..."
  if [[ -d .git ]]; then
    backup=".git.roto.$(date +%Y%m%d%H%M%S)"
    mv .git "$backup"
    echo "   Copia de seguridad: $SERVER_PATH/$backup"
  fi
  git init
  git remote add origin "$REPO" 2>/dev/null || git remote set-url origin "$REPO"
fi

git fetch origin
git checkout -B "$BRANCH" "origin/$BRANCH" 2>/dev/null || git reset --hard "origin/$BRANCH"

echo "✅ Servidor actualizado en $BRANCH ($(git rev-parse --short HEAD))"
