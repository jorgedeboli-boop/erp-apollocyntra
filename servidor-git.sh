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

point_branch_to() {
  local head="$1"
  git update-ref "refs/heads/$BRANCH" "$head"
  git symbolic-ref HEAD "refs/heads/$BRANCH" 2>/dev/null || true
}

prepare_path() {
  local path="$1" dir
  dir="$(dirname "$path")"
  while [[ "$dir" != "." && "$dir" != "/" ]]; do
    chmod g+w "$dir" 2>/dev/null || true
    dir="$(dirname "$dir")"
  done
  [[ -e "$path" ]] && chmod g+w "$path" 2>/dev/null || true
}

checkout_one_file() {
  local file="$1"
  prepare_path "$file"
  if git checkout "$NEW_HEAD" -- "$file" 2>/dev/null; then
    return 0
  fi
  local dir tmp
  dir="$(dirname "$file")"
  mkdir -p "$dir"
  tmp="$(mktemp "${dir}/.deploy.XXXXXX")"
  if git show "$NEW_HEAD:$file" > "$tmp" 2>/dev/null && mv -f "$tmp" "$file" 2>/dev/null; then
    return 0
  fi
  rm -f "$tmp"
  return 1
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
    point_branch_to "$NEW_HEAD"
    return 0
  fi

  echo "⚠️  Actualizando ${#files[@]} archivo(s) (modo permisos limitados)..."

  for file in "${files[@]}"; do
    if checkout_one_file "$file"; then
      updated=$((updated + 1))
    else
      echo "   ⚠️  Sin permiso: $file"
      failed=$((failed + 1))
    fi
  done

  echo "   ✅ Actualizados: $updated | ⚠️  Omitidos: $failed"

  if [[ $failed -gt 0 ]]; then
    echo ""
    echo "❌ Permisos insuficientes en el servidor."
    echo "   Pide a DinaServer o ejecuta (si tienes sudo):"
    echo "   sudo chown -R apollocyntra:apollocyntragrp $REAL_PATH"
    echo "   sudo chmod -R g+rwX $REAL_PATH"
    return 1
  fi

  point_branch_to "$NEW_HEAD"
}

if [[ -z "$OLD_HEAD" || "$OLD_HEAD" == "$NEW_HEAD" ]]; then
  if ! apply_full_reset 2>/dev/null; then
    point_branch_to "$NEW_HEAD" 2>/dev/null || true
    apply_changed_files_only
  fi
elif apply_full_reset 2>/dev/null; then
  :
else
  apply_changed_files_only
fi

echo "✅ Servidor actualizado en $BRANCH ($(git rev-parse --short HEAD))"
