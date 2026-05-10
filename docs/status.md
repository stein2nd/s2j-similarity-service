<!--
目的：「実装状況サマリー、Backlog、品質レポート、まとめ」の明文化
-->

## S2J Similarity Service - 実装状況

本ページは、現状の実装状況を「機能単位」で一覧化し、**実装を100%にするために仕様書で何を明確化すべきか** を示します。

### 仕様書 (参照元)

* `README.md` (ユーザー向けの利用仕様)
* `docs/core/similarity_spec.md` (類似度算出の仕様)
* `docs/interfaces/usage_spec.md` (利用手順・例コードの仕様)
* `docs/interfaces/rest_api_spec.md` (REST API 仕様)
* `docs/interfaces/sdk_spec.md` (型安全 SDK 設計)
* `docs/contracts/codegen_spec.md` (OpenAPI codegen ・生成物の扱い)
* `schema/openapi.yaml` (OpenAPI: API 契約の起点)

※ `docs_old/` 配下は「仕様執筆の参考資料」であり、**実装状況の評価・完了条件の根拠には使用しません。**

### 機能一覧 (実装状況サマリー)

| 機能名 | 実装済み/未実装 | 実装％ | 完了条件 (実装％を100にするために仕様書で明確化すべき点) |
|---|---:|---:|---|
| 類似度算出 (2文)`SimilarityService::similarity()` | 実装済み | 100 | - **docs/core/similarity_spec.md**: cosine + 0..1正規化 + clamp の整合。追加の明確化なし |
| 類似度算出 (1対多)`SimilarityService::similarityOneToMany()` | 実装済み | 100 | - **docs/core/similarity_spec.md**: 出力の順序保証 (入力順維持) の整合。追加の明確化なし |
| 類似度算出 (N 対 N 行列)`SimilarityService::similarityMatrix()` | 実装済み | 100 | - **docs/core/similarity_spec.md**: 行列定義 (対称/対角) の整合。追加の明確化なし |
| Embedding 生成 (単発)`EmbeddingService::embed()` | 実装済み | 100 | - **コード**: `EmbeddingService::embed()` および依存 Strategy は実装済み (`tests/EmbeddingServiceTest.php` 等)。<br>- **ドキュメント**: **docs/interfaces/usage_spec.md** の PHP 例は `S2J\Similarity\...` に統一済み。追加の明確化なし |
| Embedding 生成 (バッチ)`EmbeddingService::embedBatch()` | 実装済み | 100 | - **docs/interfaces/sdk_spec.md**:「出力は入力順を維持」の整合。追加の明確化なし |
| OpenAI Embeddings 連携 `OpenAIEmbeddingStrategy` | 実装済み | 100 | - **コード**: コンストラクタは `apiKey` / `defaultModel` / `endpoint` / `timeoutSeconds` (`src/Infrastructure/Embedding/OpenAIEmbeddingStrategy.php`)。バッチ・エラー分岐は実装済み。<br>- **ドキュメント**: **docs/interfaces/usage_spec.md** § 設定・コード例は実装と一致。**README.md** の Usage に見出し「OpenAIEmbeddingStrategy オプション」があり、名前付き引数・コード例・オプション一覧がコンストラクタと一致 (HTTP クライアント差し替えは未実装のため記載なし)。追加の明確化なし |
| ベクトル正規化 (L2正規化) | 実装済み | 100 | - **docs/core/similarity_spec.md**:「入力は正規化済み」前提を、Strategy 側で担保する方針として明文化 (現実装と一致)。追加の明確化なし |
| キャッシュ (Embedding Decorator)`CachedEmbeddingStrategy` + `InMemoryCache` | 実装済み | 100 | - **docs/interfaces/sdk_spec.md**: キャッシュキーは正規化テキスト (trim + lower) + model (未指定は `__default__`) + provider + normalized=true を JSON 化し sha256。TTL デフォルト値は24h。追加の明確化なし |
| エラー型 (DomainError 系) | 実装済み | 100 | - **docs/interfaces/rest_api_spec.md** の命名規則どおり、`error.type` / OpenAPI enum / PHP `DomainError::$type` は snake_case に統一済み。追加の明確化なし |
| 旧 API (`S2J\\SimilarityService\\...`) の削除 | 実装済み | 100 | - **docs/interfaces/usage_spec.md** の方針どおり、旧 API の namespace と旧メソッドを削除済み。追加の明確化なし |
| テスト基盤 (PHPUnit + WorDBless) | 実装済み | 100 | - `phpunit/phpunit` は **^13.1**、`phpunit.xml` は PHPUnit 12.x スキーマ。<br>- **`composer.lock` をリポジトリ管理** し、`composer install` で WordPress 配置・ WorDBless ドロップインまで再現可能。<br>- **`tests/bootstrap.php`** で WorDBless により WordPress 実ランタイムをテストに読み込み。<br>- `composer test` で `tests/` 以下を一括実行。追加の明確化なし |
| HTTP サーバー実装 (WordPress REST API Adapter) | 実装済み | 100 | - **実装済**: `adapters/http/wordpress/` にて `Routes::register` → `register_rest_route` (唯一の routing mechanism)、`SimilarityController` / `EmbeddingController`、`RequestValidator`、`ErrorMapper`、`ResponseFactory`、`BearerTokenAuth` (第2引数・`permissionDenied` による **403**)、`rate_limit` 時の **`Retry-After`** (`DomainError::$details['retry_after']` が非負整数のとき)。デフォルト namespace は `Routes::DEFAULT_NAMESPACE` (`s2j/v1`)。`Routes` クラスは **docs/interfaces/rest_api_spec.md** § REST API (HTTP Runtime / WordPress REST Adapter) を参照。<br>- **仕様反映済み**: **rest_api_spec.md** に「WordPress REST API Adapter の完成条件」および「REST API (HTTP Runtime / WordPress REST Adapter)」(OpenAPI 論理パスと WordPress runtime の関係・責務境界) が明文化されている。<br>- **(任意・ドキュメント)** rest_api_spec 内にコード現状と齟齬する「未実装」列挙が残っていれば、仕様側を現状に合わせて整理する。 |
| REST API (`POST /v1/similarity`, `POST /v1/embedding` の契約に対応する HTTP 面) | 実装済み | 100 | - **実装済**: **docs/interfaces/rest_api_spec.md** の「REST API の成立条件」および § REST API (HTTP Runtime / WordPress REST Adapter) に沿い、`schema/openapi.yaml`、`Routes` + `register_rest_route`、Controller 層、リクエスト検証、`ErrorMapper`、`tests/Integration/WordPressRestAdapterIntegrationTest.php` (WorDBless + `WP_REST_Server`)。多数の HTTP ステータスを統合テストで検証 (429には `Retry-After` を含む)。OpenAPI 論理パス ↔ `/wp-json/s2j/v1/...` または `?rest_route=/s2j/v1/...` は仕様・テスト (`rest_url()`) で整合。<br>- **ユーザー導線 (必須) 達成**: **README.md** に OpenAPI 論理パス、WordPress runtime URL、`rest_api_init` の登録例、Bearer 運用、curl 例を記載済み (rest_api_spec の「100% 完了条件」必須項目)。<br>- **(任意)** OpenAPI レスポンスの JSON Schema 機械検証を CI に載せるかは engineering policy で確定。 |
| OpenAPI を Single Source にした codegen (TS types/Zod/PHP DTO) | 実装済み | 100 | - **実行**: `scripts/generate/all.zsh` を唯一の入口とし、`schema/openapi.yaml` から生成 (開発 / CI / release のみ)。再現性は `openapitools.json` + `package-lock.json`。<br>- **出力**: PHP DTO は `src/Contracts/DTO/Generated/` (Composer 配布)。TS types ・ Zod ・ raw client は `tools/generated/ts/` (リポジトリ追跡、**docs/contracts/codegen_spec.md** の公開境界)。Composer の Git アーカイブから TS は `.gitattributes` の `export-ignore` で除外。<br>- **CI**: `.github/workflows/openapi-codegen.yml` (Node **22**、`npm ci` → `./scripts/generate/all.zsh` → `npm run build -w @s2j/similarity-client` → `git diff --exit-code`)。ローカルは `npm run verify:codegen` / `npm run build:ts-client`。<br>- **追加で詰められる余地** (実装％とは別): OpenAPI レスポンスの JSON Schema 機械検証を CI に足すかどうか |
| TypeScript SDK (`@s2j/similarity-client`: ApiClient / HttpClient / Timeout / Retry / エラー union) | 実装済み | 95 | - **実装済**: **docs/interfaces/sdk_spec.md** (「TypeScript SDK (@s2j/similarity-client)」ほか) に整合する形で、`packages/ts-client` に公開ラッパーを実装。`createApiClient` / `ApiClient.similarity` / `embedding`、`HttpClient` (`fetch` 抽象)、`withTimeout` と軽量 `withRetry` (指数バックオフ。**sdk_spec** が定めるとおり、通信失敗・タイムアウト・レート制限・サービス一時不可を対象)、`error.type` 基準の判別可能 union (`SDKError`) と `SDKErrorBase` / `isSDKError`、REST `ErrorResponse` の正規化。生成 raw client は `tools/generated/ts` にとどめ、公開 API は本パッケージのみ。<br>- **ユーザー導線**: **README.md** および **docs/interfaces/usage_spec.md** に、モノレポパス、`createApiClient` の最小例、`baseUrl` と WordPress REST の対応、`npm run build -w @s2j/similarity-client` / `npm run verify:codegen` を追記済み (**sdk_spec** の該当節の完了条件に対応)。<br>- **100% の残り**: `packages/ts-client` に **自動テスト** (モック `HttpClient` 等) がなく、CI でも SDK ユニットテストは実行していない。追加と CI 必須化は engineering で確定。<br>- **(任意)** 成功ボディの runtime 検証を Zod 等で載せるかは **sdk_spec**・運用で確定 (現状は形状チェック中心)。 |
| README / 使用方法ドキュメントの整合 (命名・例コード) | 実装済み | 100 | - **README.md**: PHP の `SimilarityService` / `EmbeddingService` / キャッシュは現行 `S2J\Similarity\...` で整合。**WordPress REST アダプタ**は論理パス・`/wp-json/s2j/v1/...`、`Routes::register`、Bearer、curl まで記載済み。**TypeScript SDK** は利用・ビルド・`verify:codegen` を記載済み。Usage の「OpenAIEmbeddingStrategy オプション」により、`OpenAIEmbeddingStrategy` の公開コンストラクタが **README・usage_spec・実装の三者で一致**。<br>- **usage_spec.md**: 名前空間・コンストラクタ・例外・TS SDK 区分はコードベースと整合 (長文の細部は運用で継続レビュー)。追加の明確化なし |

