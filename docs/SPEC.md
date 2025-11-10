# SPEC: Semantic Similarity Library for WordPress Plugins

## はじめに

* 本ドキュメントでは、WordPress プラグイン開発等において利用可能な「意味的な類似度判定ライブラリ」の専用仕様を定義します。
* 本プラグインの設計は、以下の共通 SPEC に準拠します。
    * [WordPress Plugin Development Spec (共通仕様)](https://github.com/stein2nd/wp-plugin-spec/blob/main/docs/WP_PLUGIN_SPEC.md) の「5.4. 共通ライブラリを Composer 化」
* 以下は、本ライブラリ固有の仕様をまとめたものです。

---

## 1. ライブラリ概要

* 名称: S2J Similarity Service
* Composer パッケージ名称: `s2j/similarity-service`
* ライセンス: GPL-2.0-or-later
* 目的: 任意の言語における文章 A と文章 B との間の意味的な類似度を数値化して返却します。
* 特徴:
    * 「OpenAI Embeddings API (`text-embedding-3-small`)」を用いて、意味的な類似度を判定します。
    * Strategy パターンを採用した `EmbeddingStrategyInterface` により、将来、`text-embedding-3-large` や、他ベンダーモデル (Claude、Gemini 等) を差し替え可能とします。
    * Adapter パターンを採用した `OpenAIEmbeddingStrategy` にて外部 API との通信部分を抽象化し、互換 API も扱えます。

## 2. Composer パッケージ仕様

### 2.1. Composer パッケージ設定

本ライブラリは、Composer パッケージとして配布・管理されます。

**`composer.json` の主要設定:**

```json
{
    "name": "s2j/similarity-service",
    "description": "A pure PHP library for semantic similarity detection.",
    "type": "library",
    "license": "GPL-2.0-or-later",
    "autoload": {
        "psr-4": {
            "S2J\\SimilarityService\\": "src/"
        }
    },
    "require": {
        "php": ">=8.0"
    }
}
```

### 2.2. オートローディング

* **PSR-4準拠**: 名前空間 `S2J\SimilarityService\` が `src/` ディレクトリにマッピングされます。
* **名前空間プレフィックス**: `S2J\SimilarityService\`
* **ディレクトリ構造**: `src/` 配下の PHP ファイルが自動的にオートロードされます。

### 2.3. インストール方法

**プラグイン/テーマ側での利用例:**

```zsh
composer require s2j/similarity-service
```

**プラグイン/テーマのメインファイルでの読み込み:**

```php
<?php
// Composer のオートローダーを読み込む
require_once __DIR__ . '/vendor/autoload.php';

use S2J\SimilarityService\SimilarityService;
use S2J\SimilarityService\OpenAIEmbeddingStrategy;
```

### 2.4. 依存関係

* **PHP**: v8.0以降
* **外部依存**: なし (cURL は PHP 標準機能を使用)

---

## 3. プロジェクト構成

本章では、「フォルダー構成」を記載します。

### 3.1. フォルダー構成・ファイル構成

```
s2j-similarity-service/
├── README.md
├── LICENSE
├── composer.json  # Composer パッケージ定義
├── .gitignore
├── phpunit.xml  # PHPUnit 設定ファイル
├┬─ docs/
│└─ SPEC.md  # ライブラリ固有仕様
├┬─ examples/  # サンプルスクリプト
│└─ test_similarity.php  # CLI 手動テスト用サンプル
├┬─ tests/  # 単体テスト
│└─ SimilarityTest.php  # PHPUnit テストケース
└┬─ src/  # ソースコード (PSR-4 準拠)
　├─ SimilarityService.php  # 同一言語内の文章類似度を判定する、サービス・クラス
　├─ VectorMath.php  # ベクトル化された2文字列間のコサイン類似度を計算する、ユーティリティ・クラス
　├─ EmbeddingStrategyInterface.php  # 任意の Embedding モデルを抽象化
　└─ OpenAIEmbeddingStrategy.php  # OpenAI Embeddings API を利用して埋め込みベクトルを生成する Strategy
```

---

## 4. 技術スタック・開発環境

* **PHP**: v8.0以降 (Composer に対応)
* **OpenAI Embeddings API**: 意味類似度の算出

### 4.1. モデル選定方針

| モデル名 | 用途 | コメント |
| --- | ---- | --- |
| `text-embedding-3-small` | 通常利用 | 意味類似度の判定、コスト効率に優れる |
| `text-embedding-3-large` | 精度重視 | 研究・学習データの類似検索等に向く |

* 原則として `text-embedding-3-small` を採用します。
* ただし、多言語間 (特に低リソース言語) の類似度評価では `large` も検討します。

---

## 5. 機能仕様

### 5.1. 基本機能

* 処理:
    1. 基準テキスト本文と検証テキスト本文それぞれを、API でベクトル化します。
    2. 両者のコサイン類似度 (0.0〜1.0) を算出します。

### 5.2. パラメーター

| パラメーター | 型 | 説明 |
|---|---|---|
| apiKey | string | OpenAI API Key |
| model | string | モデル名 (例: text-embedding-3-small) |
| baseText | string | 基準テキスト本文 |
| targetText | string | 検証テキスト本文 |
| language | string | 言語コード (例: ja、en、fr) |
| locale | string | ロケール (例: ja_JP、en_US、fr_FR) |

### 5.3. 呼び出し側の例

```php
<?php
require_once __DIR__ . '/vendor/autoload.php';

use S2J\SimilarityService\SimilarityService;
use S2J\SimilarityService\OpenAIEmbeddingStrategy;

// Strategy をインスタンス化
$strategy = new OpenAIEmbeddingStrategy();

// SimilarityService をインスタンス化
$service = new SimilarityService($strategy);

// 類似度を計算
$result = $service->compare(
    'OpenAI の API キー',
    'text-embedding-3-small',
    'en',
    'en_US',
    '文章 A の内容',
    '文章 B の内容'
);

echo $result['similarity']; // 0.82 など
echo $result['model'];      // text-embedding-3-small
echo $result['language'];   // en
```

### 5.4. API キー管理

* 共通ライブラリでは、**キーを保持できません**。
* 呼び出し側で、環境変数または WordPress 設定画面を通じて管理してください。
* 例:
  * `OPENAI_API_KEY` (OpenAI Embeddings)

---

## 6. 実装状況サマリー

本章では、「現在の実装状況」を記載します。

### 6.1. 完全実装済み機能 (100% 完了)

* ✅ `SimilarityService`: 2つの文章間の意味的類似度を計算する、メイン・クラス
* ✅ `EmbeddingStrategyInterface`: Strategy パターンのインターフェイス
* ✅ `OpenAIEmbeddingStrategy`: OpenAI Embeddings API を利用する実装
* ✅ `VectorMath`: コサイン類似度を計算する、ユーティリティ・クラス
* ✅ Composer パッケージ化 (PSR-4準拠のオートローディング)

### 6.2. 実装完了率

* **機能実装**: 100%
* **Composer パッケージ化**: 100%

### 6.3. 品質評価

* **コード品質**: PSR-4準拠、Strategy パターン採用により、拡張性が高い
* **ユーザビリティ**: Composer 経由で簡単にインストール・利用が可能
* **セキュリティ**: API キーは呼び出し側で管理 (ライブラリ側では保持しない)
* **パフォーマンス**: cURL を使用した効率的な API 通信、ベクトル計算は最適化済み
* **保守性**: インターフェイスによる抽象化により、実装の差し替えが容易

---

## 7. クラス設計詳細

### 7.1. SimilarityService

**責務**: 2つの文章間の意味的な類似度を計算する、メインサービス・クラス

**主要メソッド**:

* `__construct(EmbeddingStrategyInterface $strategy)`: Strategy を注入
* `compare(string $apiKey, string $model, string $language, string $locale, string $baseText, string $targetText): array`: 類似度を計算して返却

**戻り値**:

```php
[
    'similarity' => float,  // 0.0〜1.0 の類似度スコア
    'model' => string,      // 使用したモデル名
    'language' => string    // 使用した言語コード
]
```

### 7.2. EmbeddingStrategyInterface

**責務**: 埋め込みベクトル生成処理を抽象化する、インターフェイス

**主要メソッド**:

* `getEmbedding(string $apiKey, string $model, string $text, string $language, ?string $locale = null): array`: テキストから埋め込みベクトルを生成

### 7.3. OpenAIEmbeddingStrategy

**責務**: OpenAI Embeddings API を使用して、埋め込みベクトルを生成

**実装詳細**:

* OpenAI Embeddings API (`https://api.openai.com/v1/embeddings`) を使用
* cURL による HTTP リクエスト
* エラー・ハンドリング (HTTP ステータス・コード、API エラー・レスポンス)

### 7.4. VectorMath

**責務**: ベクトル演算ユーティリティ

**主要メソッド**:

* `cosineSimilarity(array $vecA, array $vecB): float`: 2つのベクトル間のコサイン類似度を計算

---

## 8. Backlog

本章では、「今後の予定」を記載します。

### 8.1. 短期での改善予定 (1-2週間)

* Embeddings API バージョン更新対応
* エラー・ハンドリングの強化 (リトライ・ロジックなど)

### 8.2. 中期での改善予定 (1-2ヵ月)

* バッチ比較モード (複数文比較)
* 類似度閾値による分類ユーティリティ
* 他ベンダーの Embedding API 実装 (例: Claude、Gemini)

### 8.3. 長期での改善予定 (3-6ヵ月)

* WordPress REST API 経由の利用例 (JSON 返却)
* キャッシング機能の追加
* ユニットテストの追加
* CI/CD パイプラインの構築

---

## 9. まとめ

### 9.1. 主要な成果

* Composer パッケージとして配布可能な形で実装完了
* Strategy パターンによる拡張性の確保
* PSR-4準拠による標準的なオートローディング
* 純粋な PHP ライブラリとして、WordPress プラグインから簡単に利用可能

### 9.2. 今後の展望

* 他ベンダーの Embedding API サポート拡大
* パフォーマンス最適化 (キャッシング、バッチ処理など)
* テスト・カバレッジの向上

### 9.3. 推奨アクション

* プラグイン/テーマ開発者は、Composer 経由で `s2j/similarity-service` をインストールして利用
* API キーは環境変数または WordPress 設定画面で管理
* 将来的な拡張性を考慮し、Strategy インターフェイス経由で実装にアクセス
