#!/usr/bin/env bash
set -euo pipefail
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"
PHP_BIN="${PHP_BIN:-php}"
COMPOSER_BIN="${COMPOSER_BIN:-composer}"

echo '[1/5] Checking tracked sensitive files'
for path in .env database/database.sqlite whatsapp-connector/.env whatsapp-connector/session; do
  if git ls-files --error-unmatch "$path" >/dev/null 2>&1; then echo "Sensitive path is tracked: $path" >&2; exit 1; fi
done

echo '[2/5] Checking common credential patterns'
if git grep -nE '(github_pat_[A-Za-z0-9_]{20,}|ghp_[A-Za-z0-9]{20,}|-----BEGIN (RSA |EC |OPENSSH )?PRIVATE KEY-----)' -- ':!*.md' ':!scripts/security-check.sh' ':!.github/workflows/ci.yml'; then
  echo 'Potential committed credential detected.' >&2; exit 1
fi

echo '[3/5] PHP formatting and tests'
"$PHP_BIN" vendor/bin/pint --test
"$PHP_BIN" artisan test

echo '[4/5] Dependency advisory audit'
"$COMPOSER_BIN" audit --locked --no-interaction

echo '[5/5] WhatsApp connector syntax'
node --check whatsapp-connector/src/server.js

echo 'Security and quality checks passed.'
