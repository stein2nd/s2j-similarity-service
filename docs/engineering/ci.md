# S2J Similarity Service - CI 品質ゲート

## CI 品質ゲート方針

### 設計意図 (ゴール)

本リポジトリ全体について、品質保証、契約整合、回帰検知を自動化し、仕様と実装の乖離を継続的に防止します。

本プロジェクトは、下記から構成されるため、それぞれの責務に応じた品質ゲートを CI 上で分離して実行します。

* PHP Core
* WordPress REST 統合
* OpenAPI 契約
* コード生成
* TypeScript SDK

### 設計方針 (規約)

* 品質ゲートは、CI 上で自動実行します。
* 失敗時は、merge 不可とします。
* ジョブは、責務単位で分離します。
* OpenAPI 契約整合は、コード生成の再現性で保証します。
* WordPress 統合は、PHP ユニットテストと分離します。
* TypeScript SDK は、独立ジョブとします。
* 外部プロバイダ API 依存のテストは、CI に含めません。

### 責務

CI の責務は、下記のとおりです。

* 回帰の検出
* 契約の一貫性
* 生成された成果物の検証
* PHP の品質ゲート
* WordPress 統合検証
* TypeScript SDK 検証
* 決定論的なリリースの安全性

### 非責務

CI の非責務は、下記のとおりです。

* インフラストラクチャの稼働率検証
* プロバイダの SLA 確認
* 本番環境の監視
* デプロイのオーケストレーション
* ブラウザー互換性の検証

### 非対象 (Out of Scope)

* プレビュー deployment
* SaaS スモークテスト
* ブラウザーのマトリックステスト
* ビジュアル回帰テスト
* 負荷テスト
* カオスエンジニアリング

### CI マトリックス

#### ジョブ1: PHP 品質

対象は、下記のとおりです。

```bash
composer run test:unit
composer run phpstan
composer run phpcs
```

下記コード例は、「Linting」(スタイルガイド違反を自動修正) させています。

```bash
composer run phpcbf
```

下記コード例は、PHPStan による静的解析と、PHPCS による「Linting」をまとめて実行しています。

```bash
composer run lint:php
```

#### ジョブ2: WordPress 統合

対象は、下記のとおりです。

* WorDBless
* WP_REST_Server

下記を検証します。

* ルートの登録
* リクエストの検証
* 権限コールバック
* コントローラのディスパッチ
* REST エラーのマッピング
* ステータスコード
* Retry-After

#### ジョブ3: OpenAPI / コード生成

対象は、下記のとおりです。

```bash
./scripts/generate/all.zsh
git diff --exit-code
```

下記を目的とします。

* スキーマのドリフト検出
* 生成されたアーティファクトの一貫性
* 決定論的ビルド

#### ジョブ4: TypeScript SDK

対象は、下記のとおりです。

```bash
npm test -w @s2j/similarity-client
npm run build -w @s2j/similarity-client
npm run typecheck -w @s2j/similarity-client
```

### CI で実行しないもの

下記は、実行しません。

* ブラウザー E2E
* Playwright
* Cypress
* OpenAI API ライブ・アクセス
* Claude API ライブ・アクセス
*  Gemini API ライブ・アクセス
* パフォーマンスベンチマーク
* 負荷テスト
