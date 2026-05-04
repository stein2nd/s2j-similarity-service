<!--
目的：「入出力仕様」の明文化
-->

# S2J Similarity Service - 入出力仕様

本ドキュメントは、類似度算出における **入出力データ契約 (DTO)** を定義します。
本仕様は、PHP、TypeScript、REST API 間で共有される **Source of Truth** とします。

## 概要

* 本仕様は、REST、PHP、JS のすべてで共通の「コアロジックに対する契約」とします。
* スキーマは、将来的に OpenAPI として統合可能です。
* バリデーションは、Interfaces 層で実施することを推奨します。

本仕様は、以下を定義します。

* 類似度算出の入力データ構造
* 出力データ構造
* 型定義 (JSON、PHP、TypeScript)
* バリデーションルール
* エラー仕様

REST API など外部インターフェースでは、本契約をもとにした「変換レイヤ (Application)」を介して利用されます。

## 本仕様の位置付け

本ドキュメントは、概念仕様であり、実行可能な契約は [OpenAPI 契約統合仕様](./openapi_spec.md) に定義されます。

## 類似度スコアの定義

### 設計意図 (ゴール)

API レスポンスに含まれるスコアの意味を明確にします。

### 定義

* score は、0.0〜1.0の範囲を取る。
* 1.0に近いほど、意味的に類似している。

### 注意

スコアは、[類似度算出の仕様](../core/similarity_spec.md) に定義されたアルゴリズムにもとづきます。

## 入力 DTO

### フィールド定義

```json
{
  "vectorA": [number],
  "vectorB": [number],
  "model": "string"
}
```

### フィールド詳細

| フィールド | 型 | 必須 | 説明 |
| ------- | -------- | -- | ------------------- |
| vectorA | number[] | 必須 | テキスト A の Embedding ベクトル |
| vectorB | number[] | 必須 | テキスト B の Embedding ベクトル |
| model | string | - | - |

### フィールド制約

vectorA と vectorB は、「同一モデル」から生成されている必要があります。

## 出力 DTO

### フィールド定義

```json
{
  "similarityScore": number
}
```

### フィールド詳細

| フィールド | 型 | 説明 |
| --------------- | ------ | --------------- |
| similarityScore | number | 0.0〜1.0の類似度スコア |

## 型定義

### JSON Schema (概念定義)

```json
{
  "SimilarityRequest": {
    "type": "object",
    "required": ["vectorA", "vectorB"],
    "properties": {
      "vectorA": {
        "type": "array",
        "items": { "type": "number" }
      },
      "vectorB": {
        "type": "array",
        "items": { "type": "number" }
      }
    }
  },
  "SimilarityResponse": {
    "type": "object",
    "required": ["similarityScore"],
    "properties": {
      "similarityScore": {
        "type": "number",
        "minimum": 0,
        "maximum": 1
      }
    }
  }
}
```

### PHP 型定義

```php
class SimilarityRequest {
    /** @var float[] */
    public array $vectorA;

    /** @var float[] */
    public array $vectorB;
}

class SimilarityResponse {
    public float $similarityScore;
}
```

### TypeScript 型定義

```ts
export type SimilarityRequest = {
  vectorA: number[];
  vectorB: number[];
};

export type SimilarityResponse = {
  similarityScore: number;
};
```

## Embedding 型定義

### 設計意図 (ゴール)

Embedding ベクトルをプロバイダ非依存で扱うための、統一フォーマットを定義します。

### 責務

* Embedding の構造を定義すること。
* 型安全を保証すること。

### 非責務

* Embedding の取得処理
* プロバイダ固有ロジック

### 型定義 (TypeScript)

```ts
export type Embedding = {
  vector: number[]
  dimension: number
  model: string
  provider: string
}
```

### フィールド定義

| フィールド | 説明 |
|------------|------|
| vector | 埋め込みベクトル |
| dimension | ベクトル次元数 |
| model | 使用モデル |
| provider | プロバイダ識別子 |

