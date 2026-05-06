#!/usr/bin/env zsh
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "$0")/../.." && pwd)"

SCHEMA="$ROOT_DIR/schema/openapi.yaml"

#
# WordPress production environments often don't have Node.js.
# Therefore, we generate PHP DTOs in dev/CI and ship them in the Composer package.
#
OUT_DIR="$ROOT_DIR/src/Contracts/DTO/Generated"
TMP_DIR="$ROOT_DIR/tmp/php-gen"

echo "==> [php] Cleaning"
rm -rf "$TMP_DIR" "$OUT_DIR"
mkdir -p "$TMP_DIR" "$OUT_DIR"

echo "==> [php] Generating via OpenAPI Generator"

npx @openapitools/openapi-generator-cli generate \
  -i "$SCHEMA" \
  -g php \
  -o "$TMP_DIR" \
  --additional-properties='invokerPackage=S2J\\Similarity,modelPackage=Contracts\\DTO\\Generated'

echo "==> [php] Extracting DTO only"

# DTO 配下のみコピー（契約として扱う）
#
# Note: openapi-generator (php) places models under:
#   lib/<modelPackageAsPath>/
# With our settings:
#   invokerPackage = S2J\Similarity
#   modelPackage   = Contracts\DTO\Generated
# so the generated DTOs live under:
#   lib/ContractsDTOGenerated/

cp -r "$TMP_DIR/lib/Contracts/DTO/Generated/"*.php "$OUT_DIR/"

echo "==> [php] Cleanup"
rm -rf "$TMP_DIR"

echo "==> [php] Done"
