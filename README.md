# S2J Similarity Service

[![License: GPL v2](https://img.shields.io/badge/License-GPL%20v2-blue.svg)](https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html)
[![PHP](https://img.shields.io/badge/PHP-8.0-blue.svg)](https://www.php.net/)
[![Composer](https://img.shields.io/badge/Composer-v2-blue.svg)](https://getcomposer.org)

## Description

本『S2J Similarity Service』は、任意の言語における文章 A と文章 B 間の「意味的な類似度」を数値化して返却する、純粋な PHP ライブラリです。WordPress プラグイン開発等において利用可能な Composer パッケージとして提供されています。

このライブラリは、OpenAI Embeddings API (`text-embedding-3-small`) を用いて意味的な類似度を判定しています。

Strategy パターンを採用した `EmbeddingStrategyInterface` により、将来、`text-embedding-3-large` や、他ベンダーモデル (Claude、Gemini 等) に差し替え可能な設計となっています。

### 特徴

#### 🎯 コア機能

* **「意味的な類似度」の算出**: 2つの文章間の「意味的な類似度」を0.0〜1.0の数値で返却
* **コサイン類似度による判定**: ベクトル化された文章間のコサイン類似度を計算
* **多言語対応**: 任意の言語における文章の類似度判定が可能 (例: ja、en、fr)
* **ロケール対応**: 言語コードとロケールの両方を指定可能 (例: ja_JP、en_US、fr_FR)

#### 🛠️ 技術的特徴

* **Strategy パターン**: `EmbeddingStrategyInterface` により、将来の拡張が容易
* **Adapter パターン**: `OpenAIEmbeddingStrategy` にて外部 API との通信部分を抽象化
* **PSR-4準拠**: 標準的なオートローディングにより、Composer 経由で簡単に利用可能
* **純粋な PHP ライブラリ**: WordPress に依存しない、独立したライブラリ実装
* **セキュリティ**: API キーはライブラリ側では保持せず、コール側で管理

#### 🔧 拡張性

* **他ベンダーモデル対応**: 将来的に Claude、Gemini 等の他ベンダーモデルに差し替え可能
* **モデル選択**: `text-embedding-3-small` (通常利用) と `text-embedding-3-large` (精度重視) に対応

## License

このプロジェクトは GPL v2以降の下でライセンスされています - 詳細は [LICENSE](LICENSE) ファイルを参照してください。

## Support and Contact

サポート、機能リクエスト、またはバグ報告については、[GitHub Issues](https://github.com/stein2nd/s2j-similarity-service/issues) ページをご覧ください。

---

## Installation

### 前提条件

* PHP 8.0以降
* Composer
* OpenAI API キー (または互換 API のキー)

### Composer 経由でのインストール

プラグイン/テーマ側での利用例:

```zsh
composer require s2j/similarity-service
```

### プラグイン/テーマのメインファイルでの読み込み

```php
<?php
// Composer のオートローダーを読み込む
require_once __DIR__ . '/vendor/autoload.php';

use S2J\Similarity\Application\SimilarityService;
use S2J\Similarity\Infrastructure\Embedding\OpenAIEmbeddingStrategy;
```

## Usage

### 基本的な使用例

```php
<?php
require_once __DIR__ . '/vendor/autoload.php';

use S2J\Similarity\Application\SimilarityService;
use S2J\Similarity\Application\EmbeddingService;
use S2J\Similarity\Infrastructure\Embedding\OpenAIEmbeddingStrategy;

// Strategy をインスタンス化
$strategy = new OpenAIEmbeddingStrategy(
    apiKey: getenv('OPENAI_API_KEY'),
    defaultModel: 'text-embedding-3-small'
);

// SimilarityService をインスタンス化
$service = new SimilarityService($strategy);

// 類似度を計算
$score = $service->similarity(
    '文章 A の内容',
    '文章 B の内容',
    'text-embedding-3-small' // optional (未指定時は Strategy の defaultModel)
);

echo $score; // 0.82 など (0.0〜1.0)
```

### Embedding のみを生成する (`EmbeddingService`)

類似度計算ではなく、Embedding ベクトル (および `model/provider/dimension` メタデータ) を取得したい場合は `EmbeddingService` を使用します。

```php
<?php
require_once __DIR__ . '/vendor/autoload.php';

use S2J\Similarity\Application\EmbeddingService;
use S2J\Similarity\Infrastructure\Embedding\OpenAIEmbeddingStrategy;

$strategy = new OpenAIEmbeddingStrategy(
    apiKey: getenv('OPENAI_API_KEY'),
    defaultModel: 'text-embedding-3-small'
);

$embeddingService = new EmbeddingService(
    strategy: $strategy,
    provider: 'openai',
    defaultModel: 'text-embedding-3-small'
);

$embedding = $embeddingService->embed('文章の内容');

// Embedding (Domain Model)
// - $embedding->vector (float[])
// - $embedding->dimension (int)
// - $embedding->model (string)
// - $embedding->provider (string)
```

### キャッシュを有効化する例 (Decorator)

```php
<?php
require_once __DIR__ . '/vendor/autoload.php';

use S2J\Similarity\Application\SimilarityService;
use S2J\Similarity\Infrastructure\Cache\InMemoryCache;
use S2J\Similarity\Infrastructure\Embedding\CachedEmbeddingStrategy;
use S2J\Similarity\Infrastructure\Embedding\OpenAIEmbeddingStrategy;

$inner = new OpenAIEmbeddingStrategy(
    apiKey: getenv('OPENAI_API_KEY'),
    defaultModel: 'text-embedding-3-small'
);

$strategy = new CachedEmbeddingStrategy(
    cache: new InMemoryCache(),
    inner: $inner,
    provider: 'openai'
);

$service = new SimilarityService($strategy);
$score = $service->similarity('文章 A', '文章 B');
echo $score;
```

### バッチ計算

```php
<?php
require_once __DIR__ . '/vendor/autoload.php';

use S2J\Similarity\Application\SimilarityService;
use S2J\Similarity\Infrastructure\Embedding\OpenAIEmbeddingStrategy;

$strategy = new OpenAIEmbeddingStrategy(apiKey: getenv('OPENAI_API_KEY'));
$service = new SimilarityService($strategy);

$scores = $service->similarityOneToMany('query', ['a', 'b', 'c']);
$matrix = $service->similarityMatrix(['a', 'b', 'c']);
```

### パラメータ

| パラメータ | 型 | 説明 |
|---|---|---|
| a | string | テキスト A |
| b | string | テキスト B |
| model | string \| null | モデル名 (例: text-embedding-3-small)。未指定時は Strategy の default |

### API キー管理

* API キーは、コール側で管理し、Strategy のコンストラクタに注入します。
* グローバル変数での保持やログ出力は避け、環境変数等で管理してください。
* 例:
  * `OPENAI_API_KEY` (OpenAI Embeddings)

### 戻り値

* `float` (0.0〜1.0の類似度スコア)

## FAQ

### Q: このライブラリは、WordPress プラグイン以外でも使用できますか ?

A: はい、このライブラリは純粋な PHP ライブラリとして実装されているため、WordPress 以外のプロジェクトでも使用できます。

### Q: OpenAI API 以外の Embedding API を使用できますか ?

A: 将来的には、Strategy パターンにより他ベンダーの API (Claude、Gemini 等) にも対応予定です。現在は `OpenAIEmbeddingStrategy` のみが実装されています。

### Q: どのモデルを使用すべきですか ?

A: 通常利用では `text-embedding-3-small` を推奨します。精度重視の場合や多言語間 (特に低リソース言語) の類似度評価では `text-embedding-3-large` も検討してください。

### Q: 必要な PHP のバージョンは ?

A: PHP v8.0以降が必要です。Composer に対応した環境が必要です。

### Q: 外部依存の関係はありますか ?

A: 外部依存はありません。cURL は PHP 標準機能を使用します。

---

## Development

### 技術スタック

* **PHP**:
  * v8.0以降 (Composer に対応)
* **OpenAI Embeddings API**:
  * 「意味的な類似度」の算出
* **Composer**:
  * パッケージ管理とオートローディング (PSR-4準拠)

### モデル選定方針

| モデル名 | 用途 | コメント |
| --- | ---- | --- |
| `text-embedding-3-small` | 通常利用 | 「意味的な類似度」の判定、コスト効率に優れる |
| `text-embedding-3-large` | 精度重視 | 研究・学習データの類似検索等に向く |

* 原則として `text-embedding-3-small` を採用します。
* ただし、多言語間 (特に低リソース言語) の類似度評価では `large` も検討します。

### プロジェクト構造

```
s2j-similarity-service/
├── README.md
├── LICENSE
├── composer.json  # Composer パッケージ定義
├── .gitignore
├── phpunit.xml  # PHPUnit 設定ファイル
└┬─ src/  # ソースコード (PSR-4 準拠)
　├─ Application/
　├─ Contracts/
　├─ Core/
　├─ Domain/
　└─ Infrastructure/
```

### 開発環境のセットアップ

```zsh
# リポジトリをクローンする
git clone https://github.com/stein2nd/s2j-similarity-service.git

# プロジェクト・ディレクトリに移動する
cd s2j-similarity-service

# Composer 依存関係をインストールする (開発依存関係を含む)
composer install
```

## Testing

このライブラリでは、以下の2種類のテスト手法を提供しています。

### テスト手法の概要

1. **PHPUnit によるユニットテスト**: `phpunit.xml` と `tests/SimilarityTest.php` を使用した自動テスト
2. **CLI による手動テスト**: `examples/test_similarity.php` を使用した対話的なテスト

### API キーの取得と設定

テストを実行する前に、OpenAI API キーを取得し、環境変数として設定する必要があります。

#### API キーの取得方法

1. [OpenAI Platform](https://platform.openai.com/) にアクセスし、アカウントにログインします。
2. [API Keys](https://platform.openai.com/api-keys) ページに移動します。
3. 「Create new secret key」ボタンをクリックして新しい API キーを作成します。
4. 作成された API キーをコピーします (このキーは一度しか表示されないため、必ず保存してください)。

#### 環境変数の設定方法

##### macOS / Linux の場合

現在のセッションで一時的に設定する場合:

```zsh
export OPENAI_API_KEY=your_api_key_here
```

永続的に設定する場合 (`.zshrc` または `.bashrc` に追加):

```zsh
echo 'export OPENAI_API_KEY=your_api_key_here' >> ~/.zshrc
source ~/.zshrc
```

##### Windows の場合

コマンドプロンプト (一時的):

```cmd
set OPENAI_API_KEY=your_api_key_here
```

PowerShell (一時的):

```powershell
$env:OPENAI_API_KEY="your_api_key_here"
```

永続的に設定する場合は、「システムのプロパティ → 詳細設定 → 環境変数…」で、システム環境変数を設定してください。

### PHPUnit によるユニットテスト

PHPUnit を使用した自動テストを実行します。このテストは、`tests/SimilarityTest.php` で定義されたテストケースを実行し、`SimilarityService` の動作を検証します。

#### 依存関係のインストール

```zsh
composer install
```

#### PHPUnit によるユニットテストの実行

```zsh
./vendor/bin/phpunit
```

テストが成功すると、以下のような結果が表示されます:

```
PHPUnit 12.4.2 by Sebastian Bergmann and contributors.

Runtime:       PHP 8.x.x
Configuration: /path/to/s2j-similarity-service/phpunit.xml

.                                                                   1 / 1 (100%)

Time: 00:01.234, Memory: 8.00 MB

OK (1 test, 7 assertions)
```

**注意**: `OPENAI_API_KEY` 環境変数が設定されていない場合、テストはスキップされます。

#### テストファイルの構成

* `phpunit.xml`: PHPUnit の設定ファイル。テストスイートとコード・カバレッジの設定を含みます。
* `tests/SimilarityTest.php`: `SimilarityService` クラスのテストケースを定義しています。
  * `getenv('OPENAI_API_KEY')` を使用して API キーを取得します。
  * API キーが設定されていない場合は `markTestSkipped()` でテストをスキップします。

### CLI による手動テスト

CLI を使用した対話的なテストを実行します。この方法では、実際の API をコールして結果を確認できます。

#### CLI による手動テストの実行

環境変数 `OPENAI_API_KEY` を設定してから実行します:

```zsh
export OPENAI_API_KEY=your_api_key_here
php examples/test_similarity.php
```

実行すると、以下のような出力が表示されます:

```
類似度計算結果:
Array
(
    [similarity] => 0.852341
    [model] => text-embedding-3-small
    [language] => ja
)

類似度スコア: 0.852341
```

#### テストファイルの構成

* `examples/test_similarity.php`: CLI で実行可能なサンプル・スクリプト
  * `getenv('OPENAI_API_KEY')` を使用して API キーを取得します。
  * API キーが設定されていない場合は、エラーメッセージを表示して終了します。
  * 「今日は良い天気です」と「空が晴れていて気持ちが良い」の2つの日本語文章の類似度を計算します。

## Contributing

貢献をお待ちしています ! 以下の手順に従ってください:

1. リポジトリをフォークしてください。
2. 機能ブランチを作成してください (`git checkout -b feature/amazing-feature`)。
3. 変更をコミットしてください (`git commit -m 'Add some amazing feature'`)。
4. 機能ブランチにプッシュしてください (`git push origin feature/amazing-feature`)。
5. Pull Request を開いてください。

*詳細な情報については、[docs/SPEC.md](docs/SPEC.md) ファイルを参照してください。*

### 開発ガイドライン

* 既存のコードスタイルに従ってください。
* PSR-4準拠のオートローディングを維持してください。
* Strategy パターンの設計原則を尊重してください。
* 必要に応じて、ドキュメントを更新してください。

## Contributors & Developers

**"S2J Similarity Service"** はオープンソース・ソフトウェアです。以下の皆様がこのライブラリに貢献しています。

* **開発者**: Koutarou ISHIKAWA

---

## Changelog

### v1.0.0

* 初回リリース
* `SimilarityService` クラスの実装
* `EmbeddingStrategyInterface` インターフェイスの実装
* `OpenAIEmbeddingStrategy` クラスの実装
* `VectorMath` ユーティリティ・クラスの実装
* Composer パッケージ化 (PSR-4準拠)
* Strategy パターンによる拡張性の確保

## Upgrade Notice

### 1.0.0
S2J Similarity Service の初回リリース。このバージョンには、意味的な類似度判定のための全コア機能が含まれています。
