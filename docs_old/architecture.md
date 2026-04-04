<!--
目的：「フォルダー構成、主要ファイル、技術スタック、ビルド、責務」の明文化
-->

# S2J Similarity Service - アーキテクチャー

## フォルダー・ファイル構成

```
s2j-similarity-service/
├── README.md
├── LICENSE
├── composer.json
├── .gitignore
├── phpunit.xml
├── docs/
│   ├── specs.md           # 仕様書の起点
│   ├── overview.md
│   ├── similarity_spec.md
│   ├── embedding_api_spec.md
│   ├── data_contract_spec.md
│   ├── architecture.md    # 本ファイル
│   ├── composer_package_spec.md
│   └── SPEC.md            # 統合版
├── examples/
│   └── test_similarity.php
├── tests/
│   └── SimilarityTest.php
└── src/
    ├── SimilarityService.php
    ├── VectorMath.php
    ├── EmbeddingStrategyInterface.php
    └── OpenAIEmbeddingStrategy.php
```

## 名前空間とオートロード

* **PSR-4**: 名前空間 `S2J\SimilarityService\` → `src/`
* 詳細は [composer_package_spec.md](composer_package_spec.md) を参照。

## クラス責務と仕様との対応

| クラス | 責務 | 仕様ドキュメント |
|--------|------|-------------------|
| **SimilarityService** | 2つの文章の意味的な類似度を計算するメインサービス。Strategy を注入し、Embedding 取得 → コサイン類似度計算 → 返却までを統括。 | [data_contract_spec.md](data_contract_spec.md), [similarity_spec.md](similarity_spec.md) |
| **EmbeddingStrategyInterface** | 埋め込みベクトル生成を抽象化する Strategy のインターフェイス。 | [embedding_api_spec.md](embedding_api_spec.md), [data_contract_spec.md](data_contract_spec.md) |
| **OpenAIEmbeddingStrategy** | OpenAI Embeddings API を呼び出し、ベクトルを返す実装。 | [embedding_api_spec.md](embedding_api_spec.md) |
| **VectorMath** | 2つのベクトル間のコサイン類似度を計算するユーティリティ。 | [similarity_spec.md](similarity_spec.md) |

## 設計パターン

* **Strategy**: 埋め込み取得ロジックを `EmbeddingStrategyInterface` に抽象化し、`OpenAIEmbeddingStrategy` で OpenAI を利用。将来の他ベンダー実装を差し替え可能。
* **Adapter 的役割**: `OpenAIEmbeddingStrategy` が外部 API をラップし、インターフェイスに合わせた戻り値 (`array`) を返す。

## 依存の向き

```
SimilarityService
  → EmbeddingStrategyInterface (依存性注入)
  → VectorMath (静的メソッド)

OpenAIEmbeddingStrategy
  → EmbeddingStrategyInterface (実装)
  → 外部: OpenAI API
```

* 共通ライブラリは「外部 API キー」に依存せず、呼び出し側がキーを渡す設計。

## 開発環境・技術スタック

* **PHP**: v8.0以降
* **外部依存**: なし (cURL は PHP 標準)
* テスト: PHPUnit (`phpunit.xml`)
