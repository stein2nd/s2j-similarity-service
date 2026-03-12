<!--
目的：「プロジェクトの存在理由、提供価値、特徴」の明文化
-->

# 概要

## 名称・識別子

* **名称**: S2J Similarity Service
* **Composer パッケージ名**: `s2j/similarity-service`
* **ライセンス**: GPL-2.0-or-later

## 目的

任意の言語における **文章 A** と **文章 B** の間の **意味的な類似度** を数値化して返却する、PHP ライブラリです。  
WordPress プラグイン／テーマから Composer 経由で利用することを想定しています。

## 解決する課題

* 2 つのテキストが「意味的にどれだけ近いか」を定量的に知りたいが、自前で Embedding と類似度計算を実装したくない。
* 将来的に Embedding プロバイダ（OpenAI / Claude / Gemini 等）やモデルを差し替えたい。

## 提供価値

* **意味的類似度の数値化**: OpenAI Embeddings API（`text-embedding-3-small` 等）を用いた埋め込みベクトルと、コサイン類似度による 0.0〜1.0 のスコア提供。
* **差し替え可能な設計**: Strategy パターン（`EmbeddingStrategyInterface`）により、将来の他ベンダー・他モデル対応を容易に。
* **外部 API の抽象化**: Adapter パターンに則った `OpenAIEmbeddingStrategy` により、互換 API も扱いやすくする。

## 共通仕様との関係

本ライブラリの設計は、次の共通 SPEC に準拠します。

* [WordPress Plugin Development Spec（共通仕様）](https://github.com/stein2nd/wp-plugin-spec/blob/main/docs/WP_PLUGIN_SPEC.md) の「5.4. 共通ライブラリを Composer 化」

## 技術スタック・モデル選定

* **PHP**: v8.0 以降
* **Embedding**: OpenAI Embeddings API

| モデル名 | 用途 | コメント |
| --- | ---- | --- |
| `text-embedding-3-small` | 通常利用 | 意味類似度の判定、コスト効率に優れる |
| `text-embedding-3-large` | 精度重視 | 研究・学習データの類似検索等に向く |

原則として `text-embedding-3-small` を採用します。  
多言語間（特に低リソース言語）の類似度評価では `large` の利用を検討します。
