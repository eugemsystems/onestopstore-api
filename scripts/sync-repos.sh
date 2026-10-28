#!/usr/bin/env bash
#
# Two-way sync of commits between the two copies of this codebase:
#
#   A = raines/laravel-api                    (github.com/eugemsystems/raines-laravel-api)
#   B = onestopstore/onestopstore-api         (github.com/eugemsystems/onestopstore-api)
#
# Every commit that exists on main in one repo but not in the other (and is newer than
# SYNC_SINCE) is applied to the other repo — oldest first, original message/author kept —
# and then pushed straight to main. Run it from either repo:
#
#   bash scripts/sync-repos.sh              # sync both ways and push main
#   bash scripts/sync-repos.sh --dry-run    # only list what would be synced
#   bash scripts/sync-repos.sh --no-push    # apply locally, don't push
#
# How "already synced" is decided: a commit is matched in the other repo by its author
# timestamp + subject (git am keeps both), so nothing is ever applied twice and a commit
# that was synced A->B is never bounced back B->A.
#
# If a commit does not apply cleanly (the repos' code differs there) the script aborts that
# commit with `git am --abort`, leaves the repo clean, pushes nothing for that direction and
# tells you which commit/files conflict so you can port it by hand.
#
# Env overrides: SYNC_REPO_A, SYNC_REPO_B, SYNC_SINCE, SYNC_REMOTE, SYNC_BRANCH
#
set -u

A="${SYNC_REPO_A:-/c/Users/eugem/Documents/GithubRainesProjects/raines/laravel-api}"
B="${SYNC_REPO_B:-/c/Users/eugem/Documents/GithubRainesProjects/onestopstore/onestopstore-api}"
SINCE="${SYNC_SINCE:-2026-10-16 00:00:00}"   # commits older than this are never synced (history already diverged)
REMOTE="${SYNC_REMOTE:-origin}"
BRANCH="${SYNC_BRANCH:-main}"

# Files that must never travel between the two repos (environment / machine specific).
PATHSPEC=(. ':(exclude).env' ':(exclude).env.*' ':(exclude)conf' ':(exclude).claude'
          ':(exclude)public/dailyfeeds' ':(exclude)storage' ':(exclude)docker-compose*.yml')

DRY=0; PUSH=1
for arg in "$@"; do
  case "$arg" in
    --dry-run) DRY=1 ;;
    --no-push) PUSH=0 ;;
    -h|--help) sed -n '2,25p' "$0"; exit 0 ;;
    *) echo "Unknown option: $arg"; exit 2 ;;
  esac
done

FAILED=0
say()  { printf '%s\n' "$*"; }
warn() { printf '!! %s\n' "$*" >&2; }

name_of() { basename "$1"; }

# "<author-ts>|<subject>" for every non-merge commit on BRANCH since SINCE
keys_of() { git -C "$1" log "$BRANCH" --no-merges --since="$SINCE" --format='%at|%s'; }

prepare_repo() {   # make sure repo is on BRANCH and fast-forwarded to the remote
  local repo="$1" n; n="$(name_of "$repo")"
  [ -d "$repo/.git" ] || { warn "$n: not a git repo ($repo)"; return 1; }
  local cur; cur="$(git -C "$repo" branch --show-current)"
  if [ "$cur" != "$BRANCH" ]; then
    git -C "$repo" checkout -q "$BRANCH" 2>/dev/null || { warn "$n: cannot switch to $BRANCH (on '$cur'; commit or stash first)"; return 1; }
  fi
  git -C "$repo" fetch -q "$REMOTE" 2>/dev/null || warn "$n: fetch failed (offline?) — continuing with local state"
  if git -C "$repo" rev-parse -q --verify "$REMOTE/$BRANCH" >/dev/null; then
    if ! git -C "$repo" merge -q --ff-only "$REMOTE/$BRANCH" 2>/dev/null; then
      local behind; behind="$(git -C "$repo" rev-list --count "$BRANCH..$REMOTE/$BRANCH")"
      if [ "$behind" != "0" ]; then warn "$n: local $BRANCH has diverged from $REMOTE/$BRANCH — resolve that first"; return 1; fi
    fi
  fi
  return 0
}

sync_one_way() {   # sync_one_way SRC DST
  local src="$1" dst="$2" sn dn; sn="$(name_of "$src")"; dn="$(name_of "$dst")"
  say ""; say "== $sn  ->  $dn"

  local dst_keys; dst_keys="$(keys_of "$dst")"
  local applied=0 skipped=0 ok=1 sha ts subject key

  while IFS='|' read -r sha ts subject; do
    [ -z "$sha" ] && continue
    key="$ts|$subject"
    if printf '%s\n' "$dst_keys" | grep -qxF -- "$key"; then continue; fi

    # Commit that only touches excluded files (.env, conf/, …) -> nothing to sync
    if [ -z "$(git -C "$src" diff-tree --no-commit-id --name-only -r "$sha" -- "${PATHSPEC[@]}")" ]; then
      skipped=$((skipped+1)); continue
    fi

    if [ "$DRY" = "1" ]; then say "  would apply ${sha:0:7} $subject"; applied=$((applied+1)); continue; fi

    if git -C "$src" format-patch -1 "$sha" --stdout -- "${PATHSPEC[@]}" \
         | git -C "$dst" am -3 --whitespace=nowarn >/dev/null 2>&1; then
      say "  applied ${sha:0:7} $subject"; applied=$((applied+1))
    else
      local files; files="$(git -C "$dst" diff --name-only --diff-filter=U 2>/dev/null | tr '\n' ' ')"
      git -C "$dst" am --abort >/dev/null 2>&1
      warn "CONFLICT applying ${sha:0:7} \"$subject\" to $dn  (files: ${files:-unknown})"
      warn "Port this commit by hand; later commits were not applied."
      ok=0; FAILED=1; break
    fi
  done < <(git -C "$src" log "$BRANCH" --no-merges --reverse --since="$SINCE" --format='%H|%at|%s')

  [ "$skipped" -gt 0 ] && say "  ($skipped commit(s) only touch excluded files — ignored)"
  [ "$applied" -eq 0 ] && [ "$ok" = "1" ] && say "  already in sync"

  # Push whenever the destination is ahead of its remote and nothing failed
  if [ "$DRY" = "0" ] && [ "$PUSH" = "1" ] && [ "$ok" = "1" ]; then
    local ahead; ahead="$(git -C "$dst" rev-list --count "$REMOTE/$BRANCH..$BRANCH" 2>/dev/null || echo 0)"
    if [ "${ahead:-0}" -gt 0 ]; then
      if git -C "$dst" push "$REMOTE" "$BRANCH" 2>&1 | sed 's/^/  /'; then say "  pushed $ahead commit(s) to $dn $REMOTE/$BRANCH"; else warn "push failed for $dn"; FAILED=1; fi
    fi
  fi
}

prepare_repo "$A" || exit 1
prepare_repo "$B" || exit 1

sync_one_way "$A" "$B"
sync_one_way "$B" "$A"

say ""
if [ "$FAILED" = "0" ]; then say "Sync finished OK."; else warn "Sync finished with problems (see above)."; fi
exit "$FAILED"
