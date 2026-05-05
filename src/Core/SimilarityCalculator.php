<?php

namespace S2J\Similarity\Core;

final class SimilarityCalculator
{
    /**
     * @param float[] $vectorA
     * @param float[] $vectorB
     */
    public static function calculate(array $vectorA, array $vectorB): float
    {
        return VectorMath::cosineSimilarity($vectorA, $vectorB);
    }

    /**
     * @param float[] $queryVector
     * @param array<int, float[]> $candidateVectors
     * @return float[] scores in the same order
     */
    public static function calculateOneToMany(array $queryVector, array $candidateVectors): array
    {
        $scores = [];
        foreach ($candidateVectors as $i => $v) {
            $scores[$i] = VectorMath::cosineSimilarity($queryVector, $v);
        }

        ksort($scores);
        return array_values($scores);
    }

    /**
     * @param array<int, float[]> $vectors
     * @return float[][]
     */
    public static function calculateMatrix(array $vectors): array
    {
        $vectors = array_values($vectors);
        $n = count($vectors);

        $matrix = [];
        for ($i = 0; $i < $n; $i++) {
            $row = [];
            for ($j = 0; $j < $n; $j++) {
                $row[] = VectorMath::cosineSimilarity($vectors[$i], $vectors[$j]);
            }
            $matrix[] = $row;
        }

        return $matrix;
    }
}
