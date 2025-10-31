<?php
/**
 * SimilarityService
 *
 * 同一言語内の文章類似度を判定する、サービス・クラス。
 *
 * @package S2J\SimilarityService
 * @license GPL-2.0-or-later
 */

namespace S2J\SimilarityService;

use Exception;

class SimilarityService
{
    private EmbeddingStrategyInterface $strategy;

    public function __construct(EmbeddingStrategyInterface $strategy)
    {
        $this->strategy = $strategy;
    }

    /**
     * 与えられた2つの文章の意味的類似度を計算。
     *
     * @param string $apiKey API Key
     * @param string $model モデル名 (例: text-embedding-3-small)
     * @param string $language 言語コード (例: ja、en、fr)
     * @param string $locale ロケール (例: ja_JP、en_US、fr_FR)
     * @param string $baseText 基準テキスト本文
     * @param string $targetText 検証テキスト本文
     * @return array { similarity: float, model: string, language: string } 類似度 (float)
     * @throws Exception API 通信エラーや形式不正など
     */
    public function compare(
        string $apiKey,
        string $model,
        string $language,
        string $locale,
        string $baseText,
        string $targetText
    ): array {
        $embeddingA = $this->strategy->getEmbedding($apiKey, $model, $baseText, $language, $locale);
        $embeddingB = $this->strategy->getEmbedding($apiKey, $model, $targetText, $language, $locale);

        $similarity = VectorMath::cosineSimilarity($embeddingA, $embeddingB);

        return [
            'similarity' => round($similarity, 6),
            'model' => $model,
            'language' => $language,
        ];
    }
}
