<?php
require_once __DIR__ . '/../vendor/autoload.php';

use S2J\Similarity\Application\SimilarityService;
use S2J\Similarity\Infrastructure\Embedding\OpenAIEmbeddingStrategy;

$apiKey = getenv('OPENAI_API_KEY');
if (empty($apiKey)) {
    echo "Error: OPENAI_API_KEY environment variable is not set.\n";
    exit(1);
}

// Strategy をインスタンス化
$strategy = new OpenAIEmbeddingStrategy(
    apiKey: $apiKey,
    defaultModel: 'text-embedding-3-small'
);

// SimilarityService をインスタンス化
$service = new SimilarityService($strategy);

// 類似度を計算
$score = $service->similarity(
    '今日は良い天気です',
    '空が晴れていて気持ちが良い',
    'text-embedding-3-small'
);

echo "\n類似度スコア: " . $score . "\n";
