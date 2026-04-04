<!--
目的：「入出力の定義 (パラメータ、戻り値)」の明文化
-->

# S2J Similarity Service - データ入出力の仕様

## 入力パラメータ (compare の引数)

`SimilarityService::compare()` の引数は次のとおりです。

| パラメータ | 型 | 説明 |
|------------|-----|------|
| apiKey | string | OpenAI API Key |
| model | string | モデル名 (例: `text-embedding-3-small`) |
| language | string | 言語コード (例: `ja`, `en`, `fr`) |
| locale | string | ロケール (例: `ja_JP`, `en_US`, `fr_FR`) |
| baseText | string | 基準テキスト本文 |
| targetText | string | 検証テキスト本文 |

※ `language` / `locale` は現状、OpenAI Embeddings のリクエストには含めていません。呼び出し側の識別用として保持しています。

## 戻り値 (compare の返却)

| キー | 型 | 説明 |
|------|-----|------|
| similarity | float | コサイン類似度。0.0〜1.0。小数第6位で丸め。 |
| model | string | 使用したモデル名 |
| language | string | 使用した言語コード |

### 例

```php
[
    'similarity' => 0.823456,
    'model' => 'text-embedding-3-small',
    'language' => 'en',
]
```

## `EmbeddingStrategyInterface::getEmbedding` の入出力

| 種別 | 名前 | 型 | 説明 |
|------|------|-----|------|
| 引数 | apiKey | string | API Key |
| 引数 | model | string | モデル名 |
| 引数 | text | string | ベクトル化するテキスト |
| 引数 | language | string | 言語コード |
| 引数 | locale | string\|null | ロケール (省略可) |
| 戻り値 | — | array | float の配列 (埋め込みベクトル) |
| 例外 | \Exception | — | API 通信エラーや形式不正時 |

## API キー管理

* ライブラリは **キーを保持しない**。
* 呼び出し側で、環境変数 (例: `OPENAI_API_KEY`) または WordPress の設定画面等で管理してください。
