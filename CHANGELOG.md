# S2J Similarity Service - CHANGELOG

## unreleased

## 2.0.0 - 2026-06-11

### Breaking Changes

* ライセンスを GPL-2.0-or-later から GPL-3.0-or-later に変更 (`LICENSE`、`package.json`、`composer.json`、OpenAPI 定義、README 等)

### Added

* TypeScript SDK `@s2j/similarity-client` (ApiClient、HttpClient、Timeout、Retry、エラー union)
* OpenAPI を Single Source of Truth とした codegen パイプライン (TypeScript types、Zod、PHP DTO)
* OpenAPI レスポンスの JSON Schema 機械検証を CI に追加
* Markdown 品質ゲート (S2J Docs Linter) と `docs-lint.yml` ワークフロー

### Changed

* CI 品質ゲートの段階的拡張 (PHPCS を生成 DTO へ適用等)
* S2J Docs Linter の運用を npm モジュール経由に切り替え
* 開発用 npm 依存 (`@openapitools/openapi-generator-cli`、`@s2j/docs-linter` 等) を更新
* README、使用方法ドキュメントの命名、例コードを整合

## 1.0.1

* 初回リリース相当の安定版
