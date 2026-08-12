# S2J Similarity Service - CHANGELOG

## unreleased

## 2.0.4 - 2026-08-12

### Changed

* 仕様ドキュメント (`docs/**`) の Markdown 表記を整備 (テーブル区切りの統一、リスト表記の整理、英語の設計原則ブロックの日本語化)
* 文言の微修正 (`drift` → `逸脱`、重複表現の解消、`docs_old` の誤字修正など)

## 2.0.3 - 2026-08-11

### Changed

* `@s2j/similarity-client` で TypeScript v7 (`tsc` via `@typescript/native`) と `@typescript/typescript6` を公式 side-by-side 構成で併用 (`typescript-eslint` 等の API 互換用)
* `@s2j/similarity-client` の `vitest` を ^4.1.10に更新
* ルート `allowScripts` をパッケージ名指定に整理し、`@openapitools/openapi-generator-cli` を追加 (`esbuild` / `@s2j/docs-linter` のバージョン固定を解除)

## 2.0.2 - 2026-08-08

### Added

* npm v12以降で `esbuild` と `@s2j/docs-linter` の postinstall が実行できるよう、ルート `package.json` に `allowScripts` を追加

### Changed

* 開発用 npm 依存を更新 (`@openapitools/openapi-generator-cli` ^2.40.1、`@s2j/docs-linter` ^1.0.22)
* 推移的依存 `js-yaml` の脆弱性 (CVE-2026-59870) を `npm audit fix` で解消

## 2.0.1 - 2026-07-23

### Added

* npm v12以降で `@s2j/docs-linter` の推移的 Git 依存を取得できるよう、ルート `.npmrc` に `allow-git=all` を追加 (`EALLOWGIT` 回避)

### Changed

* 開発用 npm 依存を更新 (`@openapitools/openapi-generator-cli` ^2.40.0、`@s2j/docs-linter` ^1.0.21、`openapi-typescript-codegen` ^0.31.0)

## 2.0.0 - 2026-06-11

### Breaking Changes

* ライセンスを GPL-2.0-or-later から GPL-3.0-or-later に変更 (`LICENSE`、`package.json`、`composer.json`、OpenAPI 定義、README 等)

### Added

* TypeScript SDK `@s2j/similarity-client` (ApiClient、HttpClient、Timeout、Retry、エラー union)
* OpenAPI を Single Source of Truth とした codegen パイプライン (TypeScript types、Zod、PHP DTO)
* OpenAPI レスポンスの JSON Schema 機械検証を CI に追加
* Markdown 品質ゲート (S2J Docs Linter) と `docs-lint.yml` ワークフロー
* Composer 配布 tarball 生成 (`composer run dist:php` → `artifacts/s2j-similarity-service-{version}.tar.gz`)

### Changed

* CI 品質ゲートの段階的拡張 (PHPCS を生成 DTO へ適用等)
* S2J Docs Linter の運用を npm モジュール経由に切り替え
* 開発用 npm 依存 (`@openapitools/openapi-generator-cli`、`@s2j/docs-linter` 等) を更新
* README、使用方法ドキュメントの命名、例コードを整合
* `.gitattributes` の `export-ignore` と `composer.json` の `archive.exclude` で PHP 配布物から開発専用ファイルを除外
* `composer.json` に `version` を追加 (Packagist、artifact リポジトリ向け)

## 1.0.1

* 初回リリース相当の安定版
