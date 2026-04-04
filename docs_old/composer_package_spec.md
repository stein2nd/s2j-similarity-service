<!--
目的：「Composer パッケージ仕様」の明文化
-->

# S2J Similarity Service - Composer パッケージ仕様

## パッケージ識別

| 項目 | 値 |
|------|-----|
| name | `s2j/similarity-service` |
| description | A pure PHP library for semantic similarity detection. |
| type | library |
| license | GPL-2.0-or-later |

## `composer.json` の主要設定

```json
{
    "name": "s2j/similarity-service",
    "description": "A pure PHP library for semantic similarity detection.",
    "type": "library",
    "license": "GPL-2.0-or-later",
    "autoload": {
        "psr-4": {
            "S2J\\SimilarityService\\": "src/"
        }
    },
    "require": {
        "php": ">=8.0"
    }
}
```

## オートロード

* **PSR-4準拠**: `S2J\SimilarityService\` → `src/`
* 名前空間の接頭辞: `S2J\SimilarityService\`
* `src/` 配下の PHP ファイルがオートロード対象。

## インストール (プラグイン/テーマ側)

```bash
composer require s2j/similarity-service
```

## 読み込み例 (プラグイン/テーマのメインファイル)

```php
<?php
require_once __DIR__ . '/vendor/autoload.php';

use S2J\SimilarityService\SimilarityService;
use S2J\SimilarityService\OpenAIEmbeddingStrategy;

$strategy = new OpenAIEmbeddingStrategy();
$service = new SimilarityService($strategy);

$result = $service->compare(
    getenv('OPENAI_API_KEY'),
    'text-embedding-3-small',
    'en',
    'en_US',
    '文章 A の内容',
    '文章 B の内容'
);

echo $result['similarity'];
```

## 依存関係

* **PHP**: v8.0以降
* **外部パッケージ**: なし (cURL は PHP 標準機能を使用)
