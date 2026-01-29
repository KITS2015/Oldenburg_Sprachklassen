#!/usr/bin/env bash
set -euo pipefail
IFS=$'\n\t'

# -----------------------------------------------------------------------------
# deploy.sh — One-way deployment: GitHub -> Server
# - Discards ALL local changes (tracked + untracked) except excluded paths
# - Runs composer install (vendor NOT in repo)
# - Copies Bootstrap dist from vendor -> public/assets/bootstrap (local, DSGVO-safe)
# -----------------------------------------------------------------------------

REPO_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

REMOTE="origin"
BRANCH="main"

APP_USER="user"   # <- ggf. anpassen
LOG_FILE="${REPO_DIR}/logs/git_deploy.log"

EXCLUDES=(
  "uploads"
  "logs"
  ".env"
  "config.json"
  "config.local.php"
  "app/config.php"
  "app/mail.php"
)

log() {
  mkdir -p "$(dirname "$LOG_FILE")"
  echo "[$(date '+%Y-%m-%d %H:%M:%S')] $*" | tee -a "$LOG_FILE"
}

bail() {
  log "FEHLER: $*"
  exit 1
}

run() {
  local s
  s=$(printf '%q ' "$@")
  log "+ ${s% }"
  "$@" >>"$LOG_FILE" 2>&1
}

require_cmd() {
  command -v "$1" >/dev/null 2>&1 || bail "Benötigt: $1"
}

ensure_repo() {
  [[ -d "$REPO_DIR/.git" ]] || bail "Kein Git-Repo in $REPO_DIR"
  cd "$REPO_DIR"
}

ensure_remote() {
  git remote get-url "$REMOTE" >/dev/null 2>&1 || bail "Remote '$REMOTE' fehlt"
}

disable_push_url() {
  # Zusätzliche Sicherheit: Push-URL deaktivieren (Fetch-URL bleibt)
  run git remote set-url --push "$REMOTE" "DISABLED"
}

run_as_app_user() {
  # Führt Kommandos als APP_USER aus (wenn wir nicht sowieso schon APP_USER sind)
  if [[ "$(id -un)" == "$APP_USER" ]]; then
    run "$@"
  else
    require_cmd sudo
    run sudo -u "$APP_USER" "$@"
  fi
}

usage() {
  cat <<'USAGE'
Verwendung:
  deploy.sh              # Standard: Deploy (GitHub -> Server, lokal verwerfen)
  deploy.sh deploy        # wie oben
  deploy.sh status        # Status/Revision anzeigen
  deploy.sh help          # Hilfe

Hinweis:
  Dieses Script ist absichtlich EINWEG (GitHub -> Server).
  Lokale Änderungen auf dem Server werden verworfen.
USAGE
}

composer_install() {
  if [[ -f "$REPO_DIR/composer.json" ]]; then
    require_cmd composer
    log "Composer: install --no-dev --optimize-autoloader"
    run_as_app_user composer install --no-dev --optimize-autoloader --no-interaction
  else
    log "Composer: composer.json nicht gefunden – überspringe"
  fi
}

sync_bootstrap_assets() {
  local SRC="$REPO_DIR/vendor/twbs/bootstrap/dist"
  local DST="$REPO_DIR/public/assets/bootstrap"

  if [[ -d "$SRC" ]]; then
    log "Bootstrap: Sync dist -> public/assets/bootstrap (lokal, ohne CDN)"
    run rm -rf "$DST"
    run mkdir -p "$DST"
    # dist/* nach public/assets/bootstrap/
    run cp -a "$SRC/." "$DST/"
    # Rechte (optional, aber praktisch)
    run chown -R "$APP_USER:www-data" "$DST" || true
    run find "$DST" -type d -exec chmod 2755 {} \; || true
    run find "$DST" -type f -exec chmod 0644 {} \; || true
  else
    log "Bootstrap: Quelle nicht gefunden ($SRC) – überspringe"
  fi
}

cmd_deploy() {
  log "DEPLOY gestartet (Repo: $REPO_DIR, Remote: $REMOTE, Branch: $BRANCH)"

  run git fetch --prune "$REMOTE"

  # Sicherstellen, dass wir auf dem gewünschten Branch sind (lokal anlegen falls nötig)
  if git show-ref --verify --quiet "refs/heads/$BRANCH"; then
    run git checkout "$BRANCH"
  else
    run git checkout -b "$BRANCH" "$REMOTE/$BRANCH"
  fi

  # Push auf dem Server absichern: deaktivieren
  disable_push_url

  # Harte Synchronisation: tracked Dateien exakt wie Remote
  run git reset --hard "$REMOTE/$BRANCH"

  # Untracked Dateien/Ordner aufräumen, aber wichtige Runtime-Pfade behalten
  CLEAN_ARGS=(git clean -fd)
  for e in "${EXCLUDES[@]}"; do
    CLEAN_ARGS+=(-e "$e")
  done
  run "${CLEAN_ARGS[@]}"

  # Ab hier: Runtime/Build-Schritte
  composer_install
  sync_bootstrap_assets

  log "DEPLOY abgeschlossen. Aktueller Stand:"
  run git --no-pager log -1 --oneline --decorate

  # Hinweis: Wenn public/assets/bootstrap im Repo getrackt ist, ist das Working Tree danach "dirty".
  # Das ist technisch ok, aber siehe README/Empfehlung.
  if ! git diff --quiet; then
    log "HINWEIS: Working Tree hat lokale Änderungen (vermutlich public/assets/bootstrap)."
  fi
}

cmd_status() {
  log "STATUS (Repo: $REPO_DIR)"
  run git remote -v
  run git status
  run git --no-pager log --oneline -n 10 --graph --decorate
}

main() {
  require_cmd git
  require_cmd tee

  ensure_repo
  ensure_remote

  local CMD="${1:-deploy}"
  case "$CMD" in
    deploy) cmd_deploy ;;
    status) cmd_status ;;
    help|-h|--help) usage ;;
    *) bail "Unbekannter Befehl: $CMD (nutze 'help')" ;;
  esac
}

main "$@"
