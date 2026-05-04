<!--
目的：「外部 API 仕様」の明文化
-->

# S2J Similarity Service - 外部 API 仕様

本ドキュメントは、Embedding 生成に利用する外部 API との **契約仕様** を定義します。
本仕様は、プロバイダ (OpenAI、Claude、Gemini 等) に依存しない、**抽象インターフェース** として扱います。

## 概要

* 本仕様は、Provider 非依存の契約です。
* 実装は、OpenAI、Claude、Gemini 等に対応可能とします。
* 将来的なプロバイダ追加に影響しない設計とします。

本仕様は、以下を定義します。

* Embedding 生成 API の抽象インターフェース
* 入出力データ構造
* プロバイダ差異の吸収方針
* エラーおよびリトライ方針

## 本仕様の位置付け

本仕様は、Embedding 生成の「抽象契約」を定義します。

具体的な HTTP 通信や、SDK コールは、Infrastructure 層で実装されます。

## 設計意図、方針、非対象

### 設計意図 (ゴール)

Embedding プロバイダやモデルの変更に対して、**コール側の実装変更を最小化します**。

### 設計方針 (規約)

* プロバイダ依存を、Infrastructure 層に閉じ込めます。
* Contracts 層では、抽象インターフェースのみ定義します。
* レスポンス形式を統一します。

### 非対象 (Out of Scope)

* 各プロバイダ固有の詳細仕様
* API キー管理の実装
* ネットワーク通信の具体実装

## 本仕様の責務

本仕様は、以下のみを扱います。

* テキストから Embedding Vector に契約を変換すること。
* API コールの入力/出力を定義すること。
* エラーを分類すること。

## 用語、前提

* **Embedding**
  * テキストを、数値ベクトルに変換したものです。
* **Provider**
  * Embedding を提供する、外部サービスです。
* **Model**
  * Embedding 生成に使用する、モデル識別子です。

## API キーの取り扱い

### 設計方針 (規約)

* API キーは、Strategy に注入されます。
* Strategy は、API コール時にのみ利用します。
* 内部状態として保持してよい。

### 非責務

* API キーの生成・保存
* セキュリティポリシー管理

## 入力 DTO

### フィールド定義

```json id="input_embed"
{
  "text": "string",
  "model": "string"
}
```

### フィールド詳細

| フィールド | 型 | 必須 | 説明 |
| ----- | ------ | -- | --------------- |
| text  | string | 必須 | Embedding 対象テキスト |
| model | string | 任意 | 使用するモデル識別子 |

## 出力 DTO

### フィールド定義

```json id="output_embed"
{
  "vector": [number],
  "dimension": number
}
```

### フィールド詳細

| フィールド | 型 | 説明 |
| --------- | -------- | ------------- |
| vector | number[] | Embedding ベクトル |
| dimension | number | ベクトル次元数 |

## Strategy パターンとの関係

本仕様は、EmbeddingStrategyInterface の契約を定義します。

* Strategy 実装は、Infrastructure 層に属します。
* Contracts は、インターフェースのみ定義します。

## 抽象インターフェース

### PHP

```php id="php_interface"
interface EmbeddingProvider {
    /**
     * @throws EmbeddingException
     */
    public function embed(string $text, ?string $model = null): array;
}
```

### TypeScript

```ts id="ts_interface"
export interface EmbeddingProvider {
  embed(text: string, model?: string): Promise<number[]>;
}
```

## Embedding Strategy / Adapter

### 設計意図 (ゴール)

複数の Embedding API プロバイダを差し替え可能にします。

### 設計方針 (規約)

* Strategy パターンで抽象化します。
* Adapter で外部 API をラップします。
* 戻り値は Embedding 型に統一します。

### 責務

* 外部 API との接続
* データ変換

### 非責務

* 類似度計算
* キャッシュ戦略

### Adapter の責務

