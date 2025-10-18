#!/usr/bin/env bash

# Simple snapshot generator for AlleyNote.
# Produces a machine-friendly overview that helps AI agents grasp project state quickly.

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

timestamp_utc="$(date -u +"%Y-%m-%dT%H:%M:%SZ")"
git_summary="$(git status -sb 2>/dev/null | head -n 1 || echo "(git data unavailable)")"
latest_commit="$(git log -1 --format='%h %s (%cr)' 2>/dev/null || echo "(commit data unavailable)")"

print_section() {
    printf '\n## %s\n' "$1"
}

printf '=== PROJECT SNAPSHOT ===\n'
printf 'timestamp: %s\n' "$timestamp_utc"
printf 'root: %s\n' "$ROOT_DIR"
printf 'git: %s\n' "$git_summary"
printf 'latest-commit: %s\n' "$latest_commit"

print_section "Top-Level Directories"
find . -maxdepth 1 -mindepth 1 -type d \
    -not -path './.git' \
    -not -path './vendor' \
    -print | sort | sed 's|^./|- |'

print_section "File Counts"
php_files=$(find backend -type f -name '*.php' 2>/dev/null | wc -l | tr -d ' ')
js_files=$(find frontend -type f \( -name '*.js' -o -name '*.ts' -o -name '*.tsx' \) 2>/dev/null | wc -l | tr -d ' ')

test_roots=()
if [ -d tests ]; then
    test_roots+=(tests)
fi
if [ -d backend/tests ]; then
    test_roots+=(backend/tests)
fi
if [ ${#test_roots[@]} -gt 0 ]; then
    test_files=$(find "${test_roots[@]}" -type f -name '*Test.php' 2>/dev/null | wc -l | tr -d ' ')
else
    test_files=0
fi
printf -- '- php: %s\n' "$php_files"
printf -- '- js/ts: %s\n' "$js_files"
printf -- '- php-tests: %s\n' "$test_files"

print_section "Backend Modules"
if [ -d backend/app ]; then
    find backend/app -maxdepth 2 -mindepth 2 -type d \
        | sort \
        | sed 's|^|- |'
else
    printf -- '- (backend/app missing)\n'
fi

print_section "Pending Changes"
git status --short 2>/dev/null | sed 's|^|- |' || printf -- '- (git status unavailable)\n'

print_section "Composer Packages"
if [ -f backend/composer.json ]; then
    python3 - <<'PY'
import json, pathlib
composer_path = pathlib.Path('backend/composer.json')
data = json.loads(composer_path.read_text())
name = data.get('name', 'unknown')
req = data.get('require', {})
dev = data.get('require-dev', {})
print(f"- package: {name}")
if req:
    for pkg, version in sorted(req.items()):
        print(f"  - require: {pkg} {version}")
if dev:
    for pkg, version in sorted(dev.items()):
        print(f"  - require-dev: {pkg} {version}")
PY
else
    printf -- '- (backend/composer.json not found)\n'
fi

print_section "Recent PHPStan Results"
phpstan_cache="backend/storage/phpstan" # phpstan tmpDir configured in phpstan.neon
if [ -d "$phpstan_cache" ]; then
    find "$phpstan_cache" -maxdepth 1 -type f -print | sort | sed 's|^|- |'
else
    printf -- '- (phpstan cache directory not found)\n'
fi

print_section "Notes"
printf -- '- regenerate snapshot after major refactors or before tackling PHPStan fixes\n'
