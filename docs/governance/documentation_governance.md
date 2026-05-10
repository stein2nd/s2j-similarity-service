# S2J Similarity Service - ドキュメンテーションガバナンス

## README / 使用方法ドキュメント整合ポリシー

### 設計意図 (ゴール)

公開 API、実装、ユーザー向けドキュメントの整合を保証し、ユーザーが下記のいずれにおいても、入口、命名、利用手順で迷わない状態を実現します。

* PHP 直接利用
* WordPress REST 経由利用
* TypeScript SDK 利用

本ポリシーは、下記を対象とし、公開 API の documentation source of truth を定義します。

* [READ ME](README.md)
* [使用方法](docs/interfaces/usage_spec.md)

### 設計方針 (規約)

* [READ ME](README.md) は、ユーザー向け最短導線とします。
* [使用方法](docs/interfaces/usage_spec.md) は、詳細仕様とします。
* [READ ME](README.md) と [使用方法](docs/interfaces/usage_spec.md) で、公開 API 名称を一致させます。
* 実装されていない API を、例示しません。
* 名前空間は、実コードと一致させます。
* strategy / service 等の、用語を統一します。
* REST 経路は、WordPress runtime 実 URL で説明します。
* TypeScript SDK は、PHP API と区別して記載します。
* generated/internal API は、公開 API として案内しません。

### 設計原則

```plaintext
README = shortest path
usage_spec = detailed contract
public API names are canonical
implementation and docs must match
```

### 責務

* 公開 API のドキュメントの一貫性
* [READ ME](README.md) のクイックスタートの品質
* [使用方法](docs/interfaces/usage_spec.md) の技術的な正確性
* runtime エンドポイントの明確さ
* 命名規則の一貫性

### 非責務

* generated code documentation
* internal architecture exhaustiveness
* implementation commentary
* migration guide for removed APIs

### README の責務

[READ ME](README.md) は、ユーザー向け quick start とし、下記を含むこと。

* インストール
* PHP の最小限の使い方
* REST の最小限の使い方
* WordPress との連携
* エンドポイント URL
* Bearer 認証の例
* curl 例
* TypeScript SDK の使い方
* ビルド / 検証コマンド

### README の非責務

[READ ME](README.md) は、下記を含めません。

* アーキテクチャーの詳細解説
* 内部生成コード
* 低レベルアダプタの内部構造
* 網羅的な API リファレンス

### usage_spec の責務

[使用方法](docs/interfaces/usage_spec.md) は、詳細仕様とし、下記を含むこと。

* コンストラクタの例
* strategy の注入
* キャッシュパターン
* 例外処理
* 応用例
* TS SDK の詳細な例

### usage_spec の非責務

[使用方法](docs/interfaces/usage_spec.md) は、下記を含めません。

* 廃止された例
* 過去の API エイリアス
* 実験的な内部実装

### 非対象 (Out of Scope)

* 過去の API 互換性に関するドキュメント
* 非推奨の名前空間のサポート
* 自動生成された docs ポータル
* Swagger UI の公開

### 公開 API の正式入口

#### PHP API

正式入口は下記となり、[READ ME](README.md) / [使用方法](docs/interfaces/usage_spec.md) は、これを正式 API とします。

```plaintext
S2J\Similarity\SimilarityService
S2J\Similarity\EmbeddingService
```

#### Strategy

正式名称は下記となり、`provider` 等の旧用語は、正式 API 名称として使用しません。

```plaintext
EmbeddingStrategyInterface
OpenAIEmbeddingStrategy
ClaudeEmbeddingStrategy
GeminiEmbeddingStrategy
```

#### REST API

[READ ME](README.md) に必ず明記します。

下記は、論理契約となります。

```plaintext
POST /v1/similarity
POST /v1/embedding
```

下記は、WordPress runtime エンドポイントとなります。

```plaintext
/wp-json/s2j/v1/similarity
/wp-json/s2j/v1/embedding
```

#### TypeScript SDK

正式入口は下記となり、generated raw client は、案内しません。

```plaintext
@s2j/similarity-client
createApiClient()
```

### 実装済み

#### README

下記は、整合済みです。

* PHP API
* SimilarityService
* EmbeddingService
* キャッシュの例
* WordPress REST アダプタ
* `Routes::register`
* runtime エンドポイント URL
* Bearer 認証のガイド
* curl
* TypeScript SDK
* build / `verify:codegen`
* [READ ME](README.md) の Usage 見出し「OpenAIEmbeddingStrategy オプション」— `apiKey` / `defaultModel` / `endpoint` / `timeoutSeconds` を名前付き引数で列挙し、コード例と実装 (`OpenAIEmbeddingStrategy` コンストラクタ) が一致

#### usage_spec

下記は、整合済みです。

* 命名
* 名前空間
* strategy の例
* constructor のシグネチャ
* 例外処理
* 擬似コードの分離
* TS SDK の分離

### 残事項

#### 継続運用

長文仕様である [使用方法](docs/interfaces/usage_spec.md) の細部レビューは、実装進行に伴い drift の可能性があるため、継続的に行います。
