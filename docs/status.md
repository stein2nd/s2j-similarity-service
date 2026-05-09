<!--
目的：「実装状況サマリー、Backlog、品質レポート、まとめ」の明文化
-->

## S2J Similarity Service - 実装状況

本ページは、現状の実装状況を「機能単位」で一覧化し、**実装を100%にするために仕様書で何を明確化すべきか** を示します。

### 仕様書 (参照元)

* `README.md` (ユーザー向けの利用仕様)
* `docs/core/similarity_spec.md` (類似度算出の仕様)
* `docs/interfaces/usage_spec.md` (使用方法仕様)
* `docs/interfaces/rest_api_spec.md` (REST API 仕様)
* `docs/interfaces/sdk_spec.md` (型安全 SDK 設計)
* `schema/openapi.yaml` (OpenAPI: API 契約の起点)

※ `docs_old/` 配下は「仕様執筆の参考資料」であり、**実装状況の評価・完了条件の根拠には使用しません。**

### 機能一覧 (実装状況サマリー)

| 機能名 | 実装済み/未実装 | 実装％ | 完了条件 (実装％を100にする為に仕様書で明確化すべき点) |
|---|---:|---:|---|
| 類似度算出 (2文)`SimilarityService::similarity()` | 実装済み | 100 | - **docs/core/similarity_spec.md**: cosine + 0..1 正規化 + clamp の整合。追加の明確化なし |
| 類似度算出 (1×N)`SimilarityService::similarityOneToMany()` | 実装済み | 100 | - **docs/core/similarity_spec.md**: 出力の順序保証 (入力順維持) の整合。追加の明確化なし |
| 類似度算出 (N×N 行列)`SimilarityService::similarityMatrix()` | 実装済み | 100 | - **docs/core/similarity_spec.md**: 行列定義 (対称/対角) の整合。追加の明確化なし |
| Embedding 生成 (単発)`EmbeddingService::embed()` | 実装済み | 100 | - **docs/interfaces/usage_spec.md**: 利用例の名前空間/入口クラスを現行 `S2J\Similarity\...` に統一 (仕様として確定) |
| Embedding 生成 (バッチ)`EmbeddingService::embedBatch()` | 実装済み | 100 | - **docs/interfaces/sdk_spec.md**: 「出力は入力順を維持」の整合。追加の明確化なし |
| OpenAI Embeddings 連携 `OpenAIEmbeddingStrategy` | 実装済み | 100 | - **docs/interfaces/usage_spec.md**: endpoint/timeout 等の設定可能項目を仕様として明記 (README とも整合させる) |
| ベクトル正規化 (L2正規化) | 実装済み | 100 | - **docs/core/similarity_spec.md**: 「入力は正規化済み」前提を、Strategy 側で担保する方針として明文化 (現実装と一致)。追加の明確化なし |
| キャッシュ (Embedding Decorator)`CachedEmbeddingStrategy` + `InMemoryCache` | 実装済み | 100 | - **docs/interfaces/sdk_spec.md**: キャッシュキーは正規化テキスト (trim + lower) + model (未指定は `__default__`) + provider + normalized=true を JSON 化し sha256。TTL 既定値は 24h。追加の明確化なし |
| エラー型 (DomainError 系) | 実装済み | 100 | - **docs/interfaces/rest_api_spec.md** の命名規則どおり、`error.type` / OpenAPI enum / PHP `DomainError::$type` は snake_case に統一済み。追加の明確化なし |
| 旧 API (`S2J\\SimilarityService\\...`) の削除 | 実装済み | 100 | - **docs/interfaces/usage_spec.md** の方針どおり、旧 API の namespace / compare() / getEmbedding() を削除済み。追加の明確化なし |
| テスト基盤 (PHPUnit + WorDBless) | 実装済み | 100 | - `phpunit/phpunit` は v13 系、`phpunit.xml` は現行スキーマ。<br>- **`composer.lock` をリポジトリ管理** し、`composer install` で WordPress 配置・WorDBless ドロップインまで再現可能。<br>- **`tests/bootstrap.php`** で WorDBless により WordPress 実ランタイムをテストに読み込み。<br>- 追加の明確化なし |
| HTTP サーバ実装 (WordPress REST API Adapter) | 実装済み | 90 | - **実装済**: `adapters/http/wordpress/` にて `register_rest_route`、Controller、`RequestValidator`、`ErrorMapper`、`ResponseFactory`、Bearer 認証・認証後コールバック (`BearerTokenAuth` 第2引数・`permissionDenied`) による **403**、`rate_limit` 時の **`Retry-After`** (※ `DomainError::$details['retry_after']` が非負整数のとき)。<br>- **100% の残り**: **docs/interfaces/rest_api_spec.md** が「HTTP middleware」等を未実装として列挙している記述の更新 (現状コードとの同期)、および運用側が担うゲートウェイ型レート制限・監査ログ等はライブラリ非対象である旨を仕様で明示。<br>- (任意) **OpenAPI contract test** (推奨) の自動化方針を仕様に追記 |
| REST API (`POST /v1/similarity`, `POST /v1/embedding` の契約に対応する HTTP 面) | 実装済み | 92 | - **実装済**: **docs/interfaces/rest_api_spec.md** の「REST API の成立条件」6項目に対し、(1) OpenAPI、(2) `Routes` + `register_rest_route`、(3) Controller、(4) `ErrorMapper`、(5) リクエスト検証、(6) **`tests/Integration/WordPressRestAdapterIntegrationTest.php`** による **WP_REST_Server 経由の HTTP 統合テスト** (WorDBless)。認証失敗 **401**、権限 **403** (permission_callback または `AuthorizationError`)、レート制限 **429** + `Retry-After`、その他ドメインエラーと HTTP ステータスの対応をテストで検証。<br>- **100% の残り**: `schema/openapi.yaml` のパス表記 (`/v1/...`) と WordPress 側の namespace/route (`s2j/v1` + `/similarity` 等) の **URL 上の対応関係**を **README / rest_api_spec** で一文固定する。<br>- **OpenAPI JSON schema の機械検証** (推奨) を CI に載せるかどうかを仕様・engineering ドキュメントで確定。<br>- レート制限の **運用上の上限値・ウィンドウ**はインフラまたはホスト側の確定事項として仕様に依らない (ライブラリは `RateLimitError` + `retry_after` を表現可能) |
| OpenAPI を Single Source にした codegen (TS types/Zod/PHP DTO) | 実装済み | 70 | - **codegen 実行基盤**: `scripts/generate/all.zsh` により `schema/openapi.yaml` から生成可能 (開発/CI/release のみで実行)<br>  - TS types/Zod/raw client (dev-only): `tools/generated/ts/`<br>  - PHP DTO (Composer 配布物に同梱): `src/Contracts/DTO/Generated/`<br>  - OpenAPI Generator 版の再現性: `openapitools.json` で固定<br>- **残課題 (100%条件)**: CI で `./scripts/generate/all.zsh` → `git diff --exit-code` を必須化し、生成差分があれば fail するルールを実装 (GitHub Actions 等)<br>- **公開範囲**: `docs/interfaces/sdk_spec.md` / `docs/overview.md` の方針どおり、TS の generated/raw は WordPress ユーザー向け公開 API にしない (配布物に混入しない) ことを CI/リリース手順で担保 |
| TypeScript SDK (ApiClient/HttpClient/Retry/Timeout 等) | 未実装 | 0 | - **docs/interfaces/sdk_spec.md**: 最小実装スコープ (ApiClientのみ vs Retry/Timeout まで) を確定<br>- **docs/interfaces/sdk_spec.md**: エラー型 (DomainError) を TS 側でどう表現するか (enum/union/class) を確定 |
| README / 使用方法ドキュメントの整合 (命名・例コード) | 一部実装 | 68 | - **README.md**: `SimilarityService` / `EmbeddingService` の例は現行 API と整合。<br>- **未反映**: WordPress REST への組み込み (`Routes::register`、実際のベースパス `…/wp-json/s2j/v1/similarity` 等)、認証・環境変数の推奨パターンを README に追記すると 100% 評価に近づく。<br>- **docs/interfaces/usage_spec.md**: 例コードの用語/命名 (provider/strategy 等) と実装の整合を仕様として確定 |

### 補足 (現行コードから見える前提)

* 本リポジトリは **「純粋な PHP ライブラリ」** としては主要機能が実装済み (類似度計算・Embedding・バッチ・キャッシュ・基本エラー)。
* 公開 API は `S2J\Similarity\...` に統一され、`S2J\SimilarityService\...` (旧 API) は削除済みです。
* `error.type` は `schema/openapi.yaml` と `DomainError::$type` とで snake_case に統一済みです。
* HTTP runtime は WordPress REST API を想定し、`adapters/http/wordpress/` に routing・Controller・認証・エラーマッピング・レスポンス整形を実装済みです。**HTTP 統合テスト**は WorDBless 上の `WP_REST_Server` / `rest_do_request()` で実行します。
* **`composer.lock`** をバージョン固定のソースとして追跡しており、clone 後は `composer install` でテスト用 WordPress 配置と WorDBless のドロップインまで再現できます。
* OpenAPI には `ErrorResponse` まで定義済みです。**JSON Schema によるレスポンス検証**や **codegen の CI ゲート**は、上表の「OpenAPI codegen」「REST API」行の完了条件に含めます。
