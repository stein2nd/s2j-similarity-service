<?php
require_once __DIR__ . '/../vendor/autoload.php';

use S2J\SimilarityService\SimilarityService;
use S2J\SimilarityService\OpenAIEmbeddingStrategy;

$apiKey = getenv('OPENAI_API_KEY');
if (empty($apiKey)) {
    echo "Error: OPENAI_API_KEY environment variable is not set.\n";
    exit(1);
}

// Strategy をインスタンス化
$strategy = new OpenAIEmbeddingStrategy();

// SimilarityService をインスタンス化
$service = new SimilarityService($strategy);

// 類似度を計算
$result = $service->compare(
    $apiKey,
    'text-embedding-3-small',
    'ja',
    'ja_JP',
    '今日は良い天気です',
    '空が晴れていて気持ちが良い'
);

echo "類似度計算結果:\n";
print_r($result);
echo "\n類似度スコア: " . $result['similarity'] . "\n";