### 補足 (現行コードから見える前提)

* 本リポジトリは **「純粋な PHP ライブラリ」** としては主要機能が実装済み (類似度計算、Embedding、バッチ、キャッシュ、基本エラー)。
* 公開 API は `S2J\Similarity\...` に統一され、`S2J\SimilarityService\...` (旧 API) は削除済みです (Composer の autoload に過去互換の接頭辞が残っていても、旧クラスは提供しません)。
* `error.type` は `schema/openapi.yaml` と `DomainError::$type` とで snake_case に統一済みです。
* HTTP runtime は WordPress REST API のみ (`register_rest_route`) とし、`adapters/http/wordpress/` に routing、Controller、認証・エラーマッピング・レスポンス整形を実装済みです。**HTTP 統合テスト**は WorDBless 上の `WP_REST_Server` / `rest_do_request()` で実行します。**README.md** には OpenAPI 論理パスと WordPress 実エンドポイント、プラグインへの組込み、コマンドラインによる REST 呼び出し例、TypeScript SDK の利用手順、および Usage の「OpenAIEmbeddingStrategy オプション」(コンストラクタの公式な簡易リファレンス) が記載されています。
* **`composer.lock`** をバージョン固定のソースとして追跡しており、clone 後は `composer install` でテスト用 WordPress 配置と WorDBless のドロップインまで再現できます。
* **OpenAPI codegen**: `docs/contracts/codegen_spec.md` の Governance に従い、`npm run verify:codegen` と GitHub Actions (`.github/workflows/openapi-codegen.yml`) で生成差分ゼロを検証済みです。同ジョブで **`@s2j/similarity-client` の `tsc` ビルド**も実行します (GitHub Actions の Node は **22.x**)。
* **TypeScript SDK** は npm ワークスペース `packages/ts-client` (パッケージ名 `@s2j/similarity-client`) として実装済みです。`tools/generated/ts` の生成クライアントは内部利用のみとし、外部からは本ラッパーを公開面とします。
* **GitHub Actions** は現時点で **OpenAPI codegen + TS SDK ビルド**のジョブのみです。**PHPUnit / `composer test`** はワークフローに未定義で、ローカルまたは別 CI で実行する運用です。
* OpenAPI には `ErrorResponse` まで定義済みです。**HTTP レスポンスの JSON Schema 機械検証**を CI に載せるかどうかは、上表「REST API」の任意 backlog と同様に仕様・ engineering で確定します。
