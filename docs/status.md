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
* `docs/contracts/codegen_spec.md` (OpenAPI codegen・生成物の扱い)
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
| HTTP サーバ実装 (WordPress REST API Adapter) | 実装済み | 100 | - **実装済**: `adapters/http/wordpress/` にて `Routes::register` → `register_rest_route` (唯一の routing mechanism)、`SimilarityController` / `EmbeddingController`、`RequestValidator`、`ErrorMapper`、`ResponseFactory`、`BearerTokenAuth` (第2引数・`permissionDenied` による **403**)、`rate_limit` 時の **`Retry-After`** (`DomainError::$details['retry_after']` が非負整数のとき)。デフォルト namespace は `Routes::DEFAULT_NAMESPACE` (`s2j/v1`)。`Routes` クラスは **docs/interfaces/rest_api_spec.md** § REST API (HTTP Runtime / WordPress REST Adapter) を参照。<br>- **仕様反映済み**: **rest_api_spec.md** に「WordPress REST API Adapter の完成条件」および「REST API (HTTP Runtime / WordPress REST Adapter)」(OpenAPI 論理パスと WordPress runtime の関係・責務境界) が明文化されている。<br>- **(任意・ドキュメント)** rest_api_spec 内にコード現状と齟齬する「未実装」列挙が残っていれば、仕様側を現状に合わせて整理する。 |
| REST API (`POST /v1/similarity`, `POST /v1/embedding` の契約に対応する HTTP 面) | 実装済み | 100 | - **実装済**: **docs/interfaces/rest_api_spec.md** の「REST API の成立条件」および § REST API (HTTP Runtime / WordPress REST Adapter) に沿い、`schema/openapi.yaml`、`Routes` + `register_rest_route`、Controller 層、リクエスト検証、`ErrorMapper`、`tests/Integration/WordPressRestAdapterIntegrationTest.php` (WorDBless + `WP_REST_Server`)。200 / 400 / 401 / 403 / 408 / 429 (`Retry-After`) / 503 等を統合テストで検証。OpenAPI 論理パス ↔ `/wp-json/s2j/v1/...` または `?rest_route=/s2j/v1/...` は仕様・テスト (`rest_url()`) で整合。<br>- **ユーザー導線 (必須) 達成**: **README.md** に OpenAPI 論理パス、WordPress runtime URL、`rest_api_init` 組み込み例、Bearer 運用、curl 例を記載済み (rest_api_spec の「100% 完了条件」必須項目)。<br>- **(任意)** OpenAPI レスポンスの JSON Schema 機械検証を CI に載せるかは engineering policy で確定。 |
| OpenAPI を Single Source にした codegen (TS types/Zod/PHP DTO) | 実装済み | 100 | - **実行**: `scripts/generate/all.zsh` を唯一の入口とし、`schema/openapi.yaml` から生成 (開発 / CI / release のみ)。再現性は `openapitools.json` + `package-lock.json`。<br>- **出力**: PHP DTO は `src/Contracts/DTO/Generated/` (Composer 配布)。TS types・Zod・raw client は `tools/generated/ts/` (リポジトリ追跡、**docs/contracts/codegen_spec.md** の公開境界)。Composer の git アーカイブから TS は `.gitattributes` の `export-ignore` で除外。<br>- **CI**: `.github/workflows/openapi-codegen.yml` (`npm ci` → `./scripts/generate/all.zsh` → `npm run build -w @s2j/similarity-client` → `git diff --exit-code`)。ローカルは `npm run verify:codegen` / `npm run build:ts-client`。<br>- **追加で詰められる余地** (実装％とは別): OpenAPI レスポンスの JSON Schema 機械検証を CI に足すかどうか |
| TypeScript SDK (`@s2j/similarity-client`: ApiClient / HttpClient / Timeout / Retry / エラー union) | 実装済み | 90 | - **実装済**: **docs/interfaces/sdk_spec.md** (「TypeScript SDK の最小実装スコープとエラー設計」ほか) に整合する形で、`packages/ts-client` に公開ラッパーを実装。`createApiClient` / `ApiClient.similarity` / `embedding`、`HttpClient` (`fetch` 抽象)、`withTimeout` と軽量 `withRetry` (指数バックオフ、通信失敗・タイムアウト・HTTP 429・503 を対象)、`error.type` 基準の判別可能 union (`SDKError`) と `SDKErrorBase` / `isSDKError`、REST `ErrorResponse` の正規化。生成 raw client は `tools/generated/ts` に留め、公開 API は本パッケージのみ。<br>- **100% の残り**: **README.md** (必要なら **docs/interfaces/usage_spec.md**) に、モノレポでのパス、`createApiClient` の最小例、`npm run build -w @s2j/similarity-client` / `npm run verify:codegen` 等の手順を追記し、REST 節とあわせて HTTP ユーザーが迷わないようにする。<br>- **100% の残り**: `packages/ts-client` に自動テスト (モック `HttpClient` 等) を追加し、CI で実行するかを engineering で確定する。<br>- (任意) 成功ボディの runtime 検証を Zod 等で載せるかは **sdk_spec**・運用で確定 (現状は形状チェック中心)。 |
| README / 使用方法ドキュメントの整合 (命名・例コード) | 一部実装 | 85 | - **README.md**: PHP アプリからの `SimilarityService` / `EmbeddingService` / キャッシュの例は現行 `S2J\Similarity\...` API と整合。**WordPress REST アダプター**については、論理パス・`/wp-json/s2j/v1/...`・`rest_route` 形式、`Routes::register` による組み込み、Bearer 運用、curl 例まで **記載済み** (**docs/interfaces/rest_api_spec.md** § REST API (HTTP Runtime / WordPress REST Adapter) の必須導線と一致)。<br>- **100% の残り**: **TypeScript SDK** (`@s2j/similarity-client`) の利用・ビルド手順を README または **docs/interfaces/usage_spec.md** に追記する (上表「TypeScript SDK」と同じ backlog)。<br>- **100% の残り**: **docs/interfaces/usage_spec.md** の用語 (provider / strategy 等) を現行コードと突き合わせ、仕様側で固定する。 |

