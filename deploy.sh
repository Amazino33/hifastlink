#!/bin/bash
#
# HiFastLink deployment.
#
# This repository is checked out TWICE on the server:
#   .           main Laravel app (admin panel, RADIUS API)  →  hifastlink.com/dashboard
#   public/app  customer PWA clone (separate git checkout)  →  app.hifastlink.com
#
# Unlike BasmelCare, the two checkouts are independent — each must be pulled
# separately. Each has its own .env, composer.json, and cache.
#
# Front-end assets are NOT built here: public/build is committed to git, so
# run `npm run build` in the app you changed and commit the result BEFORE
# deploying. The server needs no node.

set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
APP_CLONE="$ROOT/public/app"
APPS=("$ROOT" "$APP_CLONE")

step() { printf '\n==> %s\n' "$1"; }

on_failure() {
    printf '\n!! DEPLOY FAILED — one or both apps may still be in maintenance mode.\n'
    printf '   Fix the problem and re-run ./deploy.sh, or bring them up manually:\n'
    for app in "${APPS[@]}"; do
        printf '     (cd %s && php artisan up)\n' "$app"
    done
}
trap on_failure ERR

# ── 0. Pre-flight ────────────────────────────────────────────────────
# The customer app's MikroTik redirect URL is built from APP_URL — if it is
# missing or still localhost, the Connect button on app.hifastlink.com will
# redirect users to the wrong host. Catch it before anything goes offline.
step "Checking configuration"

if [ ! -f "$APP_CLONE/.env" ]; then
    printf '\n!! public/app/.env is missing.\n'
    printf '   Copy .env.example there and configure it, then re-run.\n\n'
    exit 1
fi

if ! grep -qE '^APP_URL=.+' "$APP_CLONE/.env"; then
    printf '\n!! APP_URL is not set in public/app/.env\n'
    printf '   Add the customer app address, e.g.\n'
    printf '     APP_URL=https://app.hifastlink.com\n'
    printf '   Nothing has been changed. Re-run ./deploy.sh afterwards.\n\n'
    exit 1
fi

echo "    APP_URL (customer app): $(grep -E '^APP_URL=' "$APP_CLONE/.env" | cut -d= -f2-)"

# ── 1. Maintenance mode ──────────────────────────────────────────────
step "Taking both apps offline"
for app in "${APPS[@]}"; do
    (cd "$app" && php artisan down --retry=60) || true
done

# ── 2. Pull — two separate git clones ────────────────────────────────
step "Pulling main app"
cd "$ROOT"
before_root="$(git rev-parse HEAD)"
git pull
after_root="$(git rev-parse HEAD)"

step "Pulling customer app clone (public/app)"
cd "$APP_CLONE"
git pull

# Detect if markup changed without a rebuilt public/build in the main pull.
# (The app clone is the same code, so checking the main clone is sufficient.)
if [ "$before_root" != "$after_root" ]; then
    changed="$(git -C "$ROOT" diff --name-only "$before_root" "$after_root" 2>/dev/null || true)"
    markup_changed=no
    build_changed=no
    case "$changed" in *blade.php*|*resources/css/*) markup_changed=yes ;; esac
    case "$changed" in *public/build/*) build_changed=yes ;; esac
    if [ "$markup_changed" = yes ] && [ "$build_changed" = no ]; then
        export DEPLOY_ASSETS_WARNING=1
    fi
fi

# Bash reads this script incrementally, so `git pull` above can rewrite THIS
# FILE while it is still executing. Re-exec the freshly pulled version once so
# what runs is what was just committed.
if [ "$before_root" != "$after_root" ] && [ -z "${DEPLOY_REEXECED:-}" ]; then
    step "Script updated by the pull — restarting with the new version"
    export DEPLOY_REEXECED=1
    exec bash "$ROOT/deploy.sh" "$@"
fi

# ── 3. Per-app deploy ────────────────────────────────────────────────
for app in "${APPS[@]}"; do
    name="$([ "$app" = "$ROOT" ] && echo 'main app' || echo 'customer app')"
    cd "$app"

    step "[$name] Installing dependencies"
    php -d memory_limit=-1 "$(which composer)" install \
        --no-dev --optimize-autoloader --no-interaction

    step "[$name] Running migrations"
    php artisan migrate --force

    step "[$name] Ensuring storage symlink"
    link="$app/public/storage"
    target="$app/storage/app/public"
    if [ -L "$link" ]; then
        if [ "$(readlink "$link")" != "$target" ]; then
            rm "$link" && ln -s "$target" "$link"
            echo "    symlink repointed at $target"
        else
            echo "    symlink already present"
        fi
    elif [ -d "$link" ]; then
        printf '\n!! %s/public/storage is a real directory, not a symlink.\n' "$app"
        printf '   Move anything inside it to %s, delete the directory, then re-run.\n\n' "$target"
        exit 1
    else
        mkdir -p "$target"
        ln -s "$target" "$link"
        echo "    symlink created"
    fi

    step "[$name] Rebuilding caches"
    php artisan config:clear
    php artisan route:clear
    php artisan view:clear
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
done

# ── 4. Back online ───────────────────────────────────────────────────
trap - ERR
step "Bringing both apps back online"
for app in "${APPS[@]}"; do
    (cd "$app" && php artisan up)
done

printf '\n✅ HiFastLink deployed — main app and customer app.\n'

if [ -n "${DEPLOY_ASSETS_WARNING:-}" ]; then
    cat <<'WARN'

!!  Markup changed in this pull but public/build did not.
    public/build is committed to git and is never rebuilt on the server.
    Any Tailwind class used for the first time has no CSS behind it —
    the page will look broken in a way nothing reports.

    On your machine:  npm run build
    then commit public/build and push again.

WARN
fi
