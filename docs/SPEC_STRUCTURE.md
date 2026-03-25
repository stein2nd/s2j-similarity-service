# 仕様書の細分化ガイド (WordPress プラグイン - PHP 向け)

## 目的

本ドキュメントは、**AI 伴走開発** と **後日のメンテナンス** を前提に、WordPress プラグイン/PHP ライブラリにおいて「仕様書をどこまで細分化しておくか」のベター・プラクティスをまとめたものです。  
Python スクリプト向けの [lead-validation-assist](https://github.com/stein2nd/lead-validation-assist) で採用した細分化方針を、PHP/WordPress 文脈に合わせて整理しています。

---

## 1. 細分化する理由

| 観点 | 細分化の効果 |
|------|----------------|
| **AI 伴走開発** | タスクごとに参照すべきドキュメントが明確になり、プロンプトに「このファイルだけ読め」と指定しやすい。変更の影響範囲も把握しやすい。 |
| **メンテナンス** | 責務ごとに仕様が分かれていると、修正時に該当ファイルだけ更新すればよく、他ドキュメントとの不整合が起きにくい。 |
| **オンボーディング** | 新規参加者が「全体像 → 興味のある部分」の順で読める。 |
| **テスト・検証** | 数式、データ形式、API 契約などが独立していると、仕様とテストの対応関係が明確になる。 |

---

## 2. 分けておくとよい「単位」

次の種類ごとに **1ファイル1関心** にすると、AI も人間も、参照しやすくなります。

| 単位 | 内容例 | ファイル名の例 |
|------|--------|----------------|
| **起点** | 仕様書の目次・プロジェクト概要へのリンク | `specs.md` |
| **存在理由** | なぜこのプロジェクトがあるか、解決する課題、提供価値 | `overview.md` |
| **数式・ロジック** | スコア算出式、閾値、正規化ルールなど「式で書けるもの」 | `*_spec.md` (例: `scoring_spec.md`, `similarity_spec.md`) |
| **データ定義** | 入出力の型、CSV/JSON の列定義、API のパラメータ・戻り値 | `data_dictionary.md`, `data_contract_spec.md` |
| **外部契約** | 外部 API のエンドポイント、リクエスト/レスポンス、エラー扱い | `*_api_spec.md`, `*_import_spec.md` |
| **コード構造** | ディレクトリ構成、クラス責務、パターン (Strategy/Adapter 等) | `architecture.md` |
| **パッケージ・配布** | Composer/npm の設定、インストール手順、依存関係 | `composer_package_spec.md` など |

WordPress プラグインの場合は、さらに次のような単位も検討できます。

- **フック・フィルター一覧** (`hooks_spec.md`): どのフックを提供するか、引数・戻り値
- **REST API 仕様** (`rest_api_spec.md`): エンドポイント、メソッド、スキーマ
- **DB スキーマ** (`schema_spec.md`): カスタムテーブルやオプション名

---

## 3. 粒度の目安

- **1ファイルは「ひとりの担当者が1テーマで完結して読める長さ」** (目安は100〜300行程度。複雑な数式や表が多い場合はもう少し長くても可)
- **「変更理由が同じもの」は同じファイルにまとめる**  
  例: コサイン類似度の式と閾値は同じ `similarity_spec.md`、OpenAI のエンドポイントとエラーは同じ `embedding_api_spec.md`
- **参照関係は「起点 → 各 spec」の一方向にするとよい**  
  必要なら「〇〇の詳細は `similarity_spec.md` 参照」とだけ書く。相互参照は最小限に。

---

## 4. 運用上の注意

- **起点ファイル (`specs.md`) を必ず用意する**  
  リポジトリを開いた人や AI が、まずここを見れば全体構成が分かるようにする。
- **ファイル名は役割が分かるようにする**  
  `overview.md`, `scoring_spec.md`, `data_dictionary.md` のように、内容が推測できる名前にする。
- **既存の単一 SPEC を残すかどうか**  
  後方互換のため `SPEC.md` を残し、「詳細は `docs/specs.md` の配下を参照」と書いておく方法が無難。逆に「単一ファイルは廃止し、常に分割版を参照」と決めてもよい。
- **仕様とコードの対応を書いておく**  
  `architecture.md` に「〇〇の仕様は `similarity_spec.md`」「実装は `src/VectorMath.php`」のように対応関係を書くと、AI が正しいファイルを開きやすい。

---

## 5. 本プロジェクト (S2J Similarity Service) での適用

上記にもとづき、次のように分割することを推奨します。

| ファイル | 役割 |
|----------|------|
| [specs.md](specs.md) | 仕様書の起点・目次 |
| [overview.md](overview.md) | プロジェクトの存在理由・提供価値 |
| [similarity_spec.md](similarity_spec.md) | 類似度算出ロジックの数式化 (コサイン類似度など) |
| [embedding_api_spec.md](embedding_api_spec.md) | Embedding API (OpenAI) の契約・エラー扱い |
| [data_contract_spec.md](data_contract_spec.md) | 入出力の定義 (パラメータ・戻り値) |
| [architecture.md](architecture.md) | コード構造とクラス責務 |
| [composer_package_spec.md](composer_package_spec.md) | Composer パッケージ仕様 |

従来の [SPEC.md](SPEC.md) は、上記の「統合版」として残し、`specs.md` からリンクする形にすると、既存の参照を壊さずに移行できます。
