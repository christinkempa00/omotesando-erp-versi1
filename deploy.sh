#!/usr/bin/env bash
# Push specific changed files/folders to the Hostinger server over SSH
# (scp, key-based auth via the "hostinger-omotesando" entry in ~/.ssh/config
# — no password ever touches this script or any file in this repo).
#
# Usage:
#   ./deploy.sh path/to/file.php [more paths...]
#   ./deploy.sh app/Http/Controllers/GA/AssetController.php
#   ./deploy.sh resources/views/ga
#
# Paths are relative to the project root (same as you'd give to git).
# public/, .env, vendor/, storage/, node_modules/, .git/, .vscode/ are
# ALWAYS refused, even if you pass them by accident — those are
# server-customized/sensitive and must never be overwritten from here.

set -euo pipefail

SSH_ALIAS="hostinger-omotesando"
REMOTE_BASE="/home/u588808719/domains/portal.allez-group.com/laravel_app"
PROTECTED_PATHS=("public" ".env" "vendor" "storage" "node_modules" ".git" ".vscode")

if [ "$#" -eq 0 ]; then
    echo "Usage: ./deploy.sh path/to/file-or-folder [more paths...]"
    exit 1
fi

needs_cache_reminder=false

for path in "$@"; do
    skip=false
    for protected in "${PROTECTED_PATHS[@]}"; do
        if [[ "$path" == "$protected" || "$path" == "$protected"/* ]]; then
            echo "SKIP (dilindungi, tidak boleh dideploy): $path"
            skip=true
            break
        fi
    done
    [ "$skip" = true ] && continue

    if [ ! -e "$path" ]; then
        echo "SKIP (tidak ditemukan lokal): $path"
        continue
    fi

    remote_dir=$(dirname "$path")
    echo "Upload: $path -> ${REMOTE_BASE}/${remote_dir}/"

    ssh "$SSH_ALIAS" "mkdir -p '${REMOTE_BASE}/${remote_dir}'"
    scp -r -q "$path" "${SSH_ALIAS}:${REMOTE_BASE}/${remote_dir}/"

    case "$path" in
        config/*|routes/*|bootstrap/*|*.blade.php)
            needs_cache_reminder=true
            ;;
    esac
done

echo ""
echo "Selesai."

if [ "$needs_cache_reminder" = true ]; then
    echo ""
    echo "PENTING — file yang diupload menyentuh config/routes/bootstrap/blade."
    echo "Jalankan ini supaya perubahan benar-benar aktif di server:"
    echo ""
    echo "  ssh $SSH_ALIAS 'cd $REMOTE_BASE && php artisan optimize:clear && php artisan config:cache && php artisan route:cache && php artisan view:cache'"
fi
