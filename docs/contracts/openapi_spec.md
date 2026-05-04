<!--
目的：「OpenAPI 契約統合仕様」の明文化
-->

# S2J Similarity Service - OpenAPI 契約の統合仕様

本ドキュメントは、Contracts 層における API 契約を、OpenAPI 形式で統合するための方針を定義します。

## 設計意図 (ゴール)

* API 契約を、単一の Source of Truth とします。
* 型定義、バリデーション、クライアント生成を自動化します。
* PHP、TypeScript、REST の整合性を保証します。

## 設計方針 (規約)

* OpenAPI を、契約の最上位定義とします。
* JSON Schema は、OpenAPI から生成します。
* 型定義 (TS、PHP) は、それぞれ異なる視点で投影し、自動生成します。
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

## 型定義ルール (`nullable`、`optional`、`enum`)

### 設計意図 (ゴール)

OpenAPI と各言語 (TypeScript、PHP) の型不整合を防ぎ、codegen の安定性を確保します。

### 設計方針 (規約)

* `required` によって、必須項目を明示します。
* `null` と未定義 (`undefined`) を明確に区別します。
* `enum` は、完全列挙とします。

### 責務

* OpenAPI 記述の厳密性を確保すること。
* codegen の安定性を保証すること。

### 非責務

* DTO の利用方法
* 実行時バリデーション
* UI 表現

### `required` のルール

* `required` に含まれるフィールドは、必須とします。
* `required` に含まれない場合、そのフィールドは `optional` とします。

```yaml id="required_example"
type: object
required:
  - text
properties:
  text:
    type: string
  metadata:
    type: string
```

### `nullable` のルール

* `nullable` は、「値として null を許容する」ことを意味します。
* `undefined` (未定義) とは別概念とします。

```yaml id="nullable_example"
type: object
properties:
  description:
    type: string
    nullable: true
```

### `enum` のルール

* `enum` は、すべての許容値を列挙します。
* 不明な値は、許容しません。

```yaml id="enum_example"
type: string
enum:
  - openai
  - claude
  - gemini
```

### TypeScript へのマッピング

| OpenAPI | TypeScript |
|--------|-----------|
| `required` | 必須プロパティ |
| `optional` | `?` |
| `nullable` | `\| null` |

### PHP へのマッピング

| OpenAPI | PHP |
|--------|-----|
| `required` | non-null |
| `optional` | `?Type` または未設定 |
| `nullable` | `?Type` |

### 禁止事項

* `nullable` と `required` の意味を混同すること。
* `enum` を省略する (string で逃げる) こと。
* `optional` + `nullable` を無秩序に併用すること。

## バリデーション

* runtime validation は、Zod により実施します。
* スキーマは、OpenAPI から生成します。
