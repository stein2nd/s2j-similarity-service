<!--
目的：「実装状況サマリー、Backlog、品質レポート、まとめ」の明文化
-->

# S2J Similarity Service - 実装状況

## 実装済み

* Core: ベクトル演算・類似度算出（正規化スコア、バッチ計算含む）
* 80%
	* PHP

* Contracts: EmbeddingStrategyInterface（Provider差し替え前提）
* 80%
	* PHP

* Domain: Embedding / SimilarityRequest / SimilarityResponse（ドメインモデル）
* 60%
	* PHP

* Application: SimilarityService（single / 1×N / N×N）
* 70%
	* PHP

* Application: EmbeddingService（vector + model + provider の統一取り扱い）
* 60%
	* PHP

* Infrastructure: OpenAIEmbeddingStrategy（APIコール + 正規化 + エラー分類）
* 75%
	* PHP

* Infrastructure: CachedEmbeddingStrategy（Decorator）+ InMemoryCache
* 80%
	* PHP

* Contracts/Application: Embedding バッチ（未対応プロバイダは逐次フォールバック）
* 70%
	* PHP

* Error: DomainError 体系（Validation / InvalidArgument / Calculation / Network / Timeout / RateLimit / Provider）
* 70%
	* PHP

## 未実装

* Provider 追加（Claude / Gemini 等）
* 完了条件
	* `docs/contracts/embedding_api_spec.md`
		* 対象Providerごとの「最小要件」（認証/エンドポイント/レスポンス差異/制限）の明記
		* バッチ対応可否とフォールバック条件の明記

* 通信ポリシー（Retry / Retry-After / Backoff / Timeout / Circuit Breaker）
* 完了条件
	* `docs/interfaces/sdk_spec.md`
		* Retry対象・回数・backoff（指数）・Retry-After優先の確定
		* timeout と circuit breaker のデフォルト値と責務境界の確定
	* `docs/contracts/embedding_api_spec.md`
		* エラー分類とリトライ可否の対応表の確定

* provider/model 制約の強制（同一provider + 同一model のみ比較可能）
* 完了条件
	* `docs/contracts/data_contract_spec.md`
		* 制約違反時の挙動（例外種別/メッセージ/詳細）を確定
		* Embedding（model/provider）の必須性と受け渡し経路を確定

* 正規化責務の一本化（L2正規化の実施箇所）
* 完了条件
	* `docs/core/similarity_spec.md`
		* Coreが「正規化を実施する/しない」の最終決定（他層での二重正規化禁止ルール）
	* `docs/contracts/embedding_api_spec.md`
		* Strategy側の正規化要件（必須/任意）と、未正規化入力を許容するかの確定

* OpenAPI / codegen 連動（DTO生成と手書き領域の境界）
* 完了条件
	* `docs/contracts/codegen_spec.md`
		* PHP DTO の生成範囲・配置・編集禁止ルールの確定
	* `docs/contracts/openapi_spec.md`
		* エラーSchema（ErrorResponse等）とDomainError対応の確定

* キャッシュ仕様の確定（キー/TTL/無効化条件）
* 完了条件
	* `docs/interfaces/sdk_spec.md`
		* TTLデフォルトと、provider/model変更時の無効化条件の確定
		* cache interface の最終I/F（同期/非同期、型）を確定
	* `docs/contracts/embedding_api_spec.md`
		* キャッシュキーに含める属性（text/model/provider/normalized）の確定

* バッチの失敗方針（fail-fast / partial-result）
* 完了条件
	* `docs/interfaces/sdk_spec.md`
		* 部分成功を採用するか（将来拡張か）を明記し、デフォルト挙動を確定
