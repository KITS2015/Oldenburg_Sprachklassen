#!/usr/bin/env bash
set -euo pipefail
IFS=$'\n\t'

# ── Konfig
REPO_DIR="/var/www/oldenburg.anmeldung.schule"
REMOTE="origin"
BRANCH="main"
LOG_FILE="$REPO_DIR/logs/git_deploy_oldenburg.log"
WIP_MSG="server: WIP $(date '+%Y-%m-%d %H:%M:%S')"

mkdir -p "$(dirname "$LOG_FILE")"

# ── Helpers
log()  { mkdir -p "$(dirname "$LOG_FILE")"; echo "[$(date '+%Y-%m-%d %H:%M:%S')] $*" | tee -a "$LOG_FILE"; }
bail() { log "FEHLER: $*"; exit 1; }
run()  { local s; s=$(printf '%q ' "$@"); log "+ ${s% }"; "$@" >>"$LOG_FILE" 2>&1; }

require_cmd(){ command -v "$1" >/dev/null 2>&1 || bail "Benötigt: $1"; }
ensure_repo(){ [[ -d "$REPO_DIR/.git" ]] || bail "Kein Git-Repo in $REPO_DIR"; cd "$REPO_DIR"; }
ensure_remote(){ git remote get-url "$REMOTE" >/dev/null 2>&1 || bail "Remote '$REMOTE' fehlt"; }

has_uncommitted(){
  ! git diff --quiet || ! git diff --cached --quiet && return 0 || return 1
}

ahead_behind(){ # echoes "BEHIND AHEAD"
  git rev-parse --verify "$REMOTE/$BRANCH" >/dev/null 2>&1 || { echo "0 0"; return; }
  git rev-list --left-right --count "$REMOTE/$BRANCH"...HEAD | awk '{print $1" "$2}'
}

autocommit_if_needed(){
  if ! git diff --quiet || ! git diff --cached --quiet; then
    log "Uncommitted Änderungen gefunden → Auto-Commit: $WIP_MSG"
    run git add -A
    run git commit -m "$WIP_MSG" || true
  fi
}

usage(){
  cat <<'USAGE'
Verwendung:
  deploy.sh sync        # (Default) bidirektional: commit/push/pull je nach Status
  deploy.sh pull        # nur vom Remote holen
  deploy.sh push [msg]  # nur lokale Änderungen pushen
  deploy.sh status      # Status & letzte Commits anzeigen
  deploy.sh help        # Hilfe
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

    autocommit_if_needed

    read BEHIND AHEAD < <(ahead_behind)
    log "Status: behind=$BEHIND ahead=$AHEAD"

    if [[ "$AHEAD" -gt 0 && "$BEHIND" -eq 0 ]]; then
      log "Nur lokal neue Commits → push"
      run git push "$REMOTE" "$BRANCH"
      log "SYNC abgeschlossen (push)."
      exit 0
    fi

    if [[ "$BEHIND" -gt 0 && "$AHEAD" -eq 0 ]]; then
      log "Nur remote neue Commits → pull (ff-only)"
      run git pull --ff-only "$REMOTE" "$BRANCH"
      log "SYNC abgeschlossen (pull)."
      exit 0
    fi

    if [[ "$BEHIND" -gt 0 && "$AHEAD" -gt 0 ]]; then
      log "Sowohl lokal als auch remote neue Commits → rebase"
      set +e
      git pull --rebase "$REMOTE" "$BRANCH" >>"$LOG_FILE" 2>&1
      rc=$?
      set -e
      if [[ $rc -ne 0 ]]; then
        bail "Rebase-Konflikte. Bitte Konflikte lösen, dann: git rebase --continue (oder --abort)."
      fi
      log "SYNC abgeschlossen (rebase + push)"
      run git push "$REMOTE" "$BRANCH"
      exit 0
    fi

    log "Keine Änderungen – already up-to-date."
    ;;

  pull)
    log "PULL gestartet"
    run git fetch --prune "$REMOTE"
    run git pull --ff-only "$REMOTE" "$BRANCH" || { log "Kein Fast-Forward möglich → nutze rebase"; run git pull --rebase "$REMOTE" "$BRANCH"; }
    log "PULL abgeschlossen."
    ;;

  push)
    log "PUSH gestartet"
    run git add -A
    run git commit -m "${2:-server: update $(date '+%Y-%m-%d %H:%M:%S')}" || log "Nichts zu committen."
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