### 制約

* `vector.length === dimension` を保証すること。
* dimension は、同一計算内で一致する必要があること。
* vector は、数値配列 (float) とすること。

### 正規化ルール

* ベクトルは、unit vector (L2正規化) とすること。
* 正規化は、Strategy 実装側で実施すること。

### エラー仕様

Embedding 取得失敗時は、例外を throw します。

```ts
class EmbeddingError extends Error {
  provider: string
  cause?: unknown
}
```

## バリデーション

### 必須チェック

* `vectorA`、`vectorB` は、必須です。
* null、undefined は、不可です。

### 型チェック

* 各要素は、number 型です。
* NaN、Infinity は、不可です。

### 構造チェック

* 配列長は、一致していること。
* 配列長は、1以上。

### 範囲チェック

* 要素値は、有限数であること。
* 出力スコアは、0.0〜1.0に収まること。

### モデル整合性 (重要制約)

下記の制約を満たさない場合、類似度スコアの意味は保証されません。

* vectorA と vectorB は、「同一モデルから生成されたもの」でなければならない。

## エラー仕様

### エラーのレイヤ分類

本プロジェクトでは、エラーを以下のレイヤで分類します。
各レイヤの責務範囲で生成されたエラーは、上位レイヤで変換されます。

* Domain Error (Core)
* Application Error
* Infrastructure Error
* Transport Error (HTTP)

### エラー分類

| 種別 | 内容 |
| -------------------- | ------------- |
| ValidationError | 入力不正 |
| InvalidArgumentError | 次元不一致 |
| CalculationError | 計算不可 (ゼロベクトル等) |

### エラー構造 (共通)

```json
{
  "error": {
    "type": "ValidationError",
    "message": "Invalid input",
    "details": {}
  }
}
```

### エラー例

#### 次元の不一致

```json
{
  "error": {
    "type": "InvalidArgumentError",
    "message": "Vector dimensions do not match"
  }
}
```

#### 不正値

```json
{
  "error": {
    "type": "ValidationError",
    "message": "Vector contains NaN or Infinity"
  }
}
```

## エラー型定義

### 設計意図 (ゴール)

API および SDK 間で、共通のエラー構造を定義します。

### 型定義

```ts id="error_shape"
export type ErrorResponse = {
  code: string
  message: string
  details?: unknown
}
```

### 注意

SDK では、ErrorResponse を DomainError に変換して throw します。

## エラー型 (DomainError) 統一

ApiClient では、すべてのエラーを DomainError に正規化します。

### 設計意図 (ゴール)

* エラー処理を一貫させます。
* UI、Application 層での分岐を、単純化します。
* 外部 API 依存を、隠蔽します。

### 設計方針 (規約)

* 外部エラーは、直接投げません。
* 必ず DomainError に変換します。
* 型で分類可能にします。

### 責務

* エラーを分類することと標準化すること。
* 「型安全なエラーハンドリング」を提供すること。

### 非責務

* UI 表示ロジック
* ログフォーマット

### エラー型定義

```ts
export type DomainError =
  | NetworkError
  | TimeoutError
  | ValidationError
  | ApiError
  | UnknownError;
```

### 各エラー

```ts
export class NetworkError extends Error {
  readonly type = "NetworkError";
}

export class TimeoutError extends Error {
  readonly type = "TimeoutError";
}

export class ValidationError extends Error {
  readonly type = "ValidationError";
}

export class ApiError extends Error {
  readonly type = "ApiError";
  constructor(public status: number, message: string) {
    super(message);
  }
}

export class UnknownError extends Error {
  readonly type = "UnknownError";
}
```

### 変換ルール

| 元エラー | DomainError |
| ------------ | --------------- |
| fetch 失敗 | NetworkError |
| AbortError | TimeoutError |
| Zod 失敗 | ValidationError |
| HTTP `4xx`/`5xx` | ApiError |
| その他 | UnknownError |
