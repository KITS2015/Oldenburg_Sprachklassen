#!/usr/bin/env bash
set -euo pipefail
IFS=$'\n\t'

# -----------------------------------------------------------------------------
# deploy.sh — One-way deployment: GitHub -> Server
# - Discards ALL local changes (tracked + untracked) except excluded paths
# - No commit, no push, no rebase
# -----------------------------------------------------------------------------

# Repo-Verzeichnis automatisch: Ordner, in dem dieses Script liegt (Repo-Root)
REPO_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

REMOTE="origin"
BRANCH="main"

LOG_FILE="${REPO_DIR}/logs/git_deploy.log"

# Untracked/ignored Pfade, die beim "git clean" NICHT gelöscht werden sollen
# (typisch: uploads, logs, env/config Dateien)
EXCLUDES=(
  "uploads"
  "logs"
  ".env"
  "config.json"
  "config.local.php"
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
  # Zusätzliche Sicherheit: Push-URL deaktivieren, damit auf dem Server kein Push möglich ist.
  # (Fetch-URL bleibt unverändert.)
  run git remote set-url --push "$REMOTE" "DISABLED"
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

  log "DEPLOY abgeschlossen. Aktueller Stand:"
  run git --no-pager log -1 --oneline --decorate
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
    *)
      bail "Unbekannter Befehl: $CMD (nutze 'help')"
      ;;
  esac
}

main "$@"
