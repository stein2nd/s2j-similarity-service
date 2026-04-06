#!/usr/bin/env zsh
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "$0")/../.." && pwd)"

echo "==> [all] Start generation"

echo "==> [all] TypeScript"
"$ROOT_DIR/scripts/generate/ts.zsh"

echo "==> [all] PHP"
"$ROOT_DIR/scripts/generate/php.zsh"

echo "==> [all] Done"
