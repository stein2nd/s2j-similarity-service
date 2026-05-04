<!--
目的：「Embedding API (OpenAI) の契約、エラー扱い」の明文化
-->

# S2J Similarity Service - OpenAI Embedding API 利用仕様

## 概要

本ライブラリは、埋め込みベクトル取得のために **OpenAI Embeddings API** を使用します。  
通信は `OpenAIEmbeddingStrategy` に集約され、インターフェイス `EmbeddingStrategyInterface` で抽象化されています。

## エンドポイント

| 項目 | 値 |
|------|-----|
| URL | `https://api.openai.com/v1/embeddings` |
| メソッド | POST |
| Content-Type | `application/json` |
| Authorization | `Bearer {apiKey}` |

## リクエストボディ

| キー | 型 | 説明 |
|------|-----|------|
| `model` | string | モデル名 (例: `text-embedding-3-small`) |
| `input` | string | ベクトル化するテキスト本文 |

本ライブラリでは、`language` / `locale` は API に送っていません (インターフェイス上はコール側の都合で保持)。

## レスポンス (成功時)

* **HTTP ステータス**: 200
* **ボディ**: JSON。埋め込みベクトルは `data[0].embedding` に格納された **float の配列**。

```json
{
  "data": [
    { "embedding": [ 0.012, -0.034, ... ], "index": 0 }
  ],
  "model": "text-embedding-3-small",
  "usage": { ... }
}
```

`data[0].embedding` をそのまま `VectorMath::cosineSimilarity` に渡す想定です。

## エラー扱い

| 状況 | 扱い |
|------|------|
| cURL 実行失敗 (ネットワークエラー等) | `Exception` をスロー。メッセージに `curl_error()` の内容を含める。 |
| HTTP ステータス≠200 | `Exception` をスロー。メッセージにステータスコードと、可能なら `error.message` を含める。 |
| レスポンス JSON の解析失敗、または `data[0].embedding` が存在しない | 空配列 `[]` を返す実装とするか、同様に `Exception` とする (現状実装は `?? []`)。 |

※ 共通ライブラリでは **API キーを保持しない**。コール側で環境変数 (例: `OPENAI_API_KEY`) や WordPress 設定で管理すること。

## 対応する実装

* **インターフェイス**: `S2J\SimilarityService\EmbeddingStrategyInterface::getEmbedding()`
* **OpenAI 実装**: `S2J\SimilarityService\OpenAIEmbeddingStrategy`
* **設定**: タイムアウト30秒 (cURL の `CURLOPT_TIMEOUT`)
