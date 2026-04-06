# S2J Similarity Service - 仕様書の起点

本プロジェクトの仕様は、以下のドキュメントに分散して定義しています。
各ドキュメントへの導線のみを提供し、詳細な仕様は個別ファイルに委譲します。

## 読み方ガイド

目的に応じて、以下の順序で参照してください。

### 全体像を把握する

1. `overview.md`
2. `concept.md`
3. `architecture.md`

### 利用者 (API / ライブラリとして利用)

1. `concept.md`
2. `contracts/data_contract_spec.md`
3. `interfaces/usage_spec.md`
4. `interfaces/rest_api_spec.md` (必要に応じて)

### 実装者 (内部ロジック理解)

1. `concept.md`
2. `core/similarity_spec.md`
3. `contracts/embedding_api_spec.md`
4. `contracts/data_dictionary.md`

## ドキュメント一覧

| ドキュメント | 内容 |
|--------------|------|
| [基本情報 - 概要](./overview.md) | プロジェクトの概要、前提環境、提供価値 |
| [基本情報 - コンセプト](./concept.md) | 問題定義、ユースケース、適用範囲 |
| [基本情報 - アーキテクチャー](./architecture.md) | 構造、責務分離、設計原則 |
| [コア - 類似度算出の仕様](./core/similarity_spec.md) | 類似度算出ロジック |
| [契約 - 入出力仕様](./contracts/data_contract_spec.md) | 入出力仕様 |
| [契約 - データ辞書](./contracts/data_dictionary.md) | 型、データ定義 |
| [契約 - 外部 API 仕様](./contracts/embedding_api_spec.md) | 外部 API 仕様 |
| [契約 - OpenAPI 契約統合仕様](./contracts/openapi_spec.md) | API 契約の統合 (OpenAPI 形式) |
| [インターフェイス - 使用方法](./interfaces/usage_spec.md) | 使用方法 (PHP、JS) |
| [インターフェイス - REST API 仕様](./interfaces/rest_api_spec.md) | REST API 仕様 |
