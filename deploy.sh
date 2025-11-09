#!/usr/bin/env bash
set -euo pipefail
IFS=$'\n\t'

# ── Konfiguration
REPO_DIR="/var/www/oldenburg.anmeldung.schule"
REMOTE="origin"
BRANCH="main"
LOG_FILE="$REPO_DIR/logs/git_deploy_oldenburg.log"

mkdir -p "$(dirname "$LOG_FILE")"

# ── Hilfsfunktionen
log()  { mkdir -p "$(dirname "$LOG_FILE")"; echo "[$(date '+%Y-%m-%d %H:%M:%S')] $*" | tee -a "$LOG_FILE"; }
bail() { log "FEHLER: $*"; exit 1; }
run()  { local s; s=$(printf '%q ' "$@"); log "+ ${s% }"; "$@" >>"$LOG_FILE" 2>&1; }

require_cmd()  { command -v "$1" >/dev/null 2>&1 || bail "Benötigt: $1"; }
ensure_repo()  { [[ -d "$REPO_DIR/.git" ]] || bail "Kein Git-Repo in $REPO_DIR"; cd "$REPO_DIR"; }
ensure_remote(){ git remote get-url "$REMOTE" >/dev/null 2>&1 || bail "Remote '$REMOTE' fehlt"; }

usage() {
  cat <<'USAGE'
Verwendung:
  deploy.sh              # = deploy.sh sync
  deploy.sh sync         # Bidirektional: commit/push/pull je nach Status
  deploy.sh pull         # Nur vom Remote holen (ff-only, bei Bedarf rebase)
  deploy.sh push [msg]   # Nur lokale Änderungen pushen
  deploy.sh status       # Status & letzte Commits anzeigen
  deploy.sh help         # Hilfe
USAGE
}

require_cmd git
require_cmd tee
ensure_repo
ensure_remote

CMD="${1:-sync}"

case "$CMD" in
  sync)
    log "SYNC gestartet (Repo: $REPO_DIR, $REMOTE/$BRANCH)"
    run git fetch --prune "$REMOTE"

    # Immer zuerst alles zum Index hinzufügen (fasst auch untracked an)
    run git add -A
    if git diff --cached --quiet; then
      log "Keine lokalen Änderungen zum Commit."
    else
      run git commit -m "server: WIP $(date '+%Y-%m-%d %H:%M:%S')" || true
    fi

    BEHIND=$(git rev-list --count HEAD.."$REMOTE/$BRANCH" 2>/dev/null || echo 0)
    AHEAD=$(git rev-list --count "$REMOTE/$BRANCH"..HEAD 2>/dev/null || echo 0)
    log "Status: behind=$BEHIND ahead=$AHEAD"

    if [[ "$AHEAD" -gt 0 && "$BEHIND" -eq 0 ]]; then
      log "Nur lokal neue Commits → push"
      run git push "$REMOTE" "$BRANCH"
      log "SYNC abgeschlossen (push)."; exit 0
    fi

    if [[ "$BEHIND" -gt 0 && "$AHEAD" -eq 0 ]]; then
      log "Nur remote neue Commits → pull (ff-only)"
      run git pull --ff-only "$REMOTE" "$BRANCH" || { log "Kein FF → rebase"; run git pull --rebase "$REMOTE" "$BRANCH"; }
      log "SYNC abgeschlossen (pull)."; exit 0
    fi

    if [[ "$BEHIND" -gt 0 && "$AHEAD" -gt 0 ]]; then
      log "Sowohl lokal als auch remote neue Commits → rebase"
      set +e; git pull --rebase "$REMOTE" "$BRANCH" >>"$LOG_FILE" 2>&1; rc=$?; set -e
      if [[ $rc -ne 0 ]]; then bail "Rebase-Konflikte. Bitte lösen, dann rebase fortsetzen."; fi
      log "Rebase ok → push"; run git push "$REMOTE" "$BRANCH"
      log "SYNC abgeschlossen (rebase + push)."; exit 0
    fi

    log "Keine Änderungen – already up-to-date."
    ;;

  pull)
    log "PULL gestartet"
    run git fetch --prune "$REMOTE"
    run git pull --ff-only "$REMOTE" "$BRANCH" || { log "Kein FF → rebase"; run git pull --rebase "$REMOTE" "$BRANCH"; }
    log "PULL abgeschlossen."
    ;;

  push)
    log "PUSH gestartet"
    run git add -A
    if git diff --cached --quiet; then
      log "Nichts zu committen."
    else
      run git commit -m "${2:-server: update $(date '+%Y-%m-%d %H:%M:%S')}" || true
    fi
    run git push "$REMOTE" "$BRANCH"
    log "PUSH abgeschlossen."
    ;;

  status)
    log "STATUS"
    run git remote -v
    run git status
    run git log --oneline -n 10 --graph --decorate
    ;;

  help|-h|--help)
    usage
    ;;

  *)
    bail "Unbekannter Befehl: $CMD"
    ;;
esac
