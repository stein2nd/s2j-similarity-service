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
| REST API (`POST /v1/similarity`, `POST /v1/embedding`) | 未実装 | 0 | - **docs/interfaces/rest_api_spec.md**: 提供形態 (どのランタイム/フレームワークでホストするか) を確定<br>- **docs/interfaces/rest_api_spec.md**: 認証 (Bearer token) の検証方法・権限モデルを確定<br>- **docs/interfaces/rest_api_spec.md**: レート制限の具体値 (単位/上限/Retry-After) を確定 |
| OpenAPI を Single Source にした codegen (TS types/Zod/PHP DTO) | 未実装 | 10 | - **schema/openapi.yaml**: `components/schemas` は定義済み。codegen 実行基盤 (生成ツール/出力先/CI) を確定<br>- **docs/interfaces/sdk_spec.md**: 生成物の公開範囲 (contracts/core/client) と「raw client を公開するか」を確定 |
| TypeScript SDK (ApiClient/HttpClient/Retry/Timeout 等) | 未実装 | 0 | - **docs/interfaces/sdk_spec.md**: 最小実装スコープ (ApiClientのみ vs Retry/Timeout まで) を確定<br>- **docs/interfaces/sdk_spec.md**: エラー型 (DomainError) を TS 側でどう表現するか (enum/union/class) を確定 |
| README / 使用方法ドキュメントの整合 (命名・例コード) | 未実装 | 60 | - **docs/interfaces/usage_spec.md**: 例コードの用語/命名 (provider/strategy 等) と実装の整合を仕様として確定<br>- **README.md**: ライブラリの「入口」を `SimilarityService` に統一するか、`EmbeddingService` も正式に案内するか方針決め |

### 補足 (現行コードから見える前提)

* 本リポジトリは **「純粋な PHP ライブラリ」** としては主要機能が実装済み (類似度計算・Embedding・バッチ・キャッシュ・基本エラー)。
* 公開 API は `S2J\Similarity\...` に統一され、`S2J\SimilarityService\...` (旧 API) は削除済みです。
* `error.type` は `schema/openapi.yaml` と `DomainError::$type` とで snake_case に統一済みです。
* `docs/interfaces/rest_api_spec.md` と `schema/openapi.yaml` は存在し、OpenAPI には `ErrorResponse` まで定義済みですが、**HTTP サーバ実装 (ルーティング/コントローラ) は見当たらない** ため、REST API は、未実装扱いです。
