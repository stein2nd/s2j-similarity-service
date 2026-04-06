#!/usr/bin/env zsh
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "$0")/../.." && pwd)"

SCHEMA="$ROOT_DIR/schema/openapi.yaml"
OUT_DIR="$ROOT_DIR/packages/ts-client/generated"

MODELS_DIR="$OUT_DIR/models"
SCHEMAS_DIR="$OUT_DIR/schemas"
API_DIR="$OUT_DIR/api"

echo "==> [ts] Cleaning output directory"
rm -rf "$OUT_DIR"
mkdir -p "$MODELS_DIR" "$SCHEMAS_DIR" "$API_DIR"

echo "==> [ts] Generating TypeScript types"
npx openapi-typescript "$SCHEMA" 
--output "$MODELS_DIR/types.ts"

echo "==> [ts] Generating Zod schemas"
npx openapi-zod-client "$SCHEMA" 
-o "$SCHEMAS_DIR/schema.ts"

echo "==> [ts] Generating raw API client (fetch)"
npx openapi-typescript-codegen 
--input "$SCHEMA" 
--output "$API_DIR" 
--client fetch

echo "==> [ts] Done"
