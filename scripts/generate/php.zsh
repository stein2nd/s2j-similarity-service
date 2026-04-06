#!/usr/bin/env zsh
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "$0")/../.." && pwd)"

SCHEMA="$ROOT_DIR/schema/openapi.yaml"

OUT_DIR="$ROOT_DIR/packages/php/src/Contracts/DTO"
TMP_DIR="$ROOT_DIR/tmp/php-gen"

echo "==> [php] Cleaning"
rm -rf "$TMP_DIR" "$OUT_DIR"
mkdir -p "$TMP_DIR" "$OUT_DIR"

echo "==> [php] Generating via OpenAPI Generator"

npx @openapitools/openapi-generator-cli generate 
-i "$SCHEMA" 
-g php 
-o "$TMP_DIR" 
--additional-properties=invokerPackage=S2J\Similarity\Contracts\DTO

echo "==> [php] Extracting DTO only"

# Model 配下のみコピー（DTOとして扱う）

cp -r "$TMP_DIR/lib/Model/"* "$OUT_DIR/"

echo "==> [php] Cleanup"
rm -rf "$TMP_DIR"

echo "==> [php] Done"
