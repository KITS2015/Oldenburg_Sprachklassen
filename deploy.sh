#!/usr/bin/env bash

set -euo pipefail
IFS=$'\n\t'

### ───────────────────────────────
### Konfiguration
### ───────────────────────────────
REPO_DIR="/var/www/oldenburg.anmeldung.schule"
REMOTE="origin"
BRANCH="main"
LOG_FILE="/var/log/git_deploy_oldenburg.log"
WEB_USER="www-data"
WEB_GROUP="www-data"
PERM_DIRS=("." "assets" "css" "js")

### ───────────────────────────────
### Hilfsfunktionen
### ───────────────────────────────
log()   { echo "[$(date '+%Y-%m-%d %H:%M:%S')] $*" | tee -a "$LOG_FILE" ; }
bail()  { log "FEHLER: $*"; exit 1; }
run()   { log "+ $*"; "$@" >>"$LOG_FILE" 2>&1; }

require_cmd() {
  command -v "$1" >/dev/null 2>&1 || bail "Benötigtes Kommando nicht gefunden: $1"
}

ensure_repo() {
  [[ -d "$REPO_DIR/.git" ]] || bail "Kein Git-Repo in $REPO_DIR gefunden."
  cd "$REPO_DIR"
}

ensure_remote() {
  local url
  url="$(git remote get-url "$REMOTE" 2>/dev/null || true)"
  [[ -n "$url" ]] || bail "Remote '$REMOTE' ist nicht gesetzt. Bitte 'git remote add $REMOTE git@github.com:KITS2015/Oldenburg_Sprachklassen.git' ausführen."
}

ensure_clean_index_or_stash() {
  if ! git diff --quiet || ! git diff --cached --quiet; then
    log "Arbeitsverzeichnis hat Änderungen → stashe temporär."
    run git stash push -u -m "deploy.sh autostash $(date '+%Y-%m-%d %H:%M:%S')" || true
  fi
}

set_permissions() {
  log "Setze Dateibesitzer auf $WEB_USER:$WEB_GROUP"
  run chown -R "$WEB_USER:$WEB_GROUP" "$REPO_DIR"
  log "Setze Basisrechte (755 Verzeichnisse, 644 Dateien)"
  run find "$REPO_DIR" -type d -exec chmod 755 {} \;
  run find "$REPO_DIR" -type f -exec chmod 644 {} \;
  if [[ -f "$REPO_DIR/deploy.sh" ]]; then run chmod 755 "$REPO_DIR/deploy.sh"; fi
}

post_deploy() {
  # Projektbezogene Aktionen (z. B. Cache löschen)
  :
}

usage() {
  cat <<'USAGE'
Verwendung:
  deploy.sh pull        # Holt neuesten Stand von origin/main (empfohlen)
  deploy.sh push        # Commit & Push lokaler Änderungen nach origin/main
  deploy.sh status      # Zeigt Status & letzte Commits
  deploy.sh perms       # Setzt Besitzer- & Dateirechte neu
  deploy.sh help        # Diese Hilfe
USAGE
}

### ───────────────────────────────
### Vorbedingungen prüfen
### ───────────────────────────────
require_cmd git
require_cmd tee
touch "$LOG_FILE" || bail "Kann Logdatei nicht schreiben: $LOG_FILE (sudo ausführen?)"
ensure_repo
ensure_remote

### ───────────────────────────────
### Befehlsverarbeitung
### ───────────────────────────────
CMD="${1:-pull}"

case "$CMD" in
  pull)
    log "Starte DEPLOY (pull) in $REPO_DIR → $REMOTE/$BRANCH"
    ensure_clean_index_or_stash
    run git fetch --prune "$REMOTE"
    CURRENT_BRANCH="$(git rev-parse --abbrev-ref HEAD)"
    if [[ "$CURRENT_BRANCH" != "$BRANCH" ]]; then
      log "Wechsle Branch: $CURRENT_BRANCH → $BRANCH"
      run git checkout "$BRANCH"
    fi
    if git merge-base --is-ancestor "$CURRENT_BRANCH" "$REMOTE/$BRANCH"; then
      log "Lokaler Branch ist hinter Remote – führe Fast-Forward Pull aus."
      run git pull --ff-only "$REMOTE" "$BRANCH"
    else
      log "Nicht fast-forwardable – führe Rebase auf $REMOTE/$BRANCH aus."
      run git pull --rebase "$REMOTE" "$BRANCH"
    fi
    set_permissions
    post_deploy
    log "DEPLOY (pull) erfolgreich abgeschlossen."
    ;;

  push)
    log "Starte PUSH lokaler Änderungen nach $REMOTE/$BRANCH"
    run git add -A
    MSG="${2:-"server: update $(date '+%Y-%m-%d %H:%M:%S')"}"
    run git commit -m "$MSG" || log "Nichts zu committen."
    CURRENT_BRANCH="$(git rev-parse --abbrev-ref HEAD)"
    if [[ "$CURRENT_BRANCH" != "$BRANCH" ]]; then
      log "Wechsle Branch: $CURRENT_BRANCH → $BRANCH"
      run git checkout "$BRANCH"
    fi
    run git push "$REMOTE" "$BRANCH"
    log "PUSH erfolgreich."
    ;;

  status)
    log "Status für $REPO_DIR"
    run git remote -v
    run git status
    run git log --oneline -n 10 --graph --decorate
    ;;

  perms)
    log "Setze Berechtigungen neu…"
    set_permissions
    log "Berechtigungen aktualisiert."
    ;;

  help|-h|--help)
    usage
    ;;

  *)
    bail "Unbekannter Befehl: $CMD (nutze 'help')"
    ;;
esac
