<?php

namespace S2J\Similarity\Application;

use S2J\Similarity\Contracts\EmbeddingStrategyInterface;
use S2J\Similarity\Core\SimilarityCalculator;

final class SimilarityService
{
    public function __construct(
        private readonly EmbeddingStrategyInterface $strategy
    ) {
    }

    public function similarity(
        string $a,
        string $b,
        ?string $model = null
    ): float {
        $va = $this->strategy->embed($a, $model);
        $vb = $this->strategy->embed($b, $model);

        return SimilarityCalculator::calculate($va, $vb);
    }

    /**
     * @param string[] $candidates
     * @return float[] scores (same order as inputs)
     */
    public function similarityOneToMany(
        string $query,
        array $candidates,
        ?string $model = null
    ): array {
        $vq = $this->strategy->embed($query, $model);

        $vectors = [];
        foreach ($candidates as $i => $candidate) {
            $vectors[$i] = $this->strategy->embed((string)$candidate, $model);
        }

        return SimilarityCalculator::calculateOneToMany($vq, $vectors);
    }

    /**
     * @param string[] $inputs
     * @return float[][] matrix[i][j] corresponds to inputs[i], inputs[j]
     */
    public function similarityMatrix(
        array $inputs,
        ?string $model = null
    ): array {
        $vectors = [];
        foreach ($inputs as $i => $text) {
            $vectors[$i] = $this->strategy->embed((string)$text, $model);
        }

        ksort($vectors);
        return SimilarityCalculator::calculateMatrix(array_values($vectors));
    }
}
