<?php
/**
 * VectorMath
 *
 * ベクトル化された2文字列間のコサイン類似度を計算する、ユーティリティ・クラス。
 *
 * @package S2J\SimilarityService
 * @license GPL-2.0-or-later
 */

namespace S2J\SimilarityService;

class VectorMath
{
    /**
     * 2つのベクトル間のコサイン類似度を計算。
     *
     * @param array $vecA
     * @param array $vecB
     * @return float 類似度 (0〜1)
     */
    public static function cosineSimilarity(array $vecA, array $vecB): float
    {
        $dot = 0.0;
        $normA = 0.0;
        $normB = 0.0;

        $length = min(count($vecA), count($vecB));
        for ($i = 0; $i < $length; $i++) {
            $dot += $vecA[$i] * $vecB[$i];
            $normA += $vecA[$i] ** 2;
            $normB += $vecB[$i] ** 2;
        }

        if ($normA === 0.0 || $normB === 0.0) {
            return 0.0;
        }

        return $dot / (sqrt($normA) * sqrt($normB));
    }
}