* 外部 API レスポンスを Embedding に変換すること。
* 正規化を適用すること。
* 次元整合性を保証すること。

### インターフェース

```ts
export interface EmbeddingStrategyInterface {
  embed(text: string): Promise<Embedding>
}
```

### プロバイダ差異

| 項目 | 差異 |
|------|------|
| 次元数 | モデルごとに異なる |
| 正規化 | API によって未実施 |
| レスポンス形式 | JSON 構造が異なる |

## バッチ版 Embedding

### 設計意図 (ゴール)

プロバイダごとのバッチ処理能力を吸収します。

### 設計方針 (規約)

* バッチ対応可否は、プロバイダに依存します。
* 未対応の場合は、逐次実行にフォールバックします。

### 責務

* 外部 API 仕様を吸収すること。

### 非責務

* 並列制御
* リトライ戦略

### 差異例

| Provider | バッチ |
|----------|--------|
| OpenAI | 対応 |
| 他 | 未対応の場合あり |

## 非同期性 (Embedding)

### 設計意図 (ゴール)

外部 API コールを前提とした、非同期処理を明確化します。

### 設計方針 (規約)

* Embedding 取得は、必ず非同期とします。
* Promise を返します。

### 責務

* Embedding を取得すること。

### 非責務

* 類似度計算
* 同期処理

### インターフェース

```ts id="embedding_async"
embed(text: string): Promise<Embedding>
```

### 理由

* 外部 API コール
* ネットワーク I/O
* レイテンシが存在

## ベクトル正規化

### 設計意図 (ゴール)

類似度計算の前提を統一します。

### 設計方針 (規約)

* Embedding は、正規化済みであることを前提とします。
* 未正規化の場合は、Strategy が正規化します。

### 仕様

```ts
v = v / ||v||
```

### 注意

Similarity 側では、正規化を行いません。

## バリデーション

### 入力

* text は、(trim 後) 空文字を不可とします。
* 最大長は、プロバイダ制約に従います。
* model は、未指定時、デフォルトを使用します。

### 出力

* vector は、number[]
* 次元数は、1以上です。
* NaN、Infinity を含みません。

## プロバイダ差異の吸収

### 設計意図 (ゴール)

プロバイダごとの差異を統一し、上位レイヤを単純化します。

### 方針

* Infrastructure 層で変換します。
* Contracts 層には、統一形式のみ公開します。

### 吸収対象

* レスポンス形式
* エラー形式
* モデル指定方法
* トークン制限

## エラー仕様

### エラー分類

| 種別 | 内容 |
| --------------- | ----------- |
| ValidationError | 入力不正 |
| ProviderError | API レスポンスエラー |
| RateLimitError | レート制限 |
| TimeoutError | タイムアウト |
| NetworkError | 通信失敗 |

### エラー構造

```json id="error_embed"
{
  "error": {
    "type": "ProviderError",
    "message": "Embedding API failed",
    "details": {}
  }
}
```

## リトライ方針

### 設計意図 (ゴール)

一時的障害に対する耐性を確保します。

### 設計方針 (規約)

* RateLimit、Timeout、NetworkError は、リトライ対象とします。
* 最大リトライ回数を設定します。
* Exponential Backoff を推奨します。

## キャッシュ対象

### 設計意図 (ゴール)

どのデータをキャッシュ対象とするかを、明確化します。

### 対象

* Embedding (vector)

### 非対象

* 類似度スコア  
* API レスポンス全体  

### 注意

Embedding は、同一入力に対して deterministic であることを、前提とします。

## キャッシュ戦略 (参考)

### 設計意図 (ゴール)

API コストの削減とパフォーマンスを向上します。

### 設計方針 (規約)

* text + model を、キーとします。
* ハッシュ化して保存します。
* TTL は、用途に応じて設定します。

### キャッシュの位置付け

キャッシュは、パフォーマンス最適化であり、本 Contracts には含まれません。

実装は、Infrastructure 層に委ねます。
