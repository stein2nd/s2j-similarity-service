<!--
目的：「モデルの型、設定配列、メタキー、option、データフロー、データ更新内容」の明文化
-->

# S2J Similarity Service - データ辞書

本ドキュメントは、本プロジェクトで扱うデータ型の **意味、制約、利用文脈** を定義します。
本仕様は、Contracts 層における **Source of Truth (意味定義)** とします。

## 概要

本仕様は、以下を定義します。

* 各データ型の意味 (Semantic)
* 型 (Type)
* 制約 (Constraints)
* 利用箇所 (Usage)

## 非対象 (Out of Scope)

* 永続化データ構造 (DB スキーマ)
* キャッシュ実装
* UI 表示形式

## 一貫性ルール

### ベクトル整合性

* 同一モデルで生成された Embedding のみ、比較可能です。

### 型の一貫性

* JSON、PHP、TypeScript で、同一意味を維持します。

### 正規化の一貫性

* スコアは、常に0〜1に正規化されます。

## 基本データ型

### 1. EmbeddingVector

#### 定義

テキストを、数値ベクトルに変換したものです。

* 各要素は、意味空間における特徴量を表します。
* 同一モデルで生成されたベクトル同士のみ、比較可能です。

#### 型

```plaintext id="type_embedding"
float[]
```

#### 制約

* 配列長は、1以上の固定です (同一モデル内で一致)。
* 要素は、有限数です (NaN、Infinity 不可)。

#### 利用箇所

* [コア - 類似度算出の仕様](../core/similarity_spec.md) (入力)
* [契約 - 外部 API 仕様](./embedding_api_spec.md) (出力)

### 2. SimilarityScore

#### 定義

2つのテキストの、意味的近さを表すスコアです。

#### 型

```plaintext id="type_score"
float
```

#### 範囲

```plaintext id="range_score"
0.0 <= score <= 1.0
```

#### 意味

* 0.0: 無関係
* 0.5: 中程度の関連
* 1.0: 意味的にほぼ同一

#### 制約

* 正規化済み (cosine similarity 変換後)
* 浮動小数誤差を考慮し、clamp される

#### 利用箇所

* [コア - 類似度算出の仕様](../core/similarity_spec.md) (出力)
* [契約 - 入出力仕様](./data_contract_spec.md)

### 3. InputText

#### 定義

Embedding 対象となる、自然言語テキスト (日本語、英語など) です。

#### 型

```plaintext id="type_text"
string
```

#### 制約

* (trim 後) 空文字は、不可です。
* 最大長は、プロバイダ制約に依存します。

#### 利用箇所

* [契約 - 外部 API 仕様](./embedding_api_spec.md) (入力)

### 4. ModelIdentifier

#### 定義

Embedding モデルを識別する文字列で、Embedding の生成方法を決定します。

#### 型

```plaintext id="type_model"
string
```

#### 制約

* プロバイダに依存します。
* 未指定時は、デフォルトモデルを使用します。

#### 利用箇所

* [契約 - 外部 API 仕様](./embedding_api_spec.md)

## 複合データ型 (DTO)

### 1. SimilarityRequest

#### 定義

類似度計算の入力です。

#### 構造

```plaintext id="dto_req"
{
  vectorA: EmbeddingVector
  vectorB: EmbeddingVector
}
```

#### 制約

* vectorA と vectorB は、同一次元です。

#### 利用箇所

* [契約 - 入出力仕様](./data_contract_spec.md)

### 2. SimilarityResponse

#### 定義

類似度計算の結果です。

#### 構造

```plaintext id="dto_res"
{
  similarityScore: SimilarityScore
}
```

### 3. EmbeddingRequest

#### 定義

Embedding API コールの入力です。

#### 構造

```plaintext id="dto_embed_req"
{
  text: InputText
  model?: ModelIdentifier
}
```

### 4. EmbeddingResponse

#### 定義

Embedding API コールの結果です。

#### 構造

```plaintext id="dto_embed_res"
{
  vector: EmbeddingVector
  dimension: number
}
```

#### 制約

* dimension === vector.length

## 補助概念

### 1. Dimension

#### 定義

ベクトルの次元数です。

#### 型

```plaintext id="type_dim"
integer
```

#### 制約

* 1以上で、モデルごとに固定です。

### 2. NormalizedScore

#### 定義

正規化済みスコアです。[SimilarityScore](#2-similarityscore) と同義 (概念名) です。
