<!--
目的：「入出力仕様」の明文化
-->

# S2J Similarity Service - 入出力仕様

本ドキュメントは、類似度算出における **入出力データ契約 (DTO)** を定義します。
本仕様は、PHP、TypeScript、REST API 間で共有される **Source of Truth** とします。

## 概要

* 本仕様は、REST、PHP、JS のすべてで共通の契約とします。
* スキーマは、将来的に OpenAPI として統合可能です。
* バリデーションは、Interfaces 層で実施することを推奨します。

本仕様は、以下を定義します。

* 類似度算出の入力データ構造
* 出力データ構造
* 型定義 (JSON、PHP、TypeScript)
* バリデーションルール
* エラー仕様

## 入力 DTO

### フィールド定義

```json
{
  "vectorA": [number],
  "vectorB": [number]
}
```

### フィールド詳細

| フィールド | 型 | 必須 | 説明 |
| ------- | -------- | -- | ------------------- |
| vectorA | number[] | 必須 | テキスト A の Embedding ベクトル |
| vectorB | number[] | 必須 | テキスト B の Embedding ベクトル |

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

## エラー仕様

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