### 補足 (現行コードから見える前提)

* 本リポジトリは **「純粋な PHP ライブラリ」** としては主要機能が実装済み (類似度計算・Embedding・バッチ・キャッシュ・基本エラー)。
* 公開 API は `S2J\Similarity\...` に統一され、`S2J\SimilarityService\...` (旧 API) は削除済みです。
* `error.type` は `schema/openapi.yaml` と `DomainError::$type` とで snake_case に統一済みです。
* HTTP runtime は WordPress REST API のみ (`register_rest_route`) とし、`adapters/http/wordpress/` に routing・Controller・認証・エラーマッピング・レスポンス整形を実装済みです。**HTTP 統合テスト**は WorDBless 上の `WP_REST_Server` / `rest_do_request()` で実行します。**README.md** には OpenAPI 論理パスと WordPress 実エンドポイント・組み込み・curl が記載されています。
* **`composer.lock`** をバージョン固定のソースとして追跡しており、clone 後は `composer install` でテスト用 WordPress 配置と WorDBless のドロップインまで再現できます。
* **OpenAPI codegen**: `docs/contracts/codegen_spec.md` の Governance に従い、`npm run verify:codegen` と GitHub Actions (`.github/workflows/openapi-codegen.yml`) で生成差分ゼロを検証済みです。同ジョブで **`@s2j/similarity-client` の `tsc` ビルド**も実行します。
* **TypeScript SDK** は npm ワークスペース `packages/ts-client` (パッケージ名 `@s2j/similarity-client`) として実装済みです。`tools/generated/ts` の生成クライアントは内部利用のみとし、外部からは本ラッパーを公開面とします。
* **GitHub Actions** は現時点で **OpenAPI codegen + TS SDK ビルド**のジョブのみです (`composer test` / PHPUnit はワークフロー未定義)。PHP のユニット・統合テストはローカルで `composer test` により再現できます。
* OpenAPI には `ErrorResponse` まで定義済みです。**HTTP レスポンスの JSON Schema 機械検証**を CI に載せるかどうかは、上表「REST API」の任意 backlog と同様に仕様・engineering で確定します。
