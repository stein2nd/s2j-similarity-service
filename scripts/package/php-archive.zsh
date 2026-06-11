#!/usr/bin/env zsh
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "$0")/../.." && pwd)"
cd "$ROOT_DIR"

VERSION="$(php -r 'echo json_decode(file_get_contents("composer.json"))->version ?? "";')"
if [[ -z "$VERSION" ]]; then
  echo "error: composer.json に version が未設定です" >&2
  exit 1
fi

OUT_DIR="$ROOT_DIR/artifacts"
mkdir -p "$OUT_DIR"

BASENAME="s2j-similarity-service-${VERSION}"
OUT_FILE="${OUT_DIR}/${BASENAME}.tar.gz"

rm -f "$OUT_FILE"

composer archive --format=tar.gz --dir="$OUT_DIR" --file="$BASENAME" --no-scripts -n

echo "Created: $OUT_FILE"
ls -lh "$OUT_FILE"
