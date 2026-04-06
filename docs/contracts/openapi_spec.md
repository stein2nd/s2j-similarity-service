<!--
目的：「OpenAPI 契約統合仕様」の明文化
-->

# S2J Similarity Service - OpenAPI 契約統合仕様

本ドキュメントは、Contracts 層における API 契約を、OpenAPI 形式で統合するための方針を定義します。

## 設計意図 (ゴール)

* API 契約を、単一の Source of Truth とします。
* 型定義、バリデーション、クライアント生成を自動化します。
* PHP、TypeScript、REST の整合性を保証します。

## 設計方針 (規約)

* OpenAPI を、契約の最上位定義とします。
* JSON Schema は、OpenAPI から生成します。
* 型定義(TS、PHP) は、それぞれ異なる視点で投影し、自動生成します。
* DTO、TypeScript 型、cliant は、すべて同一の Schema から生成します。

### Source of Truth

OpenAPI 定義を、本プロジェクトにおける唯一の契約定義 (Source of Truth) とします。

- [契約 - 入出力仕様](./data_contract_spec.md) は、説明用ドキュメントとします。
- 実際の型、バリデーション、API 仕様は、OpenAPI に従います。

## 対象範囲

OpenAPI は、以下を対象とします。

* REST API エンドポイント
* リクエスト DTO、レスポンス DTO
* エラー構造

## 非対象 (Out of Scope)

* Embedding Provider API
* 外部サービスの契約
* Core ロジック
* 内部データ構造

「Embedding Provider API」「外部サービスの契約」は、Strategy パターンにより抽象化されます。

## 期待効果

* 契約と実装の、乖離防止
* フロントエンドとの、整合性確保
* 開発効率の向上

## Contracts との関係

* `data_contract_spec.md` は、概念仕様です。
* OpenAPI は、実行可能な仕様です。

## Strategy パターンとの関係

Embedding API は、下記の理由から、OpenAPI の対象外とします。

* 外部依存であるため
* Strategy により抽象化されるため

## 型生成

OpenAPI 定義から、以下を生成します。

* TypeScript 型
* Zod スキーマ (runtime validation)
* API クライアント

## バリデーション

* runtime validation は、Zod により実施します。
* スキーマは、OpenAPI から生成します。
