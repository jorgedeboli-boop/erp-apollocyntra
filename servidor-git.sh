#!/bin/bash
# Ejecutar EN el servidor apollocyntra (via ssh). Repara .git roto y actualiza desde GitHub.
set -euo pipefail

SERVER_PATH="/home/apollocyntra/erp"
REPO="git@github.com:jorgedeboli-boop/erp-apollocyntra.git"
BRANCH="main"

cd "$SERVER_PATH"
REAL_PATH="$(pwd -P)"

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
fi

if git remote get-url origin &>/dev/null; then
  git remote set-url origin "$REPO"
else
  git remote add origin "$REPO"
fi

echo "📡 Remoto origin: $(git remote get-url origin)"

if ! git fetch origin; then
  echo ""
  echo "❌ No se pudo conectar con GitHub desde el servidor."
  echo "   Comprueba en el servidor: ssh -T git@github.com"
  exit 1
fi

NEW_HEAD="$(git rev-parse "origin/$BRANCH")"
OLD_HEAD="$(git rev-parse HEAD 2>/dev/null || echo "")"

apply_full_reset() {
  git checkout -B "$BRANCH" "origin/$BRANCH" 2>/dev/null || git reset --hard "origin/$BRANCH"
}

apply_changed_files_only() {
  local file failed=0 updated=0
  local -a files=()

  if [[ -n "$OLD_HEAD" ]]; then
    while IFS= read -r file; do
      [[ -n "$file" ]] && files+=("$file")
    done < <(git diff --name-only "$OLD_HEAD" "$NEW_HEAD")
  else
    while IFS= read -r file; do
      [[ -n "$file" ]] && files+=("$file")
    done < <(git diff-tree --no-commit-id --name-only -r "$NEW_HEAD")
  fi

  if [[ ${#files[@]} -eq 0 ]]; then
    git branch -f "$BRANCH" "$NEW_HEAD"
    git symbolic-ref HEAD "refs/heads/$BRANCH" 2>/dev/null || git checkout -B "$BRANCH" "$NEW_HEAD"
    return 0
  fi

  echo "⚠️  Actualizando ${#files[@]} archivo(s) (modo permisos limitados)..."

  for file in "${files[@]}"; do
    if git checkout "$NEW_HEAD" -- "$file" 2>/dev/null; then
      updated=$((updated + 1))
    else
      echo "   ⚠️  Sin permiso: $file"
      failed=$((failed + 1))
    fi
  done

  git branch -f "$BRANCH" "$NEW_HEAD"
  git symbolic-ref HEAD "refs/heads/$BRANCH" 2>/dev/null || true

  echo "   ✅ Actualizados: $updated | ⚠️  Omitidos: $failed"
  if [[ $failed -gt 0 ]]; then
    echo "   (Omitidos: PDFs/firmas generados por la web con otro propietario.)"
  fi
}

if [[ -z "$OLD_HEAD" || "$OLD_HEAD" == "$NEW_HEAD" ]]; then
  if ! apply_full_reset 2>/dev/null; then
    git branch -f "$BRANCH" "$NEW_HEAD"
    git checkout -B "$BRANCH" "$NEW_HEAD" 2>/dev/null || apply_changed_files_only
  fi
elif apply_full_reset 2>/dev/null; then
  :
else
  apply_changed_files_only
fi

echo "✅ Servidor actualizado en $BRANCH ($(git rev-parse --short HEAD))"
